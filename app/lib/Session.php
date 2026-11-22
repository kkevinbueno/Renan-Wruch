<?php
declare(strict_types=1);

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $dir = storage_path('sessions');
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        // Pasta própria: em hospedagem compartilhada o /tmp padrão pode ser visível a outras contas.
        session_save_path($dir);
        session_name('wruch_sid');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) (86400 * 30));
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '200');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        session_set_cookie_params([
            'lifetime' => 86400 * 30,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        // "old input" vive por uma única requisição.
        $_SESSION['_old_view'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    }
}
