<?php
declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = random_token(32);
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(): void
    {
        $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $real = (string) ($_SESSION['_csrf'] ?? '');
        if ($real === '' || $sent === '' || !hash_equals($real, $sent)) {
            if (is_json_request()) {
                json_out(['ok' => false, 'error' => 'Sessão expirada. Recarregue a página.'], 419);
            }
            http_response_code(419);
            flash('error', 'Sua sessão expirou. Tente novamente.');
            back('/');
        }
    }
}
