<?php
declare(strict_types=1);

final class CalendarController
{
    public const EVENT_TYPES = ['activity' => 'Atividade esportiva', 'appointment' => 'Compromisso da consultoria', 'assessment' => 'Avaliação física', 'rest' => 'Dia de descanso', 'workout' => 'Treino extra'];

    /** Calcula intervalo exibido: semana (padrão) ou mês. */
    public static function window(string $view, string $date): array
    {
        $d = new DateTimeImmutable($date);
        if ($view === 'month') {
            $first = $d->modify('first day of this month');
            $start = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
            $last = $d->modify('last day of this month');
            $end = $last->modify('+' . (7 - (int) $last->format('N')) . ' days');
            return [$start->format('Y-m-d'), $end->format('Y-m-d'), $d->modify('first day of previous month')->format('Y-m-d'), $d->modify('first day of next month')->format('Y-m-d')];
        }
        $start = $d->modify('-' . ((int) $d->format('N') - 1) . ' days');
        return [$start->format('Y-m-d'), $start->modify('+6 days')->format('Y-m-d'), $start->modify('-7 days')->format('Y-m-d'), $start->modify('+7 days')->format('Y-m-d')];
    }

    private function params(): array
    {
        $view = input('ver') === 'mes' ? 'month' : 'week';
        $date = (string) input('data', today());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) $date = today();
        $selected = (string) input('dia', $date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected)) $selected = $date;
        return [$view, $date, $selected];
    }

    public function student(): void
    {
        [$view, $date, $selected] = $this->params();
        [$start, $end, $prev, $next] = self::window($view, $date);
        $uid = (int) Auth::id();
        View::render('student/calendar', [
            'title' => 'Calendário', 'nav' => 'calendar', 'view' => $view, 'date' => $date, 'selected' => $selected,
            'start' => $start, 'end' => $end, 'prev' => $prev, 'next' => $next,
            'days' => Schedule::range($uid, $start, $end), 'selectedItems' => Schedule::range($uid, $selected, $selected)[$selected] ?? [],
            'workouts' => array_filter(Workouts::forStudent($uid), fn($w) => $w['published']),
            'base' => '/app/calendario', 'studentId' => $uid, 'canEdit' => false,
        ]);
    }

    public function trainer(): void
    {
        [$view, $date, $selected] = $this->params();
        [$start, $end, $prev, $next] = self::window($view, $date);
        $students = DB::all("SELECT id, name FROM users WHERE role = 'student' AND status IN ('active','paused') ORDER BY name");
        $sid = input_int('aluno') ?? ($students[0]['id'] ?? null);
        $student = $sid ? Students::find((int) $sid) : null;
        View::render('trainer/calendar', [
            'title' => 'Calendário', 'nav' => 'calendar', 'view' => $view, 'date' => $date, 'selected' => $selected,
            'start' => $start, 'end' => $end, 'prev' => $prev, 'next' => $next, 'students' => $students, 'student' => $student,
            'days' => $student ? Schedule::range((int) $student['id'], $start, $end) : [],
            'selectedItems' => $student ? (Schedule::range((int) $student['id'], $selected, $selected)[$selected] ?? []) : [],
            'week' => self::weekOverview($start, $end),
            'base' => '/painel/calendario', 'studentId' => $student['id'] ?? null, 'canEdit' => true, 'types' => self::EVENT_TYPES,
            'editEvent' => ($eid = input_int('evento')) ? DB::one('SELECT * FROM calendar_events WHERE id = ?', [$eid]) : null,
        ]);
    }

    /** Visão geral de eventos de todos os alunos no período (para o Renan). */
    private static function weekOverview(string $start, string $end): array
    {
        return DB::all("SELECT e.*, u.name FROM calendar_events e JOIN users u ON u.id = e.student_id WHERE e.event_date BETWEEN ? AND ? AND e.type <> 'rest' ORDER BY e.event_date, e.start_time IS NULL, e.start_time LIMIT 50", [$start, $end]);
    }

    public function saveEvent(): void
    {
        $sid = (int) input('student_id');
        $student = Students::findOrFail($sid);
        $type = (string) input('type');
        $date = (string) input('event_date');
        $title = trim((string) input('title', ''));
        $start = (string) input('start_time', '');
        $end = (string) input('end_time', '');
        $back = '/painel/calendario?' . http_build_query(['aluno' => $sid, 'data' => $date, 'dia' => $date]);
        if (!isset(self::EVENT_TYPES[$type]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            flash('error', 'Informe o tipo e a data do evento.');
            redirect($back);
        }
        if ($title === '') $title = self::EVENT_TYPES[$type];
        foreach ([$start, $end] as $t) {
            if ($t !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) {
                flash('error', 'Horário inválido.');
                redirect($back);
            }
        }
        $data = [
            'student_id' => $sid, 'type' => $type, 'title' => mb_substr($title, 0, 150),
            'description' => mb_substr(trim((string) input('description', '')), 0, 2000) ?: null,
            'event_date' => $date, 'start_time' => $start ?: null, 'end_time' => $end ?: null, 'updated_at' => now(),
        ];
        $id = input_int('id');
        if ($id) {
            $old = DB::one('SELECT * FROM calendar_events WHERE id = ?', [$id]) ?? abort(404);
            DB::update('calendar_events', $data, 'id = ?', [$id]);
            $summary = $data['title'] . ' em ' . fmt_date($date);
            Audit::log('calendar.update', 'calendar_event', $id);
        } else {
            $id = DB::insert('calendar_events', $data + ['created_by' => Auth::id(), 'created_at' => now()]);
            $summary = 'novo evento "' . $data['title'] . '" em ' . fmt_date($date);
            Audit::log('calendar.create', 'calendar_event', $id);
        }
        if ($student['status'] === 'active' && input('notify') === '1') {
            Notify::send($sid, 'calendar_changed', ['summary' => $summary], ['dedupe' => "cal:{$id}:" . md5(json_enc($data)), 'pref' => 'calendar_changes']);
        }
        flash('success', 'Evento salvo. O aluno já vê a alteração no calendário.');
        redirect(input('return') === 'perfil' ? "/painel/alunos/{$sid}?aba=calendario&data={$date}&dia={$date}" : $back);
    }

    public function deleteEvent(int $id): void
    {
        $e = DB::one('SELECT * FROM calendar_events WHERE id = ?', [$id]) ?? abort(404);
        DB::delete('calendar_events', 'id = ?', [$id]);
        Audit::log('calendar.delete', 'calendar_event', $id, ['title' => $e['title'], 'date' => $e['event_date']]);
        Notify::send((int) $e['student_id'], 'calendar_changed', ['summary' => '"' . $e['title'] . '" de ' . fmt_date($e['event_date']) . ' foi removido'], ['dedupe' => "caldel:{$id}", 'pref' => 'calendar_changes']);
        flash('success', 'Evento removido.');
        redirect('/painel/calendario?' . http_build_query(['aluno' => $e['student_id'], 'data' => $e['event_date'], 'dia' => $e['event_date']]));
    }
}
