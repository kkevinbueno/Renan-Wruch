<?php
declare(strict_types=1);

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function fetch(string $template, array $data = []): string
    {
        $file = APP_DIR . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View não encontrada: {$template}");
        }
        extract(self::$shared + $data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** Renderiza um template dentro de um layout ('public', 'app' ou null). */
    public static function render(string $template, array $data = [], ?string $layout = 'app'): void
    {
        $content = self::fetch($template, $data);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::fetch('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function partial(string $name, array $data = []): string
    {
        return self::fetch('partials/' . $name, $data);
    }
}
