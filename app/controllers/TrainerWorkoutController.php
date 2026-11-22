<?php
declare(strict_types=1);

final class TrainerWorkoutController
{
    private function meta(): array
    {
        $name = mb_substr(trim((string) input('name', '')), 0, 150);
        $modality = input_int('modality_id');
        if ($name === '' || !$modality || !Modalities::find($modality)) {
            throw new UserError('Informe o nome e a modalidade do treino.');
        }
        $starts = (string) input('starts_on', '');
        $ends = (string) input('ends_on', '');
        return [
            'name' => $name,
            'label' => mb_substr(trim((string) input('label', '')), 0, 40) ?: null,
            'modality_id' => $modality,
            'weekdays' => (array) ($_POST['weekdays'] ?? []),
            'starts_on' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $starts) ? $starts : null,
            'ends_on' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $ends) ? $ends : null,
        ];
    }

    public function create(int $studentId): void
    {
        Students::findOrFail($studentId);
        try {
            $meta = $this->meta();
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect("/painel/alunos/{$studentId}?aba=treinos");
        }
        $tpl = input_int('template_id');
        if ($tpl) {
            $t = Workouts::find($tpl);
            if (!$t || !$t['is_template']) abort(404, 'Modelo não encontrado.');
            $id = Workouts::duplicate($tpl, $studentId, false, $meta['name']);
            DB::update('workouts', ['label' => $meta['label'], 'weekdays' => Workouts::weekdaysString($meta['weekdays']), 'modality_id' => $meta['modality_id'], 'starts_on' => $meta['starts_on'], 'ends_on' => $meta['ends_on']], 'id = ?', [$id]);
        } else {
            $id = Workouts::create($studentId, $meta);
        }
        Audit::log('workout.create', 'workout', $id, ['student' => $studentId, 'template' => $tpl]);
        redirect("/painel/treinos/{$id}");
    }

    public function edit(int $id): void
    {
        $w = Workouts::findOrFail($id);
        $wc = Workouts::ensureWorkingCopy($id);
        $modality = Modalities::find((int) $w['modality_id']);
        $items = Workouts::decodeItems(Workouts::items((int) $wc['id']), $modality);
        foreach ($items as &$it) {
            $it['video_file_url'] = $it['video_file_id'] ? Files::url((int) $it['video_file_id']) : null;
        }
        View::render('trainer/workout_editor', [
            'title' => 'Editar treino', 'nav' => $w['is_template'] ? 'templates' : 'students',
            'w' => $w, 'wc' => $wc, 'items' => $items, 'modality' => $modality, 'modalities' => Modalities::all(),
            'published' => Workouts::published($id),
            'versions' => DB::all('SELECT * FROM workout_versions WHERE workout_id = ? ORDER BY version_no DESC', [$id]),
            'student' => $w['student_id'] ? Students::find((int) $w['student_id']) : null,
            'students' => DB::all("SELECT id, name FROM users WHERE role = 'student' AND status IN ('active','paused') ORDER BY name"),
            'fieldsCatalog' => Modalities::FIELDS,
        ]);
    }

    public function updateMeta(int $id): void
    {
        $w = Workouts::findOrFail($id);
        try {
            $m = $this->meta();
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect("/painel/treinos/{$id}");
        }
        DB::update('workouts', [
            'name' => $m['name'], 'label' => $m['label'], 'modality_id' => $m['modality_id'],
            'weekdays' => $w['is_template'] ? null : Workouts::weekdaysString($m['weekdays']),
            'starts_on' => $m['starts_on'], 'ends_on' => $m['ends_on'], 'updated_at' => now(),
        ], 'id = ?', [$id]);
        Audit::log('workout.meta', 'workout', $id);
        if ($w['student_id'] && $w['weekdays'] !== Workouts::weekdaysString($m['weekdays']) && Workouts::published($id)) {
            Notify::send((int) $w['student_id'], 'calendar_changed', ['summary' => 'os dias de "' . $m['name'] . '" foram atualizados'], ['dedupe' => "wdays:{$id}:" . md5((string) Workouts::weekdaysString($m['weekdays'])), 'pref' => 'calendar_changes']);
        }
        flash('success', 'Dados do treino atualizados.');
        redirect("/painel/treinos/{$id}");
    }

    public function saveDraft(int $id): void
    {
        Workouts::findOrFail($id);
        $wc = Workouts::ensureWorkingCopy($id);
        $items = input('items', []);
        try {
            Workouts::saveItems((int) $wc['id'], is_array($items) ? $items : []);
        } catch (UserError $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        DB::update('workout_versions', ['general_notes' => mb_substr(trim((string) input('general_notes', '')), 0, 5000) ?: null], 'id = ?', [$wc['id']]);
        json_out(['ok' => true, 'saved_at' => fmt_time(now()), 'version_id' => (int) $wc['id']]);
    }

    public function publish(int $id): void
    {
        $w = Workouts::findOrFail($id);
        if ($w['is_template']) abort(422, 'Modelos não são publicados; use-os para criar treinos.');
        $wc = Workouts::workingCopy($id) ?? abort(422, 'Não há alterações para publicar.');
        try {
            if (input('when') === 'schedule') {
                $local = (string) input('publish_at', '');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $local)) throw new UserError('Informe data e horário da publicação.');
                Workouts::schedule((int) $wc['id'], local_to_utc(str_replace('T', ' ', $local)));
                flash('success', 'Publicação agendada para ' . fmt_datetime(local_to_utc(str_replace('T', ' ', $local))) . '. A versão atual continua valendo até lá.');
            } else {
                Workouts::publishNow((int) $wc['id']);
                flash('success', 'Treino publicado. O aluno foi avisado por e-mail.');
            }
        } catch (UserError $e) {
            flash('error', $e->getMessage());
        }
        redirect("/painel/treinos/{$id}");
    }

    public function unschedule(int $id): void
    {
        $wc = Workouts::workingCopy($id);
        if ($wc) Workouts::unschedule((int) $wc['id']);
        flash('success', 'Agendamento cancelado. A versão voltou a ser rascunho.');
        redirect("/painel/treinos/{$id}");
    }

    public function discardDraft(int $id): void
    {
        $wc = Workouts::workingCopy($id);
        if ($wc && Workouts::published($id)) {
            DB::delete('workout_versions', 'id = ?', [$wc['id']]);
            flash('success', 'Rascunho descartado. A versão publicada continua igual.');
        }
        redirect("/painel/treinos/{$id}");
    }

    public function duplicate(int $id): void
    {
        $w = Workouts::findOrFail($id);
        $target = input_int('student_id') ?? ($w['student_id'] ? (int) $w['student_id'] : null);
        if ($target) Students::findOrFail($target);
        $new = Workouts::duplicate($id, $target, !$target);
        flash('success', 'Treino duplicado como rascunho.');
        redirect("/painel/treinos/{$new}");
    }

    public function saveAsTemplate(int $id): void
    {
        $w = Workouts::findOrFail($id);
        $name = trim((string) input('template_name', '')) ?: $w['name'];
        $new = Workouts::duplicate($id, null, true, $name);
        flash('success', 'Modelo salvo. Ele pode ser usado para criar treinos de qualquer aluno.');
        redirect("/painel/treinos/{$new}");
    }

    public function archive(int $id): void
    {
        $w = Workouts::findOrFail($id);
        $restore = (bool) input('restore');
        DB::update('workouts', ['archived_at' => $restore ? null : now(), 'updated_at' => now()], 'id = ?', [$id]);
        Audit::log($restore ? 'workout.restore' : 'workout.archive', 'workout', $id);
        flash('success', $restore ? 'Treino restaurado.' : 'Treino arquivado. O histórico de execuções foi preservado.');
        redirect($w['is_template'] ? '/painel/modelos' : "/painel/alunos/{$w['student_id']}?aba=treinos");
    }

    public function version(int $id, int $vid): void
    {
        $w = Workouts::findOrFail($id);
        $v = DB::one('SELECT * FROM workout_versions WHERE id = ? AND workout_id = ?', [$vid, $id]) ?? abort(404);
        $modality = Modalities::find((int) $w['modality_id']);
        View::render('trainer/workout_version', [
            'title' => 'Versão ' . $v['version_no'], 'nav' => 'students', 'w' => $w, 'v' => $v, 'modality' => $modality,
            'items' => Workouts::decodeItems(Workouts::items($vid), $modality),
            'usage' => (int) DB::val('SELECT COUNT(*) FROM workout_sessions WHERE version_id = ?', [$vid]),
        ]);
    }

    public function uploadVideo(): void
    {
        try {
            $f = Files::store($_FILES['video'] ?? [], ['context' => 'exercise_video', 'kinds' => ['video'], 'sensitive' => 0]);
        } catch (UserError $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        json_out(['ok' => true, 'id' => (int) $f['id'], 'url' => Files::url((int) $f['id']), 'name' => $f['original_name']]);
    }

    public function templates(): void
    {
        View::render('trainer/templates', ['title' => 'Modelos de treino', 'nav' => 'templates', 'templates' => Workouts::templates(), 'modalities' => Modalities::all()]);
    }

    public function createTemplate(): void
    {
        try {
            $meta = $this->meta();
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect('/painel/modelos');
        }
        $id = Workouts::create(null, $meta + ['weekdays' => []], true);
        Audit::log('template.create', 'workout', $id);
        redirect("/painel/treinos/{$id}");
    }
}
