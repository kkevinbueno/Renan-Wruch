<?php
declare(strict_types=1);

final class AuthController
{
    public function loginForm(): void
    {
        View::render('auth/login', ['title' => 'Entrar'], 'public');
    }

    public function login(): void
    {
        $email = mb_strtolower((string) input('email', ''));
        $password = (string) ($_POST['password'] ?? '');
        remember_old(['email' => $email]);
        if (!RateLimit::hit('login-ip:' . request_ip(), 30, 900) || !RateLimit::hit('login-email:' . $email, 8, 900)) {
            flash('error', 'Muitas tentativas. Aguarde 15 minutos e tente novamente.');
            redirect('/entrar');
        }
        $user = Auth::findByEmail($email);
        if (!$user || !Auth::verifyPassword($user, $password)) {
            // Mensagem genérica: não revela se o e-mail existe.
            flash('error', 'E-mail ou senha incorretos.');
            redirect('/entrar');
        }
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password_hash' => Auth::hash($password)], 'id = ?', [$user['id']]);
            $user = Auth::findByEmail($email);
        }
        RateLimit::clear('login-email:' . $email);
        $user = Auth::syncRole($user);
        Auth::login($user, 'password');
        $next = $_SESSION['next'] ?? null;
        unset($_SESSION['next']);
        if (!$user['email_verified_at']) {
            redirect('/confirmar-email');
        }
        redirect($next && str_starts_with($next, '/') && !str_starts_with($next, '//') ? $next : Auth::home());
    }

    public function registerForm(): void
    {
        View::render('auth/register', ['title' => 'Criar conta'], 'public');
    }

    public function register(): void
    {
        $name = (string) input('name', '');
        $email = mb_strtolower((string) input('email', ''));
        $password = (string) ($_POST['password'] ?? '');
        remember_old(['name' => $name, 'email' => $email]);
        $errors = [];
        if (mb_strlen($name) < 3) $errors[] = 'Informe seu nome completo.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
        if (mb_strlen($password) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        if ($password !== (string) ($_POST['password_confirm'] ?? '')) $errors[] = 'As senhas não conferem.';
        if (empty($_POST['consent_terms'])) $errors[] = 'É necessário aceitar os Termos de Uso e a Política de Privacidade.';
        if (empty($_POST['consent_health'])) $errors[] = 'É necessário consentir com o tratamento de dados de saúde e fotografias para o acompanhamento.';
        if ($errors) {
            foreach ($errors as $err) flash('error', $err);
            redirect('/cadastro');
        }
        if (!RateLimit::hit('register-ip:' . request_ip(), 10, 3600)) {
            flash('error', 'Muitos cadastros a partir desta rede. Tente mais tarde.');
            redirect('/cadastro');
        }
        $existing = Auth::findByEmail($email);
        if ($existing) {
            // Não revela a existência da conta; reenvia a confirmação se ainda não confirmada.
            if (!$existing['email_verified_at']) {
                $this->sendVerification($existing);
            }
            flash('success', 'Se o e-mail puder ser usado, você receberá um link de confirmação em instantes.');
            redirect('/entrar');
        }
        $user = Auth::createUser($name, $email, $password, false);
        $this->sendVerification($user);
        Auth::login($user, 'register');
        redirect('/confirmar-email');
    }

    private function sendVerification(array $user): void
    {
        if (!RateLimit::hit('verify-mail:' . $user['id'], 5, 3600)) return;
        $token = Tokens::create((int) $user['id'], 'verify_email', 172800);
        Notify::send($user, 'verify_email', ['link' => url('/verificar-email', ['t' => $token])]);
    }

    public function verifyNotice(): void
    {
        $u = Auth::user();
        if (!$u) redirect('/entrar');
        if ($u['email_verified_at']) redirect(Auth::home());
        View::render('auth/verify_notice', ['title' => 'Confirme seu e-mail', 'email' => $u['email']], 'public');
    }

    public function resendVerification(): void
    {
        $u = Auth::user();
        if (!$u) redirect('/entrar');
        if (!$u['email_verified_at']) {
            $this->sendVerification($u);
        }
        flash('success', 'Enviamos um novo link de confirmação.');
        redirect('/confirmar-email');
    }

    public function verify(): void
    {
        $uid = Tokens::consume((string) input('t', ''), 'verify_email');
        if (!$uid) {
            flash('error', 'Link de confirmação inválido ou expirado. Faça login para receber outro.');
            redirect('/entrar');
        }
        $user = DB::one('SELECT * FROM users WHERE id = ?', [$uid]);
        if (!$user['email_verified_at']) {
            DB::update('users', ['email_verified_at' => now(), 'updated_at' => now()], 'id = ?', [$uid]);
            $user['email_verified_at'] = now();
            Audit::log('auth.email_verified', 'user', $uid, [], $uid);
            $user = Auth::syncRole($user);
            if ($user['role'] === 'student') {
                Students::notifyTrainerNewSignup($user);
            }
        }
        if (Auth::id() !== $uid) {
            Auth::login($user, 'email_link');
        } else {
            Auth::refresh();
        }
        flash('success', 'E-mail confirmado!');
        redirect(Auth::home());
    }

    public function forgotForm(): void
    {
        View::render('auth/forgot', ['title' => 'Recuperar senha'], 'public');
    }

    public function forgot(): void
    {
        $email = mb_strtolower((string) input('email', ''));
        if (RateLimit::hit('forgot-ip:' . request_ip(), 10, 3600) && RateLimit::hit('forgot:' . $email, 3, 3600)) {
            $user = Auth::findByEmail($email);
            if ($user) {
                $token = Tokens::create((int) $user['id'], 'password_reset', 3600);
                Notify::send($user, 'password_reset', ['link' => url('/redefinir-senha', ['t' => $token])]);
                Audit::log('auth.password_reset_requested', 'user', (int) $user['id'], [], (int) $user['id']);
            }
        }
        flash('success', 'Se houver uma conta com este e-mail, enviaremos um link para criar uma nova senha.');
        redirect('/entrar');
    }

    public function resetForm(): void
    {
        $t = (string) input('t', '');
        if (!Tokens::peek($t, 'password_reset')) {
            flash('error', 'Link inválido ou expirado. Solicite um novo.');
            redirect('/esqueci-senha');
        }
        View::render('auth/reset', ['title' => 'Nova senha', 'token' => $t], 'public');
    }

    public function reset(): void
    {
        $t = (string) input('t', '');
        $p = (string) ($_POST['password'] ?? '');
        if (mb_strlen($p) < 8 || $p !== (string) ($_POST['password_confirm'] ?? '')) {
            flash('error', 'A senha deve ter 8+ caracteres e as duas devem ser iguais.');
            redirect('/redefinir-senha?t=' . urlencode($t));
        }
        $uid = Tokens::consume($t, 'password_reset');
        if (!$uid) {
            flash('error', 'Link inválido ou expirado. Solicite um novo.');
            redirect('/esqueci-senha');
        }
        $upd = ['password_hash' => Auth::hash($p), 'updated_at' => now()];
        $user = DB::one('SELECT * FROM users WHERE id = ?', [$uid]);
        if (!$user['email_verified_at']) {
            $upd['email_verified_at'] = now(); // O link chegou ao e-mail: ele foi confirmado.
        }
        DB::update('users', $upd, 'id = ?', [$uid]);
        Audit::log('auth.password_reset', 'user', $uid, [], $uid);
        Notify::send($uid, 'password_changed');
        flash('success', 'Senha alterada. Entre com a nova senha.');
        redirect('/entrar');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        flash('success', 'Você saiu da sua conta.');
        redirect('/entrar');
    }

    public function google(): void
    {
        if (!OAuth::googleEnabled()) {
            flash('error', 'Login com Google ainda não está disponível.');
            redirect('/entrar');
        }
        redirect(OAuth::googleRedirect());
    }

    public function googleCallback(): void
    {
        if (input('error')) {
            flash('error', 'Login com Google cancelado.');
            redirect('/entrar');
        }
        try {
            $info = OAuth::googleCallback((string) input('code', ''), (string) input('state', ''));
            $this->finishOAuth('google', $info);
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect('/entrar');
        } catch (RuntimeException $e) {
            Log::error('Google OAuth', ['e' => $e->getMessage()]);
            flash('error', 'Não foi possível entrar com o Google.');
            redirect('/entrar');
        }
    }

    public function apple(): void
    {
        if (!OAuth::appleEnabled()) {
            flash('error', 'Login com Apple ainda não está disponível.');
            redirect('/entrar');
        }
        redirect(OAuth::appleRedirect());
    }

    public function appleCallback(): void
    {
        if (!empty($_POST['error'])) {
            flash('error', 'Login com Apple cancelado.');
            redirect('/entrar');
        }
        try {
            $info = OAuth::appleCallback((string) ($_POST['code'] ?? ''), (string) ($_POST['state'] ?? ''), (string) ($_POST['user'] ?? ''));
            $this->finishOAuth('apple', $info);
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect('/entrar');
        } catch (RuntimeException $e) {
            Log::error('Apple OAuth', ['e' => $e->getMessage()]);
            flash('error', 'Não foi possível entrar com a Apple.');
            redirect('/entrar');
        }
    }

    private function finishOAuth(string $provider, array $info): void
    {
        $user = Auth::oauthLogin($provider, $info['sub'], $info['email'], $info['verified'], $info['name']);
        Auth::login($user, $provider);
        redirect(Auth::home());
    }

    public function pending(): void
    {
        $u = Auth::user();
        if (!in_array($u['status'], ['pending', 'rejected'], true) || $u['role'] !== 'student') {
            redirect(Auth::home());
        }
        View::render('auth/pending', ['title' => 'Cadastro em análise', 'user' => $u], 'public');
    }

    public function consentForm(): void
    {
        $u = Auth::user();
        if ($u['consent_at']) redirect(Auth::home());
        View::render('auth/consent', ['title' => 'Consentimento'], 'public');
    }

    public function consentSave(): void
    {
        if (empty($_POST['consent_terms']) || empty($_POST['consent_health'])) {
            flash('error', 'Para usar a plataforma é necessário aceitar os termos e o tratamento de dados de saúde e fotografias.');
            redirect('/consentimento');
        }
        DB::update('users', ['consent_at' => now(), 'consent_version' => config('app.consent_version'), 'updated_at' => now()], 'id = ?', [Auth::id()]);
        Audit::log('lgpd.consent', 'user', Auth::id(), ['version' => config('app.consent_version')]);
        redirect(Auth::home());
    }

    public function switchInterface(): void
    {
        $to = input('to') === 'trainer' ? 'trainer' : 'student';
        Auth::switchMode($to);
        redirect($to === 'trainer' ? '/painel' : '/app');
    }
}
