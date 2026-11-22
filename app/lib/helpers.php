<?php
declare(strict_types=1);

function config(string $path, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'];
    foreach (explode('.', $path) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function storage_path(string $sub = ''): string
{
    return rtrim((string) config('app.storage'), '/') . ($sub !== '' ? '/' . ltrim($sub, '/') : '');
}

/** Escapa conteúdo para HTML (proteção contra XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '', array $query = []): string
{
    $u = config('app.url') . '/' . ltrim($path, '/');
    if ($query) {
        $u .= (str_contains($u, '?') ? '&' : '?') . http_build_query($query);
    }
    return $u;
}

/** Caminho relativo (usado em links internos para funcionar em qualquer domínio). */
function path(string $path = '', array $query = []): string
{
    $p = '/' . ltrim($path, '/');
    if ($query) {
        $p .= '?' . http_build_query($query);
    }
    return $p;
}

function asset(string $file): string
{
    $full = APP_ROOT . '/public/assets/' . $file;
    $v = is_file($full) ? substr(md5((string) filemtime($full)), 0, 8) : APP_VERSION;
    return '/assets/' . $file . '?v=' . $v;
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url(config('app.url'), PHP_URL_HOST);
    if ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? $host)) {
        redirect($ref);
    }
    redirect($fallback);
}

/** Data/hora atual em UTC no formato do banco. */
function now(): string
{
    return gmdate('Y-m-d H:i:s', Clock::time());
}

function app_tz(): DateTimeZone
{
    static $tz = null;
    return $tz ??= new DateTimeZone((string) config('app.timezone'));
}

/** Data local (America/Sao_Paulo) de hoje, Y-m-d. */
function today(): string
{
    return (new DateTimeImmutable('@' . Clock::time()))->setTimezone(app_tz())->format('Y-m-d');
}

function local_now(): DateTimeImmutable
{
    return (new DateTimeImmutable('@' . Clock::time()))->setTimezone(app_tz());
}

/** Converte DATETIME UTC do banco para DateTime local. */
function to_local(?string $utc): ?DateTimeImmutable
{
    if (!$utc) {
        return null;
    }
    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(app_tz());
}

/** Converte data/hora local digitada (Y-m-d H:i) para UTC. */
function local_to_utc(string $local): string
{
    return (new DateTimeImmutable($local, app_tz()))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function date_add_days(string $date, int $days): string
{
    return (new DateTimeImmutable($date))->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
}

function days_between(string $from, string $to): int
{
    return (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->format('%r%a');
}

const PT_WEEKDAYS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
const PT_WEEKDAYS_SHORT = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
const PT_MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

function fmt_date(?string $date, bool $withWeekday = false): string
{
    if (!$date) {
        return '—';
    }
    $d = new DateTimeImmutable(substr($date, 0, 10));
    $s = $d->format('d') . ' de ' . PT_MONTHS[(int) $d->format('n') - 1];
    if ($d->format('Y') !== local_now()->format('Y')) {
        $s .= ' de ' . $d->format('Y');
    }
    return $withWeekday ? PT_WEEKDAYS[(int) $d->format('w')] . ', ' . $s : $s;
}

function fmt_date_short(?string $date): string
{
    return $date ? (new DateTimeImmutable(substr($date, 0, 10)))->format('d/m/Y') : '—';
}

function fmt_datetime(?string $utc): string
{
    $d = to_local($utc);
    return $d ? $d->format('d/m/Y \à\s H:i') : '—';
}

function fmt_time(?string $utc): string
{
    $d = to_local($utc);
    return $d ? $d->format('H:i') : '';
}

function fmt_ago(?string $utc): string
{
    if (!$utc) {
        return 'nunca';
    }
    $diff = Clock::time() - strtotime($utc . ' UTC');
    if ($diff < 60) return 'agora';
    if ($diff < 3600) return 'há ' . intdiv($diff, 60) . ' min';
    if ($diff < 86400) return 'há ' . intdiv($diff, 3600) . ' h';
    $days = intdiv($diff, 86400);
    if ($days === 1) return 'ontem';
    if ($days < 30) return "há {$days} dias";
    return fmt_date(to_local($utc)->format('Y-m-d'));
}

function money(int $cents): string
{
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
}

function parse_money(string $value): int
{
    $v = preg_replace('/[^\d,\.]/', '', $value);
    if (str_contains($v, ',')) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    return (int) round(((float) $v) * 100);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_json_request(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json') || $xhr === 'fetch' || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
}

/** Lê um campo da requisição (POST > GET > corpo JSON) já sem espaços nas pontas. */
function input(string $key, mixed $default = null): mixed
{
    static $json = null;
    if ($json === null) {
        $json = [];
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $json = is_array($decoded) ? $decoded : [];
        }
    }
    $v = $_POST[$key] ?? $json[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, ?int $default = null): ?int
{
    $v = input($key);
    return ($v === null || $v === '' || !is_numeric($v)) ? $default : (int) $v;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    if (is_json_request()) {
        json_out(['ok' => false, 'error' => $message ?: 'Não permitido.'], $code);
    }
    View::render('shared/error', ['code' => $code, 'message' => $message], 'public');
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function remember_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['_csrf']);
    $_SESSION['_old'] = $data;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old_view'][$key] ?? $default;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function request_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function setting(string $key, mixed $default = null): mixed
{
    return Settings::get($key, $default);
}

function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="' . asset('icons/sprite.svg') . '#' . e($name) . '"></use></svg>';
}

function json_decode_array(?string $json): array
{
    if (!$json) {
        return [];
    }
    $v = json_decode($json, true);
    return is_array($v) ? $v : [];
}

function json_enc(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function random_token(int $bytes = 32): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function app_key(): string
{
    $k = (string) config('app.key');
    if ($k === '') {
        throw new RuntimeException('APP_KEY não configurada no .env');
    }
    return $k;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [''];
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function first_name(string $name): string
{
    return explode(' ', trim($name))[0] ?: $name;
}

function plural(int $n, string $one, string $many): string
{
    return $n . ' ' . ($n === 1 ? $one : $many);
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(bool $cond): string
{
    return $cond ? ' checked' : '';
}

function nl2br_e(?string $text): string
{
    return nl2br(e($text), false);
}

/** Converte link do YouTube/Vimeo em URL de incorporação segura. */
function video_embed_url(?string $url): ?string
{
    if (!$url) {
        return null;
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{6,15})~', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return null;
}

function status_label(string $status): string
{
    return [
        'pending' => 'Pendente', 'active' => 'Ativo', 'paused' => 'Pausado', 'ended' => 'Encerrado', 'rejected' => 'Recusado',
    ][$status] ?? ucfirst($status);
}

function avatar(?array $u, string $cls = ''): string
{
    $name = (string) ($u['name'] ?? '?');
    $fid = isset($u['avatar_file_id']) && $u['avatar_file_id'] ? (int) $u['avatar_file_id'] : null;
    $inner = $fid ? '<img src="' . e(Files::url($fid)) . '" alt="" loading="lazy">' : e(initials($name));
    return '<span class="avatar ' . e($cls) . '" aria-hidden="true">' . $inner . '</span>';
}

function badge_status(string $status): string
{
    $tone = ['active' => 'ok', 'pending' => 'warn', 'paused' => '', 'ended' => 'outline', 'rejected' => 'danger'][$status] ?? '';
    return '<span class="badge ' . $tone . '">' . e(status_label($status)) . '</span>';
}

function weekday_names(array $days, bool $short = true): string
{
    $order = [1, 2, 3, 4, 5, 6, 0];
    $names = [];
    foreach ($order as $d) {
        if (in_array($d, $days, true)) $names[] = $short ? PT_WEEKDAYS_SHORT[$d] : PT_WEEKDAYS[$d];
    }
    return $names ? implode(', ', $names) : 'Sem dia fixo';
}

/** Resumo legível dos parâmetros prescritos de um exercício. */
function params_summary(array $params, array $fields): string
{
    $parts = [];
    foreach ($fields as $f) {
        if (!isset($params[$f]) || $params[$f] === '') continue;
        $meta = Modalities::FIELDS[$f] ?? ['label' => $f, 'unit' => ''];
        $val = e($params[$f]) . ($meta['unit'] && is_numeric($params[$f]) ? ' ' . e($meta['unit']) : '');
        $parts[] = '<span>' . e($meta['label']) . ': <b>' . $val . '</b></span>';
    }
    return implode('', $parts);
}
