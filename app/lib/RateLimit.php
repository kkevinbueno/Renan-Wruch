<?php
declare(strict_types=1);

/** Limitador de requisições por janela fixa, persistido no banco (funciona em hospedagem compartilhada). */
final class RateLimit
{
    /** Registra uma tentativa; retorna false se o limite foi excedido. */
    public static function hit(string $bucket, int $max, int $windowSeconds): bool
    {
        $bucket = substr(hash('sha256', $bucket), 0, 64);
        $t = Clock::time();
        $row = DB::one('SELECT hits, window_start FROM rate_limits WHERE bucket = ?', [$bucket]);
        if (!$row || $t - (int) $row['window_start'] >= $windowSeconds) {
            if ($row) {
                DB::update('rate_limits', ['hits' => 1, 'window_start' => $t], 'bucket = ?', [$bucket]);
            } else {
                try {
                    DB::insert('rate_limits', ['bucket' => $bucket, 'hits' => 1, 'window_start' => $t]);
                } catch (PDOException $e) {
                    if (!DB::isUniqueViolation($e)) throw $e;
                    DB::run('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?', [$bucket]);
                }
            }
            return true;
        }
        if ((int) $row['hits'] >= $max) {
            return false;
        }
        DB::run('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?', [$bucket]);
        return true;
    }

    public static function clear(string $bucket): void
    {
        DB::delete('rate_limits', 'bucket = ?', [substr(hash('sha256', $bucket), 0, 64)]);
    }

    public static function purge(): void
    {
        DB::delete('rate_limits', 'window_start < ?', [Clock::time() - 86400]);
    }
}
