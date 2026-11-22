<?php
declare(strict_types=1);

final class StudentWorkoutController
{
    private function ownWorkout(int $id): array
    {
        $w = Workouts::find($id);
        if (!$w || (int) $w['student_id'] !== (int) Auth::id() || $w['is_template'] || $w['archived_at']) {
            abort(404, 'Treino não encontrado.');
        }
        return $w;
    }

    private function ownSession(int $id): array
    {
        $s = DB::one('SELECT * FROM workout_sessions WHERE id = ? AND student_id = ?', [$id, Auth::id()]);
        return $s ?? abort(404, 'Registro não encontrado.');
    }

    private function validDate(?string $d): string
    {
        if (!$d || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !strtotime($d)) return today();
        return $d;
    }

    public function index(): void
    {
        $uid = (int) Auth::id();
        $today = today();
        $dow = (int) (new DateTimeImmutable($today))->format('N'); // 1=segunda
        $monday = date_add_days($today, 1 - $dow);
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date_add_days($monday, $i);
            $week[$d] = Schedule::workoutsOn($uid, $d);
        }
        $workouts = array_filter(Workouts::forStudent($uid), fn($w) => $w['published']);
        View::render('student/workouts', [
            'title' => 'Treinos', 'nav' => 'workouts', 'week' => $week, 'today' => $today, 'workouts' => $workouts,
            'requests' => DB::all("SELECT r.*, w.name, w.label FROM reschedule_requests r JOIN workouts w ON w.id = r.workout_id WHERE r.student_id = ? ORDER BY r.id DESC LIMIT 5", [$uid]),
        ]);
    }

    public function show(int $id): void
    {
        $w = $this->ownWorkout($id);
        $v = Workouts::published($id) ?? abort(404, 'Este treino ainda não foi publicado.');
        $modality = Modalities::find((int) $w['modality_id']);
        $date = $this->validDate(input('data'));
        View::render('student/workout_show', [
            'title' => Workouts::title($w), 'nav' => 'workouts', 'workout' => $w, 'version' => $v, 'modality' => $modality,
            'items' => Workouts::decodeItems(Workouts::items((int) $v['id']), $modality),
            'date' => $date,
            'session' => DB::one('SELECT * FROM workout_sessions WHERE student_id = ? AND workout_id = ? AND scheduled_date = ?', [Auth::id(), $id, $date]),
        ]);
    }

    public function start(int $id): void
    {
        $this->ownWorkout($id);
        $uid = (int) Auth::id();
        $v = Workouts::published($id) ?? abort(404, 'Treino não publicado.');
        $date = $this->validDate(input('date'));
        if ($date > today()) {
            flash('error', 'Não é possível iniciar um treino de uma data futura.');
            back('/app/treinos');
        }
        $s = DB::one('SELECT * FROM workout_sessions WHERE student_id = ? AND workout_id = ? AND scheduled_date = ?', [$uid, $id, $date]);
        if (!$s) {
            try {
                $sid = DB::insert('workout_sessions', [
                    'student_id' => $uid, 'workout_id' => $id, 'version_id' => $v['id'], 'scheduled_date' => $date,
                    'status' => 'in_progress', 'started_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            } catch (PDOException $e) {
                if (!DB::isUniqueViolation($e)) throw $e;
                $sid = (int) DB::val('SELECT id FROM workout_sessions WHERE student_id = ? AND workout_id = ? AND scheduled_date = ?', [$uid, $id, $date]);
            }
        } else {
            $sid = (int) $s['id'];
            if (in_array($s['status'], ['postponed', 'skipped'], true)) {
                // Retoma usando a versão vigente se nada havia sido registrado.
                $hasEntries = DB::val('SELECT COUNT(*) FROM session_entries WHERE session_id = ?', [$sid]);
                DB::update('workout_sessions', ['status' => 'in_progress', 'started_at' => now(), 'updated_at' => now()] + ($hasEntries ? [] : ['version_id' => $v['id']]), 'id = ?', [$sid]);
            }
        }
        redirect("/app/sessao/{$sid}");
    }

    public function markStatus(int $id): void
    {
        $this->ownWorkout($id);
        $uid = (int) Auth::id();
        $status = (string) input('status');
        if (!in_array($status, ['completed', 'postponed', 'skipped'], true)) {
            flash('error', 'Situação inválida.');
            back('/app/treinos');
        }
        $date = $this->validDate(input('date'));
        if ($status === 'completed' && $date > today()) {
            flash('error', 'Não é possível marcar como realizado um treino futuro.');
            back('/app/treinos');
        }
        $v = Workouts::published($id) ?? abort(404);
        $note = mb_substr(trim((string) input('notes', '')), 0, 2000) ?: null;
        $s = DB::one('SELECT * FROM workout_sessions WHERE student_id = ? AND workout_id = ? AND scheduled_date = ?', [$uid, $id, $date]);
        $data = ['status' => $status, 'notes' => $note, 'updated_at' => now(), 'completed_at' => $status === 'completed' ? now() : null];
        if ($s) {
            DB::update('workout_sessions', $data, 'id = ?', [$s['id']]);
        } else {
            DB::insert('workout_sessions', $data + ['student_id' => $uid, 'workout_id' => $id, 'version_id' => $v['id'], 'scheduled_date' => $date, 'created_at' => now()]);
        }
        flash('success', 'Situação registrada: ' . Schedule::sessionStatusLabel($status) . '.');
        back('/app/treinos');
    }

    public function requestChange(int $id): void
    {
        $w = $this->ownWorkout($id);
        $uid = (int) Auth::id();
        $orig = $this->validDate(input('original_date'));
        $new = (string) input('requested_date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new) || $new === $orig || $new < today()) {
            flash('error', 'Escolha uma nova data válida (hoje ou depois) e diferente da original.');
            back('/app/treinos');
        }
        if (DB::val("SELECT id FROM reschedule_requests WHERE student_id = ? AND workout_id = ? AND original_date = ? AND status = 'pending'", [$uid, $id, $orig])) {
            flash('info', 'Já existe um pedido pendente para este treino nesta data.');
            back('/app/treinos');
        }
        $rid = DB::insert('reschedule_requests', [
            'student_id' => $uid, 'workout_id' => $id, 'original_date' => $orig, 'requested_date' => $new,
            'note' => mb_substr(trim((string) input('note', '')), 0, 1000) ?: null, 'status' => 'pending', 'created_at' => now(),
        ]);
        Audit::log('reschedule.request', 'reschedule_request', $rid);
        if (Settings::get('notify_trainer_requests') === '1') {
            Notify::toTrainer('reschedule_request', ['student' => Auth::user()['name']], ['dedupe' => "resreq:{$rid}"]);
        }
        flash('success', 'Pedido enviado ao Renan. A programação muda somente após a aprovação.');
        back('/app/treinos');
    }

    public function session(int $id): void
    {
        $s = $this->ownSession($id);
        $w = Workouts::findOrFail((int) $s['workout_id']);
        $modality = Modalities::find((int) $w['modality_id']);
        $v = DB::one('SELECT * FROM workout_versions WHERE id = ?', [$s['version_id']]);
        $items = Workouts::decodeItems(Workouts::items((int) $s['version_id']), $modality);
        $entries = [];
        foreach (DB::all('SELECT * FROM session_entries WHERE session_id = ?', [$id]) as $e) {
            $e['data'] = json_decode_array($e['data']);
            $entries[(int) $e['item_id']] = $e;
        }
        View::render('student/session', [
            'title' => Workouts::title($w), 'nav' => 'workouts', 'session' => $s, 'workout' => $w, 'version' => $v,
            'modality' => $modality, 'items' => $items, 'entries' => $entries,
        ]);
    }

    public function saveEntry(int $id): void
    {
        // Correções após a conclusão são permitidas; o status do treino é mantido.
        $s = $this->ownSession($id);
        $itemId = (int) input('item_id');
        if (!DB::val('SELECT id FROM workout_items WHERE id = ? AND version_id = ?', [$itemId, $s['version_id']])) {
            json_out(['ok' => false, 'error' => 'Exercício inválido.'], 422);
        }
        $raw = input('data', []);
        $data = $this->cleanEntry(is_array($raw) ? $raw : []);
        $completed = (int) (bool) input('completed');
        $exists = DB::val('SELECT id FROM session_entries WHERE session_id = ? AND item_id = ?', [$id, $itemId]);
        if ($exists) {
            DB::update('session_entries', ['data' => json_enc($data), 'completed' => $completed, 'updated_at' => now()], 'id = ?', [$exists]);
        } else {
            DB::insert('session_entries', ['session_id' => $id, 'item_id' => $itemId, 'data' => json_enc($data), 'completed' => $completed, 'updated_at' => now()]);
        }
        DB::update('workout_sessions', ['updated_at' => now()], 'id = ?', [$id]);
        $done = (int) DB::val('SELECT COUNT(*) FROM session_entries WHERE session_id = ? AND completed = 1', [$id]);
        json_out(['ok' => true, 'saved_at' => fmt_time(now()), 'done' => $done]);
    }

    private function cleanEntry(array $raw): array
    {
        $out = ['sets' => []];
        foreach (array_slice((array) ($raw['sets'] ?? []), 0, 30) as $set) {
            $row = [];
            foreach (['reps', 'load', 'time', 'distance'] as $k) {
                $v = trim((string) ($set[$k] ?? ''));
                if ($v !== '') $row[$k] = mb_substr($v, 0, 20);
            }
            $row['done'] = !empty($set['done']);
            $out['sets'][] = $row;
        }
        foreach (['duration', 'distance', 'time', 'intensity', 'pace', 'reps', 'load'] as $k) {
            $v = trim((string) ($raw[$k] ?? ''));
            if ($v !== '') $out[$k] = mb_substr($v, 0, 40);
        }
        $notes = trim((string) ($raw['notes'] ?? ''));
        if ($notes !== '') $out['notes'] = mb_substr($notes, 0, 2000);
        return $out;
    }

    public function complete(int $id): void
    {
        $s = $this->ownSession($id);
        DB::update('workout_sessions', [
            'status' => 'completed', 'completed_at' => $s['completed_at'] ?? now(), 'updated_at' => now(),
            'notes' => mb_substr(trim((string) input('notes', '')), 0, 2000) ?: $s['notes'],
        ], 'id = ?', [$id]);
        flash('success', 'Treino concluído. Bom trabalho!');
        redirect('/app');
    }

    public function history(): void
    {
        $uid = (int) Auth::id();
        $page = max(1, (int) input('p', 1));
        $per = 20;
        $total = (int) DB::val('SELECT COUNT(*) FROM workout_sessions WHERE student_id = ?', [$uid]);
        $rows = DB::all('SELECT s.*, w.name, w.label, v.version_no, m.name AS modality_name,
                (SELECT COUNT(*) FROM session_entries e WHERE e.session_id = s.id AND e.completed = 1) AS done,
                (SELECT COUNT(*) FROM workout_items i WHERE i.version_id = s.version_id) AS total_items
            FROM workout_sessions s JOIN workouts w ON w.id = s.workout_id JOIN workout_versions v ON v.id = s.version_id JOIN modalities m ON m.id = w.modality_id
            WHERE s.student_id = ? ORDER BY s.scheduled_date DESC, s.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), [$uid]);
        View::render('student/history', ['title' => 'Histórico de treinos', 'nav' => 'workouts', 'rows' => $rows, 'page' => $page, 'pages' => max(1, (int) ceil($total / $per))]);
    }
}
