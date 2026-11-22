<?php
declare(strict_types=1);

/** Relógio da aplicação. Em testes, WRUCH_FAKE_TIME (timestamp) permite simular datas. */
final class Clock
{
    private static ?int $fixed = null;

    public static function time(): int
    {
        if (self::$fixed !== null) {
            return self::$fixed;
        }
        $fake = getenv('WRUCH_FAKE_TIME');
        if ($fake !== false && $fake !== '' && config('app.env') === 'testing') {
            return (int) $fake;
        }
        return time();
    }

    public static function set(?int $ts): void
    {
        self::$fixed = $ts;
    }
}
