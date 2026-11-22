<?php
declare(strict_types=1);

final class StudentController
{
    public function dashboard(): void
    {
        $uid = (int) Auth::id();
        $today = today();
        $profile = Students::profile($uid);
        $todays = Schedule::workoutsOn($uid, $today);
        $hasAny = Schedule::hasPublishedWorkout($uid);
        $inProgress = DB::one("SELECT s.*, w.name, w.label FROM workout_sessions s JOIN workouts w ON w.id = s.workout_id
            WHERE s.student_id = ? AND s.status = 'in_progress' AND s.scheduled_date <> ? ORDER BY s.updated_at DESC LIMIT 1", [$uid, $today]);
        $selfDue = Cron::selfEvalDueDate(['id' => $uid, 'self_eval_every_days' => $profile['self_eval_every_days'], 'onboarding_completed_at' => $profile['onboarding_completed_at']]);
        View::render('student/dashboard', [
            'title' => 'Início',
            'nav' => 'home',
            'profile' => $profile,
            'todays' => $todays,
            'hasAny' => $hasAny,
            'next' => $todays ? null : Schedule::nextWorkout($uid, $today),
            'inProgress' => $inProgress,
            'nextEvent' => Schedule::nextEvent($uid, $today),
            'selfDue' => $selfDue,
            'selfDoneToday' => (bool) DB::val('SELECT id FROM self_assessments WHERE student_id = ? AND ref_date = ?', [$uid, $today]),
            'unread' => (int) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND read_at IS NULL AND (sender_id IS NULL OR sender_id <> ?)', [$uid, $uid]),
            'pendingAssessment' => DB::one("SELECT * FROM physical_assessments WHERE student_id = ? AND status = 'submitted' ORDER BY id DESC LIMIT 1", [$uid]),
        ]);
    }

    public function situation(): void
    {
        $u = Auth::user();
        $p = Students::profile((int) $u['id']);
        if ($u['status'] === 'active' && $p['onboarding_completed_at'] && Billing::hasAccess((int) $u['id'])) {
            redirect('/app');
        }
        View::render('student/situation', [
            'title' => 'Situação da conta',
            'nav' => '',
            'user' => $u,
            'billingBlocked' => $u['status'] === 'active' && !Billing::hasAccess((int) $u['id']),
            'standing' => Billing::standing((int) $u['id']),
        ]);
    }
}
