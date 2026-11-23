<?php
declare(strict_types=1);

/** Regras de negócio dos alunos: aprovação, situação, ciclos e configurações individuais. */
final class Students
{
    public const STATUSES = ['pending' => 'Pendente', 'active' => 'Ativo', 'paused' => 'Pausado', 'ended' => 'Encerrado'];

    public const SELF_EVAL_CATEGORIES = [
        'sono' => 'Sono', 'alimentacao' => 'Alimentação', 'treinamento' => 'Treinamento', 'cardio' => 'Cardio',
        'disposicao' => 'Disposição', 'recuperacao' => 'Recuperação', 'motivacao' => 'Motivação', 'hidratacao' => 'Hidratação',
    ];

    public static function ensureProfile(int $userId): void
    {
        if (!DB::val('SELECT user_id FROM student_profiles WHERE user_id = ?', [$userId])) {
            DB::insert('student_profiles', [
                'user_id' => $userId,
                'self_eval_every_days' => Settings::int('default_self_eval_days') ?: 7,
                'assessment_every_days' => Settings::int('default_assessment_days') ?: 30,
                'self_eval_categories' => json_enc(array_keys(self::SELF_EVAL_CATEGORIES)),
                'self_eval_reminder_time' => '08:00',
                'updated_at' => now(),
            ]);
        }
    }

    public static function profile(int $userId): array
    {
        self::ensureProfile($userId);
        $p = DB::one('SELECT * FROM student_profiles WHERE user_id = ?', [$userId]);
        $p['categories'] = json_decode_array($p['self_eval_categories']) ?: array_keys(self::SELF_EVAL_CATEGORIES);
        return $p;
    }

    public static function find(int $id): ?array
    {
        return DB::one("SELECT u.*, p.modality_id, p.cycle, p.onboarding_completed_at, p.next_assessment_on, p.phone
            FROM users u LEFT JOIN student_profiles p ON p.user_id = u.id WHERE u.id = ? AND u.role IN ('student','developer')", [$id]);
    }

    public static function findOrFail(int $id): array
    {
        return self::find($id) ?? abort(404, 'Aluno não encontrado.');
    }

    public static function notifyTrainerNewSignup(array $user): void
    {
        if (Settings::get('notify_trainer_new_signup') === '1') {
            Notify::toTrainer('signup_pending', ['student' => $user['name'], 'link' => url('/painel/alunos/' . $user['id'])], ['dedupe' => 'signup:' . $user['id']]);
        }
    }

    public static function approve(int $id, string $note = ''): void
    {
        $s = self::findOrFail($id);
        if ($s['status'] !== 'pending' && $s['status'] !== 'rejected') {
            throw new UserError('Este cadastro já foi analisado.');
        }
        DB::transaction(function () use ($id, $note) {
            DB::update('users', ['status' => 'active', 'approved_at' => now(), 'decision_note' => $note ?: null, 'updated_at' => now()], 'id = ?', [$id]);
            DB::update('student_profiles', ['cycle_started_on' => today(), 'updated_at' => now()], 'user_id = ?', [$id]);
            if (!DB::val('SELECT id FROM student_cycles WHERE student_id = ?', [$id])) {
                DB::insert('student_cycles', ['student_id' => $id, 'number' => 1, 'started_on' => today(), 'created_at' => now()]);
            }
        });
        Audit::log('student.approve', 'user', $id);
        Notify::send($id, 'approved', [], ['dedupe' => "approved:{$id}:" . today()]);
    }

    public static function reject(int $id, string $note = ''): void
    {
        $s = self::findOrFail($id);
        if ($s['status'] !== 'pending') {
            throw new UserError('Somente cadastros pendentes podem ser recusados.');
        }
        DB::update('users', ['status' => 'rejected', 'decision_note' => $note ?: null, 'updated_at' => now()], 'id = ?', [$id]);
        Audit::log('student.reject', 'user', $id, ['note' => $note]);
        Notify::send($id, 'rejected', ['note' => $note], ['dedupe' => "rejected:{$id}:" . today()]);
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['paused', 'ended'], true)) {
            throw new UserError('Situação inválida.');
        }
        $s = self::findOrFail($id);
        DB::update('users', ['status' => $status, 'updated_at' => now()], 'id = ?', [$id]);
        if ($status === 'ended') {
            DB::run('UPDATE student_cycles SET ended_on = ? WHERE student_id = ? AND ended_on IS NULL', [today(), $id]);
        }
        Audit::log('student.status', 'user', $id, ['from' => $s['status'], 'to' => $status]);
    }

    /** Reativa um aluno pausado/encerrado. Com novo ciclo, preserva todo o histórico e pode pedir nova avaliação inicial. */
    public static function reactivate(int $id, bool $newCycle, bool $requireNewOnboarding): void
    {
        $s = self::findOrFail($id);
        if (!in_array($s['status'], ['paused', 'ended'], true)) {
            throw new UserError('Somente alunos pausados ou encerrados podem ser reativados.');
        }
        DB::transaction(function () use ($id, $newCycle, $requireNewOnboarding) {
            DB::update('users', ['status' => 'active', 'updated_at' => now()], 'id = ?', [$id]);
            if ($newCycle) {
                $p = self::profile($id);
                $next = (int) $p['cycle'] + 1;
                DB::run('UPDATE student_cycles SET ended_on = ? WHERE student_id = ? AND ended_on IS NULL', [today(), $id]);
                DB::insert('student_cycles', ['student_id' => $id, 'number' => $next, 'started_on' => today(), 'created_at' => now()]);
                $upd = ['cycle' => $next, 'cycle_started_on' => today(), 'updated_at' => now()];
                if ($requireNewOnboarding) {
                    $upd['onboarding_completed_at'] = null;
                }
                DB::update('student_profiles', $upd, 'user_id = ?', [$id]);
            }
        });
        Audit::log('student.reactivate', 'user', $id, ['new_cycle' => $newCycle, 'new_onboarding' => $requireNewOnboarding]);
    }

    /** Lista "Meus alunos" com busca, filtros, ordenação e paginação. */
    public static function search(array $f, int $page = 1, int $perPage = 20): array
    {
        $where = ["u.role = 'student'"];
        $params = [];
        if (!empty($f['include_dev'])) {
            $where = ["u.role IN ('student','developer')"];
        }
        if (!empty($f['q'])) {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $f['q'] . '%';
            $params[] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status']) && isset(self::STATUSES[$f['status']])) {
            $where[] = 'u.status = ?';
            $params[] = $f['status'];
        } elseif (empty($f['status'])) {
            $where[] = "u.status <> 'rejected'";
        } elseif ($f['status'] === 'rejected') {
            $where[] = "u.status = 'rejected'";
        } elseif ($f['status'] === 'late') {
            // Ativos com avaliação física atrasada ou pagamento recusado.
            $where[] = "u.status = 'active' AND (p.next_assessment_on < ? OR EXISTS (SELECT 1 FROM subscriptions sb WHERE sb.student_id = u.id AND sb.status = 'payment_failed'))";
            $params[] = today();
        }
        if (!empty($f['modality'])) {
            $where[] = '(p.modality_id = ? OR EXISTS (SELECT 1 FROM workouts w WHERE w.student_id = u.id AND w.modality_id = ? AND w.archived_at IS NULL))';
            $params[] = (int) $f['modality'];
            $params[] = (int) $f['modality'];
        }
        $order = match ($f['sort'] ?? 'name') {
            'recent' => 'u.created_at DESC',
            'activity' => 'u.last_activity_at IS NULL, u.last_activity_at DESC',
            'assessment' => 'p.next_assessment_on IS NULL, p.next_assessment_on ASC',
            default => 'u.name ASC',
        };
        $w = implode(' AND ', $where);
        $total = (int) DB::val("SELECT COUNT(*) FROM users u LEFT JOIN student_profiles p ON p.user_id = u.id WHERE {$w}", $params);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = DB::all("SELECT u.id, u.name, u.email, u.status, u.role, u.avatar_file_id, u.last_activity_at, u.created_at,
                p.modality_id, p.next_assessment_on, p.onboarding_completed_at,
                (SELECT MAX(s.updated_at) FROM workout_sessions s WHERE s.student_id = u.id) AS last_session_at
            FROM users u LEFT JOIN student_profiles p ON p.user_id = u.id
            WHERE {$w} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}", $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public static function modalitiesOf(int $studentId): array
    {
        return DB::col('SELECT DISTINCT m.name FROM workouts w JOIN modalities m ON m.id = w.modality_id WHERE w.student_id = ? AND w.archived_at IS NULL', [$studentId]);
    }

    /** Exclusão definitiva (LGPD) — remove arquivos físicos e todos os registros do aluno. */
    public static function purge(int $id): void
    {
        $s = self::findOrFail($id);
        foreach (DB::all('SELECT * FROM files WHERE student_id = ? OR uploaded_by = ?', [$id, $id]) as $f) {
            @unlink(Files::fullPath($f));
        }
        Audit::log('student.purge', 'user', $id, ['email_hash' => hash('sha256', $s['email'])]);
        DB::transaction(function () use ($id) {
            DB::delete('files', 'student_id = ? OR uploaded_by = ?', [$id, $id]);
            DB::delete('payments', 'student_id = ?', [$id]);
            DB::delete('subscriptions', 'student_id = ?', [$id]);
            DB::run('DELETE FROM session_entries WHERE session_id IN (SELECT id FROM workout_sessions WHERE student_id = ?)', [$id]);
            DB::delete('workout_sessions', 'student_id = ?', [$id]);
            DB::delete('users', 'id = ?', [$id]);
        });
    }
}
