<?php
declare(strict_types=1);

final class SelfAssessmentController
{
    public function index(): void
    {
        $uid = (int) Auth::id();
        $p = Students::profile($uid);
        $date = (string) input('data', today());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > today()) $date = today();
        View::render('student/self_assessment', [
            'title' => 'Autoavaliação', 'nav' => 'self',
            'profile' => $p, 'date' => $date,
            'categories' => array_intersect_key(Students::SELF_EVAL_CATEGORIES, array_flip($p['categories'])),
            'current' => DB::one('SELECT * FROM self_assessments WHERE student_id = ? AND ref_date = ?', [$uid, $date]),
            'history' => DB::all('SELECT * FROM self_assessments WHERE student_id = ? ORDER BY ref_date DESC LIMIT 12', [$uid]),
            'dueDate' => Cron::selfEvalDueDate(['id' => $uid, 'self_eval_every_days' => $p['self_eval_every_days'], 'onboarding_completed_at' => $p['onboarding_completed_at']]),
        ]);
    }

    public function save(): void
    {
        $uid = (int) Auth::id();
        $p = Students::profile($uid);
        $date = (string) input('date', today());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > today() || $date < date_add_days(today(), -14)) {
            flash('error', 'Data inválida (é possível registrar até 14 dias atrás).');
            redirect('/app/autoavaliacao');
        }
        $scores = [];
        foreach ($p['categories'] as $cat) {
            $v = (int) ($_POST['scores'][$cat] ?? 0);
            if ($v < 1 || $v > 5) {
                flash('error', 'Dê uma nota de 1 a 5 para: ' . (Students::SELF_EVAL_CATEGORIES[$cat] ?? $cat) . '.');
                redirect('/app/autoavaliacao?data=' . $date);
            }
            $scores[$cat] = $v;
        }
        $notes = mb_substr(trim((string) input('notes', '')), 0, 3000) ?: null;
        $id = DB::val('SELECT id FROM self_assessments WHERE student_id = ? AND ref_date = ?', [$uid, $date]);
        if ($id) {
            DB::update('self_assessments', ['scores' => json_enc($scores), 'notes' => $notes, 'updated_at' => now()], 'id = ?', [$id]);
        } else {
            DB::insert('self_assessments', ['student_id' => $uid, 'ref_date' => $date, 'scores' => json_enc($scores), 'notes' => $notes, 'created_at' => now(), 'updated_at' => now()]);
        }
        flash('success', 'Autoavaliação salva. Obrigado!');
        redirect('/app/autoavaliacao');
    }

    public function reminderTime(): void
    {
        $t = (string) input('time', '');
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) {
            flash('error', 'Horário inválido.');
            redirect('/app/autoavaliacao');
        }
        DB::update('student_profiles', ['self_eval_reminder_time' => $t, 'updated_at' => now()], 'user_id = ?', [Auth::id()]);
        flash('success', 'Horário do lembrete atualizado.');
        redirect('/app/autoavaliacao');
    }
}
