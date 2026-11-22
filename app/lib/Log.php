<?php
declare(strict_types=1);

final class Log
{
    public static function write(string $level, string $message, array $context = []): void
    {
        $line = sprintf("[%s] %s: %s %s\n", gmdate('Y-m-d H:i:s'), strtoupper($level), $message, $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '');
        $dir = storage_path('logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents($dir . '/app-' . gmdate('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $m, array $c = []): void { self::write('info', $m, $c); }
    public static function warning(string $m, array $c = []): void { self::write('warning', $m, $c); }
    public static function error(string $m, array $c = []): void { self::write('error', $m, $c); }
}
