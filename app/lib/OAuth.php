<?php
declare(strict_types=1);

/** Login com Google (OpenID Connect) e Apple (Sign in with Apple), sem bibliotecas externas. */
final class OAuth
{
    public static function googleEnabled(): bool
    {
        return config('google.client_id') !== '' && config('google.client_secret') !== '';
    }

    public static function appleEnabled(): bool
    {
        return config('apple.client_id') !== '' && config('apple.team_id') !== '' && config('apple.key_id') !== '' && is_file((string) config('apple.private_key_path'));
    }

    /** @param ?int $linkUserId  conta já logada que quer vincular o Google (null = entrar/cadastrar) */
    public static function googleRedirect(?int $linkUserId = null): string
    {
        $state = random_token(24);
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_nonce'] = random_token(16);
        if ($linkUserId) $_SESSION['oauth_link'] = $linkUserId; else unset($_SESSION['oauth_link']);
        return config('google.auth_url') . '?' . http_build_query([
            'client_id' => config('google.client_id'),
            'redirect_uri' => url('/auth/google/callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $_SESSION['oauth_nonce'],
            'prompt' => 'select_account',
        ]);
    }

    /** @return array{sub:string,email:string,verified:bool,name:string} */
    public static function googleCallback(string $code, string $state): array
    {
        if ($state === '' || !hash_equals((string) ($_SESSION['oauth_state'] ?? ''), $state)) {
            throw new UserError('Sessão de login expirada. Tente novamente.');
        }
        unset($_SESSION['oauth_state']);
        $r = Http::request('POST', (string) config('google.token_url'), ['Content-Type' => 'application/x-www-form-urlencoded'], [
            'code' => $code,
            'client_id' => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'redirect_uri' => url('/auth/google/callback'),
            'grant_type' => 'authorization_code',
        ]);
        $idToken = $r['json']['id_token'] ?? null;
        if (!$idToken) {
            throw new UserError('O Google não confirmou o login.');
        }
        // Token recebido diretamente do Google por TLS: as declarações podem ser lidas e validadas.
        $c = self::claims($idToken);
        if (!in_array($c['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
            || ($c['aud'] ?? '') !== config('google.client_id') || ($c['exp'] ?? 0) < Clock::time()
            || ($c['nonce'] ?? '') !== ($_SESSION['oauth_nonce'] ?? null)) {
            throw new UserError('Resposta do Google inválida.');
        }
        unset($_SESSION['oauth_nonce']);
        if (empty($c['sub'])) throw new UserError('Resposta do Google inválida.');
        return [
            'sub' => (string) $c['sub'],
            'email' => mb_strtolower((string) ($c['email'] ?? '')),
            'verified' => ($c['email_verified'] ?? false) === true || ($c['email_verified'] ?? '') === 'true',
            'name' => (string) ($c['name'] ?? ''),
        ];
    }

    public static function appleRedirect(): string
    {
        // O retorno da Apple é um POST entre sites: o estado vai em cookie próprio (SameSite=None).
        $state = random_token(24);
        $nonce = random_token(16);
        setcookie('wruch_apple', $state . '.' . $nonce, ['expires' => time() + 600, 'path' => '/auth/apple', 'secure' => true, 'httponly' => true, 'samesite' => 'None']);
        return 'https://appleid.apple.com/auth/authorize?' . http_build_query([
            'client_id' => config('apple.client_id'),
            'redirect_uri' => url('/auth/apple/callback'),
            'response_type' => 'code',
            'response_mode' => 'form_post',
            'scope' => 'name email',
            'state' => $state,
            'nonce' => hash('sha256', $nonce),
        ]);
    }

    public static function appleCallback(string $code, string $state, string $userJson): array
    {
        [$cState, $cNonce] = array_pad(explode('.', (string) ($_COOKIE['wruch_apple'] ?? ''), 2), 2, '');
        setcookie('wruch_apple', '', ['expires' => time() - 3600, 'path' => '/auth/apple', 'secure' => true, 'httponly' => true, 'samesite' => 'None']);
        if ($state === '' || $cState === '' || !hash_equals($cState, $state)) {
            throw new UserError('Sessão de login expirada. Tente novamente.');
        }
        $r = Http::request('POST', 'https://appleid.apple.com/auth/token', ['Content-Type' => 'application/x-www-form-urlencoded'], [
            'client_id' => config('apple.client_id'),
            'client_secret' => self::appleClientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => url('/auth/apple/callback'),
        ]);
        $idToken = $r['json']['id_token'] ?? null;
        if (!$idToken) {
            throw new UserError('A Apple não confirmou o login.');
        }
        $c = self::claims($idToken);
        if (($c['iss'] ?? '') !== 'https://appleid.apple.com' || ($c['aud'] ?? '') !== config('apple.client_id')
            || ($c['exp'] ?? 0) < Clock::time() || ($c['nonce'] ?? '') !== hash('sha256', $cNonce)) {
            throw new UserError('Resposta da Apple inválida.');
        }
        $u = json_decode($userJson, true) ?: [];
        $name = trim(($u['name']['firstName'] ?? '') . ' ' . ($u['name']['lastName'] ?? ''));
        return [
            'sub' => (string) $c['sub'],
            'email' => mb_strtolower((string) ($c['email'] ?? '')),
            'verified' => ($c['email_verified'] ?? false) === true || ($c['email_verified'] ?? '') === 'true',
            'name' => $name,
        ];
    }

    private static function claims(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new UserError('Token inválido.');
        }
        return json_decode(self::b64urlDecode($parts[1]), true) ?: [];
    }

    private static function b64urlDecode(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    private static function b64url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    /** client_secret da Apple: JWT ES256 assinado com a chave .p8. */
    public static function appleClientSecret(): string
    {
        $header = self::b64url(json_encode(['alg' => 'ES256', 'kid' => config('apple.key_id')]));
        $now = Clock::time();
        $payload = self::b64url(json_encode([
            'iss' => config('apple.team_id'), 'iat' => $now, 'exp' => $now + 3600,
            'aud' => 'https://appleid.apple.com', 'sub' => config('apple.client_id'),
        ]));
        $key = openssl_pkey_get_private((string) file_get_contents((string) config('apple.private_key_path')));
        if (!$key || !openssl_sign("{$header}.{$payload}", $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Não foi possível assinar o client_secret da Apple.');
        }
        return "{$header}.{$payload}." . self::b64url(self::derToRaw($der));
    }

    /** Converte assinatura ECDSA DER em R||S (64 bytes), formato exigido pelo JWT. */
    public static function derToRaw(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) $pos += ord($der[1]) & 0x7f;
        $pos++; // 0x02
        $rLen = ord($der[$pos++]);
        $r = substr($der, $pos, $rLen);
        $pos += $rLen + 1;
        $sLen = ord($der[$pos++]);
        $s = substr($der, $pos, $sLen);
        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
        return $r . $s;
    }
}
