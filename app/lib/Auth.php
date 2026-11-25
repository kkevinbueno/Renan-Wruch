<?php
declare(strict_types=1);

/**
 * Autenticação e papéis.
 * - student: aluno (cadastro público, depende de aprovação do Renan)
 * - trainer: Renan (definido por TRAINER_EMAILS no servidor)
 * - developer: equipe (DEVELOPER_EMAILS) — alterna entre interface de aluno e do Renan
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['uid'] ?? null;
            if ($id) {
                self::$user = DB::one('SELECT * FROM users WHERE id = ?', [(int) $id]);
                if (!self::$user || (int) ($_SESSION['auth_ver'] ?? 0) !== self::authVersion(self::$user)) {
                    // Senha alterada ou conta removida: encerra a sessão.
                    self::$user = null;
                    unset($_SESSION['uid']);
                } elseif (!self::$user['last_activity_at'] || strtotime(self::$user['last_activity_at'] . ' UTC') < Clock::time() - 300) {
                    DB::update('users', ['last_activity_at' => now()], 'id = ?', [self::$user['id']]);
                }
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function refresh(): void
    {
        self::$loaded = false;
        self::$user = null;
    }

    /** Muda quando a senha muda, invalidando sessões antigas. */
    private static function authVersion(array $u): int
    {
        return crc32((string) $u['password_hash'] . '|' . $u['id']);
    }

    public static function login(array $user, string $method): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['auth_ver'] = self::authVersion($user);
        $_SESSION['mode'] = $user['role'] === 'trainer' ? 'trainer' : 'student';
        unset($_SESSION['_csrf']);
        DB::update('users', ['last_login_at' => now(), 'last_activity_at' => now()], 'id = ?', [$user['id']]);
        self::refresh();
        Audit::log('auth.login', 'user', (int) $user['id'], ['method' => $method]);
    }

    public static function logout(): void
    {
        if (self::id()) {
            Audit::log('auth.logout', 'user', self::id());
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        }
        session_destroy();
        self::refresh();
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isDeveloper(): bool
    {
        return self::role() === 'developer';
    }

    public static function isTrainerAccount(): bool
    {
        return self::role() === 'trainer';
    }

    /** Interface ativa nesta sessão. */
    public static function mode(): string
    {
        $role = self::role();
        if ($role === 'trainer') return 'trainer';
        if ($role === 'developer') return ($_SESSION['mode'] ?? 'student') === 'trainer' ? 'trainer' : 'student';
        return 'student';
    }

    /** Pode operar as funcionalidades do Renan? (verificado no servidor em toda rota do painel) */
    public static function canTrainer(): bool
    {
        return self::role() === 'trainer' || (self::role() === 'developer' && self::mode() === 'trainer');
    }

    public static function inStudentInterface(): bool
    {
        $role = self::role();
        return $role === 'student' || ($role === 'developer' && self::mode() === 'student');
    }

    public static function switchMode(string $mode): void
    {
        if (!self::isDeveloper()) {
            abort(403, 'Somente contas autorizadas podem alternar a interface.');
        }
        $_SESSION['mode'] = $mode === 'trainer' ? 'trainer' : 'student';
        Audit::log('dev.switch_interface', 'user', self::id(), ['to' => $_SESSION['mode']]);
        if ($_SESSION['mode'] === 'student') {
            Students::ensureProfile((int) self::id());
        }
    }

    public static function home(): string
    {
        if (!self::user()) return '/entrar';
        return self::canTrainer() ? '/painel' : '/app';
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return $user['password_hash'] && password_verify($password, $user['password_hash']);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function createUser(string $name, string $email, ?string $password, bool $verified, array $extra = []): array
    {
        $email = mb_strtolower(trim($email));
        $id = DB::insert('users', array_merge([
            'email' => $email,
            'password_hash' => $password !== null ? self::hash($password) : null,
            'name' => mb_substr(trim($name) ?: explode('@', $email)[0], 0, 120),
            'role' => 'student',
            'status' => 'pending',
            'email_verified_at' => $verified ? now() : null,
            'consent_at' => now(),
            'consent_version' => config('app.consent_version'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));
        Students::ensureProfile($id);
        $user = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
        $user = self::syncRole($user);
        Audit::log('auth.register', 'user', $id, ['verified' => $verified], $id);
        if ($verified && $user['role'] === 'student') {
            Students::notifyTrainerNewSignup($user);
        }
        return $user;
    }

    /**
     * Aplica o papel definido no servidor. Só eleva permissões de e-mails CONFIRMADOS,
     * impedindo que alguém se cadastre com o e-mail do Renan sem acesso à caixa de entrada.
     */
    public static function syncRole(array $user): array
    {
        $email = mb_strtolower($user['email']);
        $target = 'student';
        if ($user['email_verified_at']) {
            if (in_array($email, config('roles.trainer_emails'), true)) $target = 'trainer';
            elseif (in_array($email, config('roles.developer_emails'), true)) $target = 'developer';
        }
        if ($target !== $user['role']) {
            $data = ['role' => $target, 'updated_at' => now()];
            if ($target !== 'student') {
                $data['status'] = 'active';
            } elseif (in_array($user['role'], ['trainer', 'developer'], true)) {
                // Removido da configuração: perde acesso especial e fica encerrado.
                $data['status'] = 'ended';
            }
            DB::update('users', $data, 'id = ?', [$user['id']]);
            Audit::log('auth.role_sync', 'user', (int) $user['id'], ['from' => $user['role'], 'to' => $target], (int) $user['id']);
            $user = array_merge($user, $data);
        }
        return $user;
    }

    /** Vincula o login social a uma conta já autenticada (sem trocar papel nem e-mail). */
    public static function linkProvider(int $userId, string $provider, string $sub): void
    {
        $col = $provider === 'google' ? 'google_sub' : 'apple_sub';
        $owner = DB::val("SELECT id FROM users WHERE {$col} = ?", [$sub]);
        if ($owner && (int) $owner !== $userId) {
            throw new UserError('Esta conta Google já está ligada a outro cadastro.');
        }
        DB::update('users', [$col => $sub, 'updated_at' => now()], 'id = ?', [$userId]);
        Audit::log('auth.oauth_link', 'user', $userId, ['provider' => $provider], $userId);
    }

    /** Login social: vincula por identificador do provedor ou por e-mail verificado. */
    public static function oauthLogin(string $provider, string $sub, string $email, bool $emailVerified, string $name): array
    {
        $col = $provider === 'google' ? 'google_sub' : 'apple_sub';
        $user = DB::one("SELECT * FROM users WHERE {$col} = ?", [$sub]);
        if (!$user) {
            if (!$emailVerified || $email === '') {
                throw new RuntimeException('O provedor não confirmou o e-mail desta conta.');
            }
            $user = self::findByEmail($email);
            if ($user) {
                $upd = [$col => $sub, 'updated_at' => now()];
                if (!$user['email_verified_at']) {
                    $upd['email_verified_at'] = now();
                }
                DB::update('users', $upd, 'id = ?', [$user['id']]);
                Audit::log('auth.oauth_link', 'user', (int) $user['id'], ['provider' => $provider, 'by' => 'verified_email'], (int) $user['id']);
                $wasUnverified = !$user['email_verified_at'];
                $user = array_merge($user, $upd);
                if ($wasUnverified && $user['role'] === 'student') {
                    Students::notifyTrainerNewSignup($user);
                }
            } else {
                // Consentimento LGPD é coletado na primeira entrada (tela /consentimento).
                $user = self::createUser($name, $email, null, true, [$col => $sub, 'consent_at' => null, 'consent_version' => null]);
            }
        }
        return self::syncRole($user);
    }
}
