<?php
declare(strict_types=1);

/** Verificações de acesso executadas no servidor antes de cada rota. */
final class Middleware
{
    public static function run(string $name): void
    {
        match ($name) {
            'guest' => self::guest(),
            'auth' => self::auth(),
            'trainer' => self::trainer(),
            'student' => self::student(),
            'onboarding' => self::onboarding(),
            'app' => self::app(),
            'chat' => self::chat(),
            'developer' => self::developer(),
            default => throw new InvalidArgumentException("Middleware desconhecido: {$name}"),
        };
    }

    private static function guest(): void
    {
        if (Auth::user()) {
            redirect(Auth::home());
        }
    }

    private static function auth(): void
    {
        $u = Auth::user();
        if (!$u) {
            if (is_json_request()) json_out(['ok' => false, 'error' => 'Faça login novamente.'], 401);
            $_SESSION['next'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/entrar');
        }
        if (!$u['email_verified_at']) {
            if (is_json_request()) json_out(['ok' => false, 'error' => 'Confirme seu e-mail.'], 403);
            redirect('/confirmar-email');
        }
    }

    private static function developer(): void
    {
        self::auth();
        if (!Auth::isDeveloper()) abort(403);
    }

    private static function trainer(): void
    {
        self::auth();
        if (!Auth::canTrainer()) {
            if (Auth::inStudentInterface() && !is_json_request()) {
                redirect('/app');
            }
            abort(403, 'Acesso restrito à interface do treinador.');
        }
    }

    private static function student(): void
    {
        self::auth();
        if (!Auth::inStudentInterface()) {
            if (Auth::isDeveloper()) {
                flash('info', 'Você está na interface do Renan. Use "Alternar interface" para ver a área do aluno.');
            }
            if (is_json_request()) abort(403);
            redirect('/painel');
        }
        if (!Auth::user()['consent_at'] && !Auth::isDeveloper()) {
            if (is_json_request()) abort(403, 'Consentimento pendente.');
            redirect('/consentimento');
        }
        $status = Auth::user()['status'];
        if (in_array($status, ['pending', 'rejected'], true) && !Auth::isDeveloper()) {
            if (is_json_request()) abort(403, 'Cadastro aguardando aprovação.');
            redirect('/aguardando');
        }
    }

    private static function onboarding(): void
    {
        self::student();
        if (!Auth::isDeveloper() && Auth::user()['status'] !== 'active') {
            if (is_json_request()) abort(403, 'Acompanhamento não está ativo.');
            redirect('/app/situacao');
        }
    }

    private static function app(): void
    {
        self::onboarding();
        $profile = Students::profile((int) Auth::id());
        if (!$profile['onboarding_completed_at']) {
            if (is_json_request()) abort(403, 'Conclua a avaliação inicial.');
            redirect('/app/avaliacao-inicial');
        }
        if (!Billing::hasAccess((int) Auth::id())) {
            if (is_json_request()) abort(402, 'Assinatura pendente.');
            redirect('/app/situacao');
        }
    }

    private static function chat(): void
    {
        self::student();
        $u = Auth::user();
        if (!Auth::isDeveloper() && !in_array($u['status'], ['active', 'paused'], true)) {
            if (is_json_request()) abort(403);
            redirect('/app/situacao');
        }
        $profile = Students::profile((int) $u['id']);
        if (!$profile['onboarding_completed_at'] && !Auth::isDeveloper()) {
            if (is_json_request()) abort(403, 'Conclua a avaliação inicial.');
            redirect('/app/avaliacao-inicial');
        }
    }
}
