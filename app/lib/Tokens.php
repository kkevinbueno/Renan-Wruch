<?php
declare(strict_types=1);

/** Tokens de uso único (confirmação de e-mail, redefinição de senha). Somente o hash é armazenado. */
final class Tokens
{
    public static function create(int $userId, string $purpose, int $ttlSeconds): string
    {
        DB::run('UPDATE tokens SET used_at = ? WHERE user_id = ? AND purpose = ? AND used_at IS NULL', [now(), $userId, $purpose]);
        $raw = random_token(32);
        DB::insert('tokens', [
            'user_id' => $userId,
            'purpose' => $purpose,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => gmdate('Y-m-d H:i:s', Clock::time() + $ttlSeconds),
            'created_at' => now(),
        ]);
        return $raw;
    }

    /** Retorna o id do usuário se o token for válido, marcando-o como usado. */
    public static function consume(string $raw, string $purpose): ?int
    {
        if ($raw === '') return null;
        $row = DB::one('SELECT * FROM tokens WHERE token_hash = ? AND purpose = ?', [hash('sha256', $raw), $purpose]);
        if (!$row || $row['used_at'] || $row['expires_at'] < now()) {
            return null;
        }
        DB::update('tokens', ['used_at' => now()], 'id = ?', [$row['id']]);
        return (int) $row['user_id'];
    }

    public static function peek(string $raw, string $purpose): ?int
    {
        $row = DB::one('SELECT * FROM tokens WHERE token_hash = ? AND purpose = ?', [hash('sha256', $raw), $purpose]);
        return ($row && !$row['used_at'] && $row['expires_at'] >= now()) ? (int) $row['user_id'] : null;
    }

    public static function purge(): void
    {
        DB::delete('tokens', 'expires_at < ?', [gmdate('Y-m-d H:i:s', Clock::time() - 86400 * 7)]);
    }
}
