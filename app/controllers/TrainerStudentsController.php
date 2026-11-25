<?php
declare(strict_types=1);

final class TrainerStudentsController
{
    public const TABS = ['treinos' => 'Treinos', 'progresso' => 'Progresso', 'dados' => 'Dados', 'visao' => 'Visão geral', 'avaliacoes' => 'Avaliações físicas', 'autoavaliacoes' => 'Autoavaliações', 'calendario' => 'Calendário', 'mensagens' => 'Mensagens'];

    public function index(): void
    {
        $f = ['q' => (string) input('q', ''), 'status' => (string) input('situacao', ''), 'modality' => input_int('modalidade'), 'sort' => (string) input('ordem', 'name'), 'include_dev' => (bool) input('testes')];
        $result = Students::search($f, max(1, (int) input('p', 1)));
        View::render('trainer/students', ['title' => 'Meus alunos', 'nav' => 'students', 'f' => $f, 'r' => $result, 'modalities' => Modalities::map()]);
    }

    public function show(int $id): void
    {
        $s = Students::findOrFail($id);
        $tab = (string) input('aba', 'treinos');
        if (!isset(self::TABS[$tab])) $tab = 'treinos';
        $profile = Students::profile($id);
        $data = [
            'title' => $s['name'], 'nav' => 'students', 's' => $s, 'profile' => $profile, 'tab' => $tab, 'tabs' => self::TABS,
            'modalities' => Modalities::map(), 'standing' => Billing::standing($id),
            'unread' => (int) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND sender_id = ? AND read_at IS NULL AND deleted_at IS NULL', [$id, $id]),
        ];
        switch ($tab) {
            case 'visao':
                $data['sessions'] = DB::all('SELECT s.*, w.name, w.label FROM workout_sessions s JOIN workouts w ON w.id = s.workout_id WHERE s.student_id = ? ORDER BY s.scheduled_date DESC, s.id DESC LIMIT 8', [$id]);
                $data['cycles'] = DB::all('SELECT * FROM student_cycles WHERE student_id = ? ORDER BY number DESC', [$id]);
                $data['today'] = Schedule::workoutsOn($id, today());
                $data['lastSelf'] = DB::one('SELECT * FROM self_assessments WHERE student_id = ? ORDER BY ref_date DESC LIMIT 1', [$id]);
                break;
            case 'treinos':
                $data['workouts'] = Workouts::forStudent($id);
                $data['archived'] = DB::all('SELECT w.*, m.name AS modality_name FROM workouts w JOIN modalities m ON m.id = w.modality_id WHERE w.student_id = ? AND w.archived_at IS NOT NULL ORDER BY w.archived_at DESC', [$id]);
                $data['templates'] = Workouts::templates();
                $data['sessions'] = DB::all('SELECT s.*, w.name, w.label, v.version_no,
                        (SELECT COUNT(*) FROM session_entries e WHERE e.session_id = s.id AND e.completed = 1) AS done,
                        (SELECT COUNT(*) FROM workout_items i WHERE i.version_id = s.version_id) AS total_items
                    FROM workout_sessions s JOIN workouts w ON w.id = s.workout_id JOIN workout_versions v ON v.id = s.version_id
                    WHERE s.student_id = ? ORDER BY s.scheduled_date DESC, s.id DESC LIMIT 30', [$id]);
                $data['requests'] = DB::all("SELECT r.*, w.name, w.label FROM reschedule_requests r JOIN workouts w ON w.id = r.workout_id WHERE r.student_id = ? ORDER BY r.id DESC LIMIT 10", [$id]);
                break;
            case 'progresso':
                $data['sessions'] = DB::all('SELECT s.*, w.name, w.label FROM workout_sessions s JOIN workouts w ON w.id = s.workout_id WHERE s.student_id = ? ORDER BY s.scheduled_date DESC, s.id DESC LIMIT 8', [$id]);
                $data['total'] = Progress::totalCompleted($id);
                $data['streak'] = Progress::streak($id, today());
                $data['heat'] = Progress::heatmap($id, today());
                $data['series'] = Progress::selfSeries($id);
                $data['paths'] = Progress::chartPaths($data['series']);
                $data['selfs'] = DB::all('SELECT * FROM self_assessments WHERE student_id = ? ORDER BY ref_date DESC LIMIT 60', [$id]);
                $data['dueDate'] = Cron::selfEvalDueDate(['id' => $id, 'self_eval_every_days' => $profile['self_eval_every_days'], 'onboarding_completed_at' => $profile['onboarding_completed_at']]);
                $data['assessments'] = DB::all("SELECT * FROM physical_assessments WHERE student_id = ? AND status <> 'draft' ORDER BY submitted_at DESC", [$id]);
                break;
            case 'avaliacoes':
                $data['assessments'] = DB::all("SELECT * FROM physical_assessments WHERE student_id = ? AND status <> 'draft' ORDER BY submitted_at DESC", [$id]);
                break;
            case 'autoavaliacoes':
                $data['selfs'] = DB::all('SELECT * FROM self_assessments WHERE student_id = ? ORDER BY ref_date DESC LIMIT 60', [$id]);
                $data['dueDate'] = Cron::selfEvalDueDate(['id' => $id, 'self_eval_every_days' => $profile['self_eval_every_days'], 'onboarding_completed_at' => $profile['onboarding_completed_at']]);
                break;
            case 'calendario':
                $view = input('ver') === 'mes' ? 'month' : 'week';
                $date = (string) input('data', today());
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = today();
                $selected = (string) input('dia', $date);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected)) $selected = $date;
                [$start, $end, $prev, $next] = CalendarController::window($view, $date);
                $data += ['view' => $view, 'date' => $date, 'selected' => $selected, 'start' => $start, 'end' => $end, 'prev' => $prev, 'next' => $next,
                    'days' => Schedule::range($id, $start, $end), 'selectedItems' => Schedule::range($id, $selected, $selected)[$selected] ?? [],
                    'types' => CalendarController::EVENT_TYPES, 'editEvent' => ($eid = input_int('evento')) ? DB::one('SELECT * FROM calendar_events WHERE id = ? AND student_id = ?', [$eid, $id]) : null];
                break;
            case 'dados':
                $data['anamnesis'] = Anamnesis::latestSubmitted($id) ?? (DB::one('SELECT * FROM anamneses WHERE student_id = ? ORDER BY id DESC LIMIT 1', [$id]) ?: null);
                if ($data['anamnesis'] && !isset($data['anamnesis']['answers'])) $data['anamnesis']['answers'] = json_decode_array($data['anamnesis']['data']);
                $data['sections'] = Anamnesis::sections();
                $data['photos'] = $data['anamnesis'] ? Files::forContext('anamnesis', (int) $data['anamnesis']['id']) : [];
                $data['history'] = DB::all("SELECT id, cycle, submitted_at FROM anamneses WHERE student_id = ? AND status = 'submitted' ORDER BY id DESC", [$id]);
                $data['subs'] = DB::all('SELECT s.*, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.student_id = ? ORDER BY s.id DESC LIMIT 10', [$id]);
                break;
        }
        if ($tab === 'dados' && input('anamnese')) {
            $a = DB::one("SELECT * FROM anamneses WHERE id = ? AND student_id = ?", [(int) input('anamnese'), $id]);
            if ($a) {
                $a['answers'] = json_decode_array($a['data']);
                $data['anamnesis'] = $a;
                $data['photos'] = Files::forContext('anamnesis', (int) $a['id']);
            }
        }
        View::render('trainer/student_show', $data);
    }

    public function approve(int $id): void
    {
        try {
            Students::approve($id, (string) input('note', ''));
            flash('success', 'Cadastro aprovado. O aluno foi avisado por e-mail.');
        } catch (UserError $e) {
            flash('error', $e->getMessage());
        }
        back("/painel/alunos/{$id}");
    }

    public function reject(int $id): void
    {
        try {
            Students::reject($id, (string) input('note', ''));
            flash('success', 'Cadastro recusado. O usuário foi avisado por e-mail.');
        } catch (UserError $e) {
            flash('error', $e->getMessage());
        }
        back("/painel/alunos/{$id}");
    }

    public function status(int $id): void
    {
        try {
            Students::setStatus($id, (string) input('status'));
            flash('success', 'Situação atualizada.');
        } catch (UserError $e) {
            flash('error', $e->getMessage());
        }
        redirect("/painel/alunos/{$id}");
    }

    public function reactivate(int $id): void
    {
        try {
            Students::reactivate($id, (bool) input('new_cycle'), (bool) input('new_onboarding'));
            flash('success', 'Aluno reativado.' . (input('new_cycle') ? ' Novo ciclo iniciado; histórico anterior preservado.' : ''));
        } catch (UserError $e) {
            flash('error', $e->getMessage());
        }
        redirect("/painel/alunos/{$id}");
    }

    public function settings(int $id): void
    {
        Students::findOrFail($id);
        $cats = array_values(array_intersect((array) ($_POST['categories'] ?? []), array_keys(Students::SELF_EVAL_CATEGORIES)));
        if (!$cats) {
            flash('error', 'Selecione pelo menos uma categoria de autoavaliação.');
            redirect("/painel/alunos/{$id}");
        }
        $next = (string) input('next_assessment_on', '');
        $upd = [
            'self_eval_every_days' => max(1, min(60, (int) input('self_eval_every_days', 7))),
            'self_eval_categories' => json_enc($cats),
            'assessment_every_days' => max(7, min(365, (int) input('assessment_every_days', 30))),
            'assessment_remind_days_before' => max(0, min(30, (int) input('assessment_remind_days_before', 3))),
            'assessment_reminders_count' => max(1, min(5, (int) input('assessment_reminders_count', 2))),
            'assessment_overdue_alert' => (int) (bool) input('assessment_overdue_alert'),
            'modality_id' => input_int('modality_id'),
            'access_override' => input('access_override') === 'allow' ? 'allow' : null,
            'trainer_notes' => mb_substr(trim((string) input('trainer_notes', '')), 0, 6000) ?: null,
            'updated_at' => now(),
        ];
        if (array_key_exists('next_assessment_on', $_POST)) {
            $upd['next_assessment_on'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $next) ? $next : null;
        }
        DB::update('student_profiles', $upd, 'user_id = ?', [$id]);
        Audit::log('student.settings', 'user', $id);
        flash('success', 'Configurações do aluno salvas.');
        redirect("/painel/alunos/{$id}");
    }

    public function purge(int $id): void
    {
        $s = Students::findOrFail($id);
        if (mb_strtolower(trim((string) input('confirm_email', ''))) !== mb_strtolower($s['email'])) {
            flash('error', 'Para excluir definitivamente, digite o e-mail do aluno exatamente como cadastrado.');
            redirect("/painel/alunos/{$id}?aba=dados");
        }
        if ($s['role'] !== 'student') abort(403);
        Students::purge($id);
        DB::run("UPDATE data_requests SET status = 'done', resolved_at = ? WHERE user_id = ?", [now(), $id]);
        flash('success', 'Todos os dados e arquivos do aluno foram excluídos definitivamente.');
        redirect('/painel/alunos');
    }

    public function sessionDetail(int $id, int $sid): void
    {
        $s = DB::one('SELECT * FROM workout_sessions WHERE id = ? AND student_id = ?', [$sid, $id]) ?? abort(404);
        $w = Workouts::findOrFail((int) $s['workout_id']);
        $modality = Modalities::find((int) $w['modality_id']);
        $entries = [];
        foreach (DB::all('SELECT * FROM session_entries WHERE session_id = ?', [$sid]) as $e) {
            $e['data'] = json_decode_array($e['data']);
            $entries[(int) $e['item_id']] = $e;
        }
        View::render('trainer/session_detail', [
            'title' => 'Registro de treino', 'nav' => 'students', 'student' => Students::find($id), 'session' => $s, 'workout' => $w,
            'version' => DB::one('SELECT * FROM workout_versions WHERE id = ?', [$s['version_id']]),
            'items' => Workouts::decodeItems(Workouts::items((int) $s['version_id']), $modality), 'entries' => $entries,
        ]);
    }
}
