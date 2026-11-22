<?php
declare(strict_types=1);

final class Router
{
    private static array $routes = [];
    /** Rotas POST que não usam o token CSRF da sessão (validadas por assinatura própria). */
    private static array $csrfExempt = [];

    public static function add(string $method, string $pattern, callable|array $handler, array $middleware = [], bool $csrf = true): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[0-9]+)', rtrim($pattern, '/') ?: '/') . '$#';
        self::$routes[] = compact('method', 'pattern', 'regex', 'handler', 'middleware');
        if (!$csrf) {
            self::$csrfExempt[] = $regex;
        }
    }

    public static function get(string $p, callable|array $h, array $m = []): void { self::add('GET', $p, $h, $m); }
    public static function post(string $p, callable|array $h, array $m = [], bool $csrf = true): void { self::add('POST', $p, $h, $m, $csrf); }

    public static function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        $path = rtrim($path, '/') ?: '/';
        $allowed = false;
        foreach (self::$routes as $r) {
            if (!preg_match($r['regex'], $path, $m)) {
                continue;
            }
            if ($r['method'] !== $method && !($method === 'HEAD' && $r['method'] === 'GET')) {
                $allowed = true;
                continue;
            }
            if ($method === 'POST' && !in_array($r['regex'], self::$csrfExempt, true)) {
                Csrf::verify();
            }
            foreach ($r['middleware'] as $mw) {
                Middleware::run($mw);
            }
            $params = array_map('intval', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
            $h = $r['handler'];
            if (is_array($h)) {
                [$class, $fn] = $h;
                (new $class())->$fn(...array_values($params));
            } else {
                $h(...array_values($params));
            }
            return;
        }
        abort($allowed ? 405 : 404, $allowed ? 'Método não permitido.' : 'Página não encontrada.');
    }
}
