<?php
declare(strict_types=1);

/**
 * Serviço centralizado de notificações por e-mail:
 * fila persistente, deduplicação por chave, novas tentativas com espera crescente e registro de falhas.
 * Os e-mails nunca contêm fotografias, respostas de saúde ou o conteúdo das mensagens do chat.
 */
final class Notify
{
    /** Preferências não essenciais que o usuário pode desligar. */
    public const PREFS = [
        'workout_published' => 'Novo treino publicado',
        'calendar_changes' => 'Alterações no calendário',
        'self_eval_reminders' => 'Lembretes de autoavaliação',
        'assessment_reminders' => 'Lembretes de avaliação física',
        'activity_reminders' => 'Lembretes de atividades agendadas',
        'message_digest' => 'Aviso de mensagens não lidas',
        'payment_reminders' => 'Lembretes de pagamento',
    ];

    private static bool $flushRegistered = false;

    public static function prefEnabled(int $userId, string $key): bool
    {
        $v = DB::val('SELECT enabled FROM notification_prefs WHERE user_id = ? AND pref_key = ?', [$userId, $key]);
        return $v === null ? true : (bool) $v;
    }

    /**
     * Enfileira um e-mail.
     * @param array|int $to   usuário (array/id) ou ['email' => ..., 'name' => ...]
     * @param array $opts      dedupe, delay (segundos), pref, immediate
     */
    public static function send(array|int $to, string $template, array $vars = [], array $opts = []): ?int
    {
        if (is_int($to)) {
            $to = DB::one('SELECT * FROM users WHERE id = ?', [$to]);
            if (!$to) return null;
        }
        $userId = isset($to['id']) ? (int) $to['id'] : null;
        if ($userId && isset($opts['pref']) && !self::prefEnabled($userId, $opts['pref'])) {
            return null;
        }
        $email = (string) ($to['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $dedupe = $opts['dedupe'] ?? null;
        if ($dedupe && DB::val('SELECT id FROM email_queue WHERE dedupe_key = ?', [$dedupe])) {
            return null;
        }
        [$subject, $html, $text] = EmailTemplates::render($template, $vars + ['name' => first_name((string) ($to['name'] ?? ''))]);
        try {
            $id = DB::insert('email_queue', [
                'user_id' => $userId,
                'to_email' => $email,
                'template' => $template,
                'subject' => $subject,
                'html_body' => $html,
                'text_body' => $text,
                'dedupe_key' => $dedupe,
                'status' => 'queued',
                'attempts' => 0,
                'send_after' => gmdate('Y-m-d H:i:s', Clock::time() + (int) ($opts['delay'] ?? 0)),
                'created_at' => now(),
            ]);
        } catch (PDOException $e) {
            if (DB::isUniqueViolation($e)) return null;
            throw $e;
        }
        if (empty($opts['delay']) && PHP_SAPI !== 'cli') {
            self::flushAtShutdown();
        }
        return $id;
    }

    public static function toTrainer(string $template, array $vars = [], array $opts = []): void
    {
        $trainer = DB::one("SELECT * FROM users WHERE role = 'trainer' ORDER BY id LIMIT 1");
        $to = $trainer ?: ['email' => config('mail.trainer_notify'), 'name' => Settings::get('trainer_name')];
        self::send($to, $template, $vars, $opts);
    }

    /** Envia os e-mails imediatos depois que a resposta já foi entregue ao navegador. */
    private static function flushAtShutdown(): void
    {
        if (self::$flushRegistered) return;
        self::$flushRegistered = true;
        register_shutdown_function(function () {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            try {
                self::process(5);
            } catch (Throwable $e) {
                Log::error('Falha ao processar fila de e-mail no encerramento', ['e' => $e->getMessage()]);
            }
        });
    }

    /** Processa a fila. Retorna [enviados, falhas]. */
    public static function process(int $limit = 50): array
    {
        $sent = 0;
        $failed = 0;
        $rows = DB::all("SELECT * FROM email_queue WHERE status = 'queued' AND send_after <= ? ORDER BY id LIMIT " . (int) $limit, [now()]);
        foreach ($rows as $row) {
            // Reserva a linha para evitar envio duplicado por execuções simultâneas do cron.
            $claimed = DB::update('email_queue', ['status' => 'sending'], "id = ? AND status = 'queued'", [$row['id']]);
            if (!$claimed) continue;
            if ($row['template'] === 'chat_unread' && !self::chatStillUnread($row)) {
                DB::update('email_queue', ['status' => 'skipped', 'sent_at' => now()], 'id = ?', [$row['id']]);
                continue;
            }
            try {
                Mailer::send($row['to_email'], '', $row['subject'], $row['html_body'], $row['text_body']);
                DB::update('email_queue', ['status' => 'sent', 'sent_at' => now(), 'attempts' => $row['attempts'] + 1], 'id = ?', [$row['id']]);
                $sent++;
            } catch (Throwable $e) {
                $attempts = (int) $row['attempts'] + 1;
                $final = $attempts >= 5;
                DB::update('email_queue', [
                    'status' => $final ? 'failed' : 'queued',
                    'attempts' => $attempts,
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                    'send_after' => gmdate('Y-m-d H:i:s', Clock::time() + 60 * (2 ** $attempts)),
                ], 'id = ?', [$row['id']]);
                Log::warning('Falha no envio de e-mail', ['id' => $row['id'], 'tentativa' => $attempts, 'erro' => $e->getMessage()]);
                $failed++;
            }
        }
        // Libera linhas presas em "sending" por processos interrompidos.
        DB::run("UPDATE email_queue SET status = 'queued' WHERE status = 'sending' AND send_after < ?", [gmdate('Y-m-d H:i:s', Clock::time() - 900)]);
        return [$sent, $failed];
    }

    private static function chatStillUnread(array $row): bool
    {
        // dedupe: chat:{recipientId}:{studentId}:{bloco}
        $parts = explode(':', (string) $row['dedupe_key']);
        if (count($parts) < 3) return true;
        $recipient = (int) $parts[1];
        $student = (int) $parts[2];
        return (bool) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND read_at IS NULL AND (sender_id IS NULL OR sender_id <> ?)', [$student, $recipient]);
    }

    /** Agrupa avisos de mensagem: no máximo um e-mail por conversa a cada janela configurada. */
    public static function chatDigest(int $recipientId, int $studentId): void
    {
        $minutes = max(5, Settings::int('chat_digest_minutes'));
        $block = intdiv(Clock::time(), $minutes * 60);
        $recipient = DB::one('SELECT * FROM users WHERE id = ?', [$recipientId]);
        if (!$recipient) return;
        $isTrainerSide = $recipient['role'] !== 'student' && $recipientId !== $studentId;
        $student = DB::one('SELECT name FROM users WHERE id = ?', [$studentId]);
        self::send($recipient, 'chat_unread', [
            'from' => $isTrainerSide ? ($student['name'] ?? 'Aluno') : Settings::get('trainer_name'),
            'link' => url($isTrainerSide ? "/painel/mensagens/{$studentId}" : '/app/mensagens'),
        ], ['dedupe' => "chat:{$recipientId}:{$studentId}:{$block}", 'delay' => $minutes * 60, 'pref' => 'message_digest']);
    }
}
