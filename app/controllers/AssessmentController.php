<?php
declare(strict_types=1);

/** Avaliações físicas periódicas baseadas em fotografias (frente, lado, costas) + medidas opcionais. */
final class AssessmentController
{
    public const SLOTS = ['front' => 'Frente', 'side' => 'Lado', 'back' => 'Costas'];
    public const MEASURES = ['weight_kg' => 'Peso (kg)', 'waist_cm' => 'Cintura (cm)', 'hip_cm' => 'Quadril (cm)', 'chest_cm' => 'Peitoral (cm)', 'arm_cm' => 'Braço (cm)', 'thigh_cm' => 'Coxa (cm)', 'calf_cm' => 'Panturrilha (cm)'];

    private function own(int $id): array
    {
        return DB::one('SELECT * FROM physical_assessments WHERE id = ? AND student_id = ?', [$id, Auth::id()]) ?? abort(404, 'Avaliação não encontrada.');
    }

    public function studentIndex(): void
    {
        $uid = (int) Auth::id();
        View::render('student/assessments', [
            'title' => 'Avaliações físicas', 'nav' => 'assessments',
            'profile' => Students::profile($uid),
            'rows' => DB::all("SELECT * FROM physical_assessments WHERE student_id = ? AND status <> 'draft' ORDER BY id DESC", [$uid]),
            'draft' => DB::one("SELECT * FROM physical_assessments WHERE student_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1", [$uid]),
        ]);
    }

    public function studentNew(): void
    {
        $uid = (int) Auth::id();
        $draft = DB::one("SELECT id FROM physical_assessments WHERE student_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1", [$uid]);
        if ($draft) redirect('/app/avaliacoes/' . $draft['id']);
        $p = Students::profile($uid);
        $id = DB::insert('physical_assessments', [
            'student_id' => $uid, 'cycle' => $p['cycle'], 'due_on' => $p['next_assessment_on'], 'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        redirect('/app/avaliacoes/' . $id);
    }

    public function studentShow(int $id): void
    {
        $a = $this->own($id);
        View::render('student/assessment_form', [
            'title' => 'Avaliação física', 'nav' => 'assessments', 'a' => $a,
            'measures' => json_decode_array($a['measurements']), 'photos' => Files::forContext('assessment', $id),
            'slots' => self::SLOTS, 'measureLabels' => self::MEASURES,
        ]);
    }

    public function uploadPhoto(int $id): void
    {
        $a = $this->own($id);
        $uid = (int) Auth::id();
        if ($a['status'] !== 'draft') json_out(['ok' => false, 'error' => 'Avaliação já enviada.'], 409);
        if (!RateLimit::hit("upload:{$uid}", 60, 600)) json_out(['ok' => false, 'error' => 'Muitos envios seguidos.'], 429);
        $slot = (string) input('slot', 'extra');
        if (!isset(self::SLOTS[$slot]) && $slot !== 'extra') json_out(['ok' => false, 'error' => 'Posição inválida.'], 422);
        try {
            $f = Files::store($_FILES['photo'] ?? [], ['context' => 'assessment', 'context_id' => $id, 'student_id' => $uid, 'kinds' => ['image'], 'kind_label' => $slot, 'sensitive' => 1]);
        } catch (UserError $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        if ($slot !== 'extra') {
            foreach (DB::col("SELECT id FROM files WHERE context = 'assessment' AND context_id = ? AND kind = ? AND id <> ? AND deleted_at IS NULL", [$id, $slot, $f['id']]) as $old) {
                Files::delete((int) $old);
            }
        }
        json_out(['ok' => true, 'id' => (int) $f['id'], 'url' => Files::url((int) $f['id']), 'slot' => $slot]);
    }

    public function deletePhoto(int $id, int $fid): void
    {
        $a = $this->own($id);
        $f = Files::find($fid);
        if ($a['status'] !== 'draft' || !$f || $f['context'] !== 'assessment' || (int) $f['context_id'] !== $id) {
            json_out(['ok' => false, 'error' => 'Foto não encontrada.'], 404);
        }
        Files::delete($fid);
        json_out(['ok' => true]);
    }

    public function submit(int $id): void
    {
        $a = $this->own($id);
        if ($a['status'] !== 'draft') redirect('/app/avaliacoes/' . $id);
        $m = [];
        foreach (self::MEASURES as $k => $_) {
            $v = str_replace(',', '.', trim((string) ($_POST['m'][$k] ?? '')));
            if ($v !== '' && is_numeric($v) && (float) $v > 0 && (float) $v < 400) $m[$k] = round((float) $v, 1);
        }
        $notes = mb_substr(trim((string) input('student_notes', '')), 0, 4000) ?: null;
        DB::update('physical_assessments', ['measurements' => $m ? json_enc($m) : null, 'student_notes' => $notes, 'updated_at' => now()], 'id = ?', [$id]);
        if (input('action') !== 'submit') {
            flash('success', 'Rascunho salvo.');
            redirect('/app/avaliacoes/' . $id);
        }
        $kinds = array_column(Files::forContext('assessment', $id), 'kind');
        $missing = array_diff(array_keys(self::SLOTS), $kinds);
        if ($missing) {
            flash('error', 'Envie as fotos obrigatórias: ' . implode(', ', array_map(fn($k) => self::SLOTS[$k], $missing)) . '.');
            redirect('/app/avaliacoes/' . $id);
        }
        DB::update('physical_assessments', ['status' => 'submitted', 'submitted_at' => now(), 'updated_at' => now()], 'id = ?', [$id]);
        Audit::log('assessment.submit', 'assessment', $id);
        if (Settings::get('notify_trainer_assessment') === '1') {
            Notify::toTrainer('assessment_received', ['student' => Auth::user()['name'], 'link' => url('/painel/avaliacoes/' . $id)], ['dedupe' => "assessrcv:{$id}"]);
        }
        flash('success', 'Avaliação enviada! O Renan foi avisado.');
        redirect('/app/avaliacoes');
    }

    // ───────── Renan ─────────

    public function trainerIndex(): void
    {
        $status = input('situacao') === 'revisadas' ? 'reviewed' : 'submitted';
        View::render('trainer/assessments', [
            'title' => 'Avaliações físicas', 'nav' => 'assessments', 'status' => $status,
            'rows' => DB::all("SELECT a.*, u.name, u.avatar_file_id FROM physical_assessments a JOIN users u ON u.id = a.student_id
                WHERE a.status = ? ORDER BY a.submitted_at " . ($status === 'submitted' ? 'ASC' : 'DESC') . ' LIMIT 100', [$status]),
            'overdue' => DB::all("SELECT u.id, u.name, p.next_assessment_on FROM users u JOIN student_profiles p ON p.user_id = u.id
                WHERE u.role = 'student' AND u.status = 'active' AND p.next_assessment_on < ?
                AND NOT EXISTS (SELECT 1 FROM physical_assessments a WHERE a.student_id = u.id AND a.due_on = p.next_assessment_on AND a.status IN ('submitted','reviewed'))
                ORDER BY p.next_assessment_on", [today()]),
        ]);
    }

    public function trainerShow(int $id): void
    {
        $a = DB::one('SELECT a.*, u.name, u.id AS uid FROM physical_assessments a JOIN users u ON u.id = a.student_id WHERE a.id = ?', [$id]) ?? abort(404);
        $prev = DB::all("SELECT * FROM physical_assessments WHERE student_id = ? AND id <> ? AND status IN ('submitted','reviewed') ORDER BY submitted_at DESC LIMIT 6", [$a['student_id'], $id]);
        $compare = (int) input('comparar', $prev[0]['id'] ?? 0);
        $compareRow = null;
        foreach ($prev as $p) if ((int) $p['id'] === $compare) $compareRow = $p;
        View::render('trainer/assessment_show', [
            'title' => 'Avaliação de ' . $a['name'], 'nav' => 'assessments', 'a' => $a,
            'photos' => Files::forContext('assessment', $id),
            'measures' => json_decode_array($a['measurements']),
            'previous' => $prev, 'compare' => $compareRow,
            'comparePhotos' => $compareRow ? Files::forContext('assessment', (int) $compareRow['id']) : [],
            'initialPhotos' => ($a2 = Anamnesis::latestSubmitted((int) $a['student_id'])) ? Files::forContext('anamnesis', (int) $a2['id']) : [],
            'profile' => Students::profile((int) $a['student_id']),
            'slots' => self::SLOTS, 'measureLabels' => self::MEASURES,
        ]);
    }

    public function review(int $id): void
    {
        $a = DB::one('SELECT * FROM physical_assessments WHERE id = ?', [$id]) ?? abort(404);
        if ($a['status'] === 'draft') abort(422, 'Avaliação ainda não enviada pelo aluno.');
        $upd = [
            'trainer_comments' => mb_substr(trim((string) input('trainer_comments', '')), 0, 6000) ?: null,
            'trainer_guidance' => mb_substr(trim((string) input('trainer_guidance', '')), 0, 6000) ?: null,
            'updated_at' => now(),
        ];
        $markReviewed = (bool) input('mark_reviewed') && $a['status'] === 'submitted';
        if ($markReviewed) {
            $upd += ['status' => 'reviewed', 'reviewed_at' => now(), 'reviewed_by' => Auth::id()];
        }
        DB::update('physical_assessments', $upd, 'id = ?', [$id]);
        $p = Students::profile((int) $a['student_id']);
        $next = (string) input('next_assessment_on', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $next)) {
            DB::update('student_profiles', ['next_assessment_on' => $next, 'updated_at' => now()], 'user_id = ?', [$a['student_id']]);
        } elseif ($markReviewed) {
            // Próxima avaliação: data de envio + intervalo definido para o aluno.
            $base = to_local($a['submitted_at'])->format('Y-m-d');
            DB::update('student_profiles', ['next_assessment_on' => date_add_days($base, max(1, (int) $p['assessment_every_days'])), 'updated_at' => now()], 'user_id = ?', [$a['student_id']]);
        }
        Audit::log($markReviewed ? 'assessment.review' : 'assessment.comment', 'assessment', $id);
        if ($markReviewed) {
            Notify::send((int) $a['student_id'], 'assessment_reviewed', ['link' => url('/app/avaliacoes/' . $id)], ['dedupe' => "assessrev:{$id}"]);
            flash('success', 'Avaliação marcada como revisada. Próxima avaliação calculada.');
        } else {
            flash('success', 'Comentários salvos.');
        }
        redirect('/painel/avaliacoes/' . $id);
    }
}
