<?php
declare(strict_types=1);

final class TrainerController
{
    public function dashboard(): void
    {
        $today = today();
        $dow = (int) (new DateTimeImmutable($today))->format('N');
        $monday = date_add_days($today, 1 - $dow);
        $sunday = date_add_days($monday, 6);
        $c = Cron::trainerCounters();
        $staleDays = Settings::int('workout_stale_days');
        $staleLimit = gmdate('Y-m-d H:i:s', Clock::time() - $staleDays * 86400);
        // Alunos ativos com avaliação inicial concluída e sem treino publicado, ou com treino publicado há muito tempo.
        $needsUpdate = DB::all("SELECT u.id, u.name,
                (SELECT MAX(v.published_at) FROM workout_versions v JOIN workouts w ON w.id = v.workout_id WHERE w.student_id = u.id AND w.archived_at IS NULL AND v.status = 'published') AS last_pub
            FROM users u JOIN student_profiles p ON p.user_id = u.id
            WHERE u.role = 'student' AND u.status = 'active' AND p.onboarding_completed_at IS NOT NULL");
        $needsUpdate = array_values(array_filter($needsUpdate, fn($r) => !$r['last_pub'] || $r['last_pub'] < $staleLimit));
        usort($needsUpdate, fn($a, $b) => strcmp((string) $a['last_pub'], (string) $b['last_pub']));
        View::render('trainer/dashboard', [
            'title' => 'Painel', 'nav' => 'home',
            'active' => (int) DB::val("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'"),
            'c' => $c,
            'pendingList' => DB::all("SELECT id, name, email, created_at FROM users WHERE role = 'student' AND status = 'pending' AND email_verified_at IS NOT NULL ORDER BY created_at LIMIT 5"),
            'assessmentList' => DB::all("SELECT a.id, a.submitted_at, u.name FROM physical_assessments a JOIN users u ON u.id = a.student_id WHERE a.status = 'submitted' AND u.role = 'student' ORDER BY a.submitted_at LIMIT 5"),
            'needsUpdate' => array_slice($needsUpdate, 0, 6), 'needsUpdateCount' => count($needsUpdate), 'staleDays' => $staleDays,
            'weekEvents' => DB::all("SELECT e.*, u.name FROM calendar_events e JOIN users u ON u.id = e.student_id WHERE e.event_date BETWEEN ? AND ? AND e.type <> 'rest' ORDER BY e.event_date, e.start_time IS NULL, e.start_time LIMIT 8", [$today, $sunday]),
            'weekSessions' => [
                'completed' => (int) DB::val("SELECT COUNT(*) FROM workout_sessions s JOIN users u ON u.id = s.student_id WHERE u.role = 'student' AND s.status = 'completed' AND s.scheduled_date BETWEEN ? AND ?", [$monday, $sunday]),
                'missed' => (int) DB::val("SELECT COUNT(*) FROM workout_sessions s JOIN users u ON u.id = s.student_id WHERE u.role = 'student' AND s.status IN ('skipped','postponed') AND s.scheduled_date BETWEEN ? AND ?", [$monday, $sunday]),
            ],
            'unreadList' => array_slice(array_filter(ChatController::conversations(), fn($r) => (int) $r['unread'] > 0), 0, 5),
            'failedEmails' => (int) DB::val("SELECT COUNT(*) FROM email_queue WHERE status = 'failed' AND created_at >= ?", [gmdate('Y-m-d H:i:s', Clock::time() - 7 * 86400)]),
            'cronLast' => Settings::get('cron_last_run'),
        ]);
    }

    public function requests(): void
    {
        View::render('trainer/requests', [
            'title' => 'Pedidos de mudança', 'nav' => 'requests',
            'pending' => DB::all("SELECT r.*, u.name AS student_name, w.name, w.label FROM reschedule_requests r JOIN users u ON u.id = r.student_id JOIN workouts w ON w.id = r.workout_id WHERE r.status = 'pending' ORDER BY r.created_at"),
            'decided' => DB::all("SELECT r.*, u.name AS student_name, w.name, w.label FROM reschedule_requests r JOIN users u ON u.id = r.student_id JOIN workouts w ON w.id = r.workout_id WHERE r.status <> 'pending' ORDER BY r.decided_at DESC LIMIT 30"),
        ]);
    }

    public function decideRequest(int $id): void
    {
        $r = DB::one('SELECT * FROM reschedule_requests WHERE id = ?', [$id]) ?? abort(404);
        if ($r['status'] !== 'pending') {
            flash('info', 'Este pedido já foi respondido.');
            redirect('/painel/solicitacoes');
        }
        $approve = input('decision') === 'approve';
        $note = mb_substr(trim((string) input('decision_note', '')), 0, 1000) ?: null;
        DB::transaction(function () use ($r, $approve, $note) {
            DB::update('reschedule_requests', ['status' => $approve ? 'approved' : 'rejected', 'decided_by' => Auth::id(), 'decided_at' => now(), 'decision_note' => $note], 'id = ?', [$r['id']]);
            if ($approve) {
                // Somente após a aprovação a programação oficial muda.
                DB::insert('schedule_overrides', ['student_id' => $r['student_id'], 'workout_id' => $r['workout_id'], 'original_date' => $r['original_date'], 'new_date' => $r['requested_date'], 'request_id' => $r['id'], 'created_at' => now()]);
            }
        });
        Audit::log($approve ? 'reschedule.approve' : 'reschedule.reject', 'reschedule_request', $id);
        $w = Workouts::find((int) $r['workout_id']);
        Notify::send((int) $r['student_id'], 'reschedule_decided', ['decision' => $approve ? 'aprovado' : 'recusado', 'workout' => $w ? Workouts::title($w) : 'o treino'], ['dedupe' => "resdec:{$id}"]);
        flash('success', $approve ? 'Pedido aprovado. O calendário do aluno foi atualizado.' : 'Pedido recusado.');
        back('/painel/solicitacoes');
    }
}
