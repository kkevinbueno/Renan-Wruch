<?php
declare(strict_types=1);

/** Tarefas agendadas. Rode a cada 5–15 minutos: php cron/run.php */
final class Cron
{
    public static function run(): array
    {
        $out = [];
        $lockFile = storage_path('cache/cron.lock');
        if (!is_dir(dirname($lockFile))) mkdir(dirname($lockFile), 0750, true);
        $lock = fopen($lockFile, 'c');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            return ['Outra execução do cron está em andamento.'];
        }
        $jobs = [
            'publicações agendadas' => fn() => Workouts::publishDue(),
            'lembretes de autoavaliação' => fn() => self::selfEvalReminders(),
            'lembretes de avaliação física' => fn() => self::assessmentReminders(),
            'lembretes de atividades' => fn() => self::activityReminders(),
            'lembretes de pagamento' => fn() => self::paymentReminders(),
            'reconciliação de pagamentos' => fn() => self::every('reconcile', 3600, fn() => Billing::reconcileAll()),
            'resumo diário do Renan' => fn() => self::trainerDigest(),
            'manutenção' => fn() => self::every('maintenance', 86400, fn() => self::maintenance()),
            'fila de e-mails' => fn() => implode(' enviados / ', Notify::process(100)) . ' falhas',
        ];
        foreach ($jobs as $name => $job) {
            try {
                $out[] = "{$name}: " . $job();
            } catch (Throwable $e) {
                $out[] = "{$name}: ERRO " . $e->getMessage();
                Log::error("Cron {$name}", ['e' => $e->getMessage()]);
            }
        }
        Settings::set('cron_last_run', now());
        flock($lock, LOCK_UN);
        return $out;
    }

    private static function every(string $key, int $seconds, callable $fn): string
    {
        $last = (string) Settings::get('cron_last_' . $key, '');
        if ($last !== '' && strtotime($last . ' UTC') > Clock::time() - $seconds) {
            return 'aguardando intervalo';
        }
        Settings::set('cron_last_' . $key, now());
        return (string) $fn();
    }

    private static function activeStudents(): array
    {
        return DB::all("SELECT u.*, p.self_eval_every_days, p.self_eval_reminder_time, p.next_assessment_on, p.assessment_remind_days_before,
                p.assessment_reminders_count, p.assessment_overdue_alert, p.cycle_started_on, p.onboarding_completed_at
            FROM users u JOIN student_profiles p ON p.user_id = u.id
            WHERE u.role = 'student' AND u.status = 'active' AND p.onboarding_completed_at IS NOT NULL");
    }

    /** Próxima data em que a autoavaliação é devida. */
    public static function selfEvalDueDate(array $s): string
    {
        $last = DB::val('SELECT MAX(ref_date) FROM self_assessments WHERE student_id = ?', [$s['id']]);
        $every = max(1, (int) $s['self_eval_every_days']);
        if ($last) return date_add_days((string) $last, $every);
        $start = $s['onboarding_completed_at'] ? to_local($s['onboarding_completed_at'])->format('Y-m-d') : today();
        return date_add_days($start, $every);
    }

    private static function selfEvalReminders(): int
    {
        $n = 0;
        $nowLocal = local_now();
        foreach (self::activeStudents() as $s) {
            $due = self::selfEvalDueDate($s);
            if ($due > today()) continue;
            $time = $s['self_eval_reminder_time'] ?: '08:00';
            if ($nowLocal->format('H:i') < $time) continue;
            $round = days_between($due, today()) >= 2 ? 2 : 1;
            if (Notify::send($s, 'self_eval_reminder', [], ['dedupe' => "selfeval:{$s['id']}:{$due}:{$round}", 'pref' => 'self_eval_reminders'])) $n++;
        }
        return $n;
    }

    /** Dias (antes da data) em que os lembretes de avaliação física devem sair. */
    public static function reminderOffsets(int $before, int $count): array
    {
        $before = max(0, $before);
        $count = max(1, $count);
        if ($count === 1 || $before === 0) return [$before];
        $offs = [];
        for ($i = 0; $i < $count; $i++) {
            $offs[] = (int) round($before - $i * $before / ($count - 1));
        }
        return array_values(array_unique($offs));
    }

    private static function assessmentReminders(): int
    {
        $n = 0;
        $today = today();
        foreach (self::activeStudents() as $s) {
            $d = $s['next_assessment_on'];
            if (!$d) continue;
            $done = DB::val("SELECT COUNT(*) FROM physical_assessments WHERE student_id = ? AND due_on = ? AND status IN ('submitted','reviewed')", [$s['id'], $d]);
            if ($done) continue;
            foreach (self::reminderOffsets((int) $s['assessment_remind_days_before'], (int) $s['assessment_reminders_count']) as $i => $off) {
                $day = date_add_days($d, -$off);
                if ($today === $day || $today === date_add_days($day, 1) && $day < $d) {
                    $when = $off === 0 ? 'hoje' : ($off === 1 ? 'amanhã' : "em {$off} dias");
                    if (Notify::send($s, 'assessment_reminder', ['date' => fmt_date($d), 'when' => $when], ['dedupe' => "assess:{$s['id']}:{$d}:{$i}", 'pref' => 'assessment_reminders'])) $n++;
                }
            }
            if ($today > $d) {
                $late = days_between($d, $today);
                foreach ([1, 3, 7] as $k) {
                    if ($late >= $k && $late < $k + 2) {
                        if (Notify::send($s, 'assessment_overdue', ['date' => fmt_date($d)], ['dedupe' => "assesslate:{$s['id']}:{$d}:{$k}", 'pref' => 'assessment_reminders'])) $n++;
                    }
                }
                if ($s['assessment_overdue_alert'] && $late >= 1) {
                    Notify::toTrainer('assessment_overdue_trainer', ['student' => $s['name'], 'date' => fmt_date($d), 'link' => url('/painel/alunos/' . $s['id'])], ['dedupe' => "assesslatet:{$s['id']}:{$d}"]);
                }
            }
        }
        return $n;
    }

    private static function activityReminders(): int
    {
        $n = 0;
        if ((int) local_now()->format('H') < 9) return 0;
        $tomorrow = date_add_days(today(), 1);
        $rows = DB::all("SELECT e.*, u.email, u.name, u.status FROM calendar_events e JOIN users u ON u.id = e.student_id
            WHERE e.event_date = ? AND e.type IN ('activity','appointment','assessment') AND u.status = 'active'", [$tomorrow]);
        foreach ($rows as $e) {
            $user = ['id' => $e['student_id'], 'email' => $e['email'], 'name' => $e['name']];
            $when = 'amanhã' . ($e['start_time'] ? ' às ' . $e['start_time'] : '');
            if (Notify::send($user, 'activity_reminder', ['title' => $e['title'], 'when' => $when], ['dedupe' => "event:{$e['id']}:{$e['event_date']}", 'pref' => 'activity_reminders'])) $n++;
        }
        return $n;
    }

    private static function paymentReminders(): int
    {
        $n = 0;
        $limit = date_add_days(today(), 5);
        $rows = DB::all("SELECT s.* FROM subscriptions s WHERE s.mode = 'one_time' AND s.status = 'active' AND s.current_period_end BETWEEN ? AND ?", [today(), $limit]);
        foreach ($rows as $s) {
            if (DB::val("SELECT COUNT(*) FROM subscriptions WHERE student_id = ? AND id <> ? AND status = 'active' AND current_period_end > ?", [$s['student_id'], $s['id'], $s['current_period_end']])) continue;
            if (Notify::send((int) $s['student_id'], 'payment_due', ['date' => fmt_date($s['current_period_end'])], ['dedupe' => "due:{$s['id']}:{$s['current_period_end']}", 'pref' => 'payment_reminders'])) $n++;
        }
        return $n;
    }

    public static function trainerCounters(): array
    {
        $trainerIds = DB::col("SELECT id FROM users WHERE role IN ('trainer','developer')");
        return [
            'pending' => (int) DB::val("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'pending' AND email_verified_at IS NOT NULL"),
            'assessments' => (int) DB::val("SELECT COUNT(*) FROM physical_assessments a JOIN users u ON u.id = a.student_id WHERE a.status = 'submitted' AND u.role = 'student'"),
            'onboarding' => (int) DB::val("SELECT COUNT(*) FROM anamneses a JOIN users u ON u.id = a.student_id WHERE a.status = 'submitted' AND u.role = 'student' AND NOT EXISTS (SELECT 1 FROM workouts w WHERE w.student_id = a.student_id AND w.archived_at IS NULL)"),
            'unread' => (int) DB::val('SELECT COUNT(*) FROM messages m JOIN users u ON u.id = m.student_id WHERE m.read_at IS NULL AND m.deleted_at IS NULL AND m.sender_id = m.student_id AND u.role = \'student\''),
            'requests' => (int) DB::val("SELECT COUNT(*) FROM reschedule_requests WHERE status = 'pending'"),
            'data_requests' => (int) DB::val("SELECT COUNT(*) FROM data_requests WHERE status = 'open'"),
            'failed_payments' => (int) DB::val("SELECT COUNT(*) FROM subscriptions WHERE status = 'payment_failed'"),
            'trainer_ids' => $trainerIds,
        ];
    }

    private static function trainerDigest(): string
    {
        if (Settings::get('notify_trainer_daily_digest') !== '1') return 'desativado';
        if ((int) local_now()->format('H') < Settings::int('trainer_digest_hour')) return 'aguardando horário';
        $c = self::trainerCounters();
        $lines = array_filter([
            $c['pending'] ? plural($c['pending'], 'cadastro aguardando aprovação', 'cadastros aguardando aprovação') : null,
            $c['onboarding'] ? plural($c['onboarding'], 'avaliação inicial aguardando o primeiro treino', 'avaliações iniciais aguardando o primeiro treino') : null,
            $c['assessments'] ? plural($c['assessments'], 'avaliação física para revisar', 'avaliações físicas para revisar') : null,
            $c['unread'] ? plural($c['unread'], 'mensagem não lida', 'mensagens não lidas') : null,
            $c['requests'] ? plural($c['requests'], 'pedido de mudança de treino', 'pedidos de mudança de treino') : null,
            $c['data_requests'] ? plural($c['data_requests'], 'solicitação LGPD em aberto', 'solicitações LGPD em aberto') : null,
            $c['failed_payments'] ? plural($c['failed_payments'], 'pagamento recusado', 'pagamentos recusados') : null,
        ]);
        if (!$lines) return 'sem pendências';
        Notify::toTrainer('trainer_digest', ['lines' => array_values($lines)], ['dedupe' => 'digest:' . today()]);
        return count($lines) . ' itens';
    }

    private static function maintenance(): string
    {
        RateLimit::purge();
        Tokens::purge();
        // Mantém o registro de entrega, mas descarta o corpo de e-mails antigos.
        DB::run("UPDATE email_queue SET html_body = '', text_body = '' WHERE status IN ('sent','skipped') AND created_at < ?", [gmdate('Y-m-d H:i:s', Clock::time() - 86400 * 90)]);
        $removed = 0;
        $months = Settings::int('retention_months_after_end');
        if ($months > 0) {
            $limit = gmdate('Y-m-d H:i:s', strtotime("-{$months} months", Clock::time()));
            $students = DB::col("SELECT id FROM users WHERE role = 'student' AND status IN ('ended','rejected') AND updated_at < ?", [$limit]);
            foreach ($students as $sid) {
                foreach (DB::all("SELECT id FROM files WHERE student_id = ? AND sensitive = 1 AND deleted_at IS NULL AND context IN ('anamnesis','assessment')", [$sid]) as $f) {
                    Files::delete((int) $f['id']);
                    $removed++;
                }
            }
            if ($removed) Audit::log('lgpd.retention_cleanup', null, null, ['files' => $removed], null);
        }
        return "ok ({$removed} fotos removidas por retenção)";
    }
}
