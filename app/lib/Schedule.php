<?php
declare(strict_types=1);

/** Programação do aluno: treinos por dia da semana + mudanças aprovadas + eventos do calendário. */
final class Schedule
{
    /** Treinos publicados previstos para uma data (considera mudanças de dia aprovadas). */
    public static function workoutsOn(int $studentId, string $date): array
    {
        $dow = (int) (new DateTimeImmutable($date))->format('w');
        $out = [];
        $movedAway = DB::col('SELECT workout_id FROM schedule_overrides WHERE student_id = ? AND original_date = ?', [$studentId, $date]);
        $movedHere = DB::all('SELECT workout_id, original_date FROM schedule_overrides WHERE student_id = ? AND new_date = ?', [$studentId, $date]);
        $movedHereIds = array_map(fn($r) => (int) $r['workout_id'], $movedHere);
        $all = DB::all("SELECT w.*, m.name AS modality_name FROM workouts w JOIN modalities m ON m.id = w.modality_id
            WHERE w.student_id = ? AND w.is_template = 0 AND w.archived_at IS NULL ORDER BY w.sort_order, w.label, w.id", [$studentId]);
        foreach ($all as $w) {
            $wid = (int) $w['id'];
            $inRange = (!$w['starts_on'] || $w['starts_on'] <= $date) && (!$w['ends_on'] || $w['ends_on'] >= $date);
            $regular = in_array($dow, Workouts::weekdays($w), true) && $inRange && !in_array($wid, array_map('intval', $movedAway), true);
            $moved = in_array($wid, $movedHereIds, true);
            if (!$regular && !$moved) continue;
            $pub = Workouts::published($wid);
            if (!$pub) continue;
            $w['version'] = $pub;
            $w['moved_from'] = null;
            foreach ($movedHere as $mh) {
                if ((int) $mh['workout_id'] === $wid) $w['moved_from'] = $mh['original_date'];
            }
            $w['session'] = DB::one('SELECT * FROM workout_sessions WHERE student_id = ? AND workout_id = ? AND scheduled_date = ?', [$studentId, $wid, $date]);
            $out[] = $w;
        }
        return $out;
    }

    public static function hasPublishedWorkout(int $studentId): bool
    {
        return (bool) DB::val("SELECT COUNT(*) FROM workouts w JOIN workout_versions v ON v.workout_id = w.id
            WHERE w.student_id = ? AND w.is_template = 0 AND w.archived_at IS NULL AND v.status = 'published'", [$studentId]);
    }

    /** Próxima data (após $from) com treino previsto. */
    public static function nextWorkout(int $studentId, string $from, int $horizon = 21): ?array
    {
        for ($i = 1; $i <= $horizon; $i++) {
            $d = date_add_days($from, $i);
            $list = self::workoutsOn($studentId, $d);
            if ($list) {
                return ['date' => $d, 'workout' => $list[0]];
            }
        }
        return null;
    }

    public static function events(int $studentId, string $from, string $to): array
    {
        return DB::all('SELECT * FROM calendar_events WHERE student_id = ? AND event_date BETWEEN ? AND ? ORDER BY event_date, start_time IS NULL, start_time, id', [$studentId, $from, $to]);
    }

    public static function nextEvent(int $studentId, string $from): ?array
    {
        return DB::one("SELECT * FROM calendar_events WHERE student_id = ? AND event_date >= ? AND type <> 'rest' ORDER BY event_date, start_time IS NULL, start_time LIMIT 1", [$studentId, $from]);
    }

    /**
     * Itens do calendário entre duas datas, agrupados por dia:
     * treinos (calculados), eventos oficiais, avaliação física prevista.
     */
    public static function range(int $studentId, string $from, string $to): array
    {
        $days = [];
        for ($d = $from; $d <= $to; $d = date_add_days($d, 1)) {
            $days[$d] = [];
            foreach (self::workoutsOn($studentId, $d) as $w) {
                $days[$d][] = [
                    'type' => 'workout', 'title' => Workouts::title($w), 'subtitle' => $w['modality_name'],
                    'workout_id' => (int) $w['id'], 'status' => $w['session']['status'] ?? null, 'moved_from' => $w['moved_from'],
                ];
            }
        }
        foreach (self::events($studentId, $from, $to) as $ev) {
            $days[$ev['event_date']][] = [
                'type' => $ev['type'], 'title' => $ev['title'], 'subtitle' => trim(($ev['start_time'] ?? '') . ($ev['end_time'] ? '–' . $ev['end_time'] : '')),
                'description' => $ev['description'], 'event_id' => (int) $ev['id'], 'start_time' => $ev['start_time'], 'end_time' => $ev['end_time'],
            ];
        }
        $p = Students::profile($studentId);
        if ($p['next_assessment_on'] && $p['next_assessment_on'] >= $from && $p['next_assessment_on'] <= $to) {
            $days[$p['next_assessment_on']][] = ['type' => 'assessment', 'title' => 'Avaliação física', 'subtitle' => 'Envio de fotos'];
        }
        return $days;
    }

    public static function typeLabel(string $type): string
    {
        return [
            'workout' => 'Treino', 'assessment' => 'Avaliação física', 'activity' => 'Atividade esportiva',
            'appointment' => 'Compromisso', 'rest' => 'Descanso',
        ][$type] ?? ucfirst($type);
    }

    public static function sessionStatusLabel(?string $s): string
    {
        return [
            'in_progress' => 'Em andamento', 'completed' => 'Realizado', 'postponed' => 'Adiado', 'skipped' => 'Não realizado',
        ][$s ?? ''] ?? 'Previsto';
    }
}
