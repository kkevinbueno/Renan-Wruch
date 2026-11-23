<?php
declare(strict_types=1);

/** Números de acompanhamento do aluno: semana, sequência de treinos, mapa de dias e evolução das autoavaliações. */
final class Progress
{
    public const WATER_GOAL_ML = 2500;

    /** Semana atual (segunda a domingo) com treinos previstos e realizados. */
    public static function week(int $studentId, string $today): array
    {
        $dow = (int) (new DateTimeImmutable($today))->format('N'); // 1 = segunda
        $monday = date_add_days($today, -($dow - 1));
        $done = self::completedDates($studentId, $monday, date_add_days($monday, 6));
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date_add_days($monday, $i);
            $list = Schedule::workoutsOn($studentId, $d);
            $days[] = [
                'date' => $d,
                'wd' => ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'][$i],
                'n' => (int) substr($d, 8, 2),
                'tones' => array_map(fn($w) => mod_tone((int) $w['modality_id']), $list),
                'planned' => count($list),
                'done' => $done[$d] ?? 0,
            ];
        }
        return $days;
    }

    /** Datas com treino concluído => quantidade. */
    public static function completedDates(int $studentId, string $from, string $to): array
    {
        $out = [];
        foreach (DB::all("SELECT scheduled_date AS d, COUNT(*) AS n FROM workout_sessions WHERE student_id = ? AND status = 'completed' AND scheduled_date BETWEEN ? AND ? GROUP BY scheduled_date", [$studentId, $from, $to]) as $r) {
            $out[$r['d']] = (int) $r['n'];
        }
        return $out;
    }

    /**
     * Treinos seguidos: conta os dias de treino concluídos de trás para frente;
     * dias de descanso não quebram a sequência, um treino previsto e não feito quebra.
     */
    public static function streak(int $studentId, string $today, int $horizon = 60): int
    {
        $done = self::completedDates($studentId, date_add_days($today, -$horizon), $today);
        if (!$done) return 0;
        $streak = 0;
        for ($i = 0; $i <= $horizon; $i++) {
            $d = date_add_days($today, -$i);
            if (!empty($done[$d])) { $streak++; continue; }
            if ($i === 0) continue; // hoje ainda pode treinar
            if (Schedule::workoutsOn($studentId, $d)) break;
            if ($d < min(array_keys($done))) break;
        }
        return $streak;
    }

    public static function totalCompleted(int $studentId): int
    {
        return (int) DB::val("SELECT COUNT(*) FROM workout_sessions WHERE student_id = ? AND status = 'completed'", [$studentId]);
    }

    /** Mapa das últimas N semanas: nível 0–3 por dia (treino feito, autoavaliação, registro de alimentação). */
    public static function heatmap(int $studentId, string $today, int $weeks = 14): array
    {
        $dow = (int) (new DateTimeImmutable($today))->format('N');
        $start = date_add_days($today, -($dow - 1) - ($weeks - 1) * 7);
        $done = self::completedDates($studentId, $start, $today);
        $self = array_flip(DB::col('SELECT ref_date FROM self_assessments WHERE student_id = ? AND ref_date >= ?', [$studentId, $start]));
        $food = array_flip(DB::col('SELECT DISTINCT ref_date FROM nutrition_logs WHERE student_id = ? AND ref_date >= ?', [$studentId, $start]));
        $cells = [];
        for ($d = $start; $d <= date_add_days($start, $weeks * 7 - 1); $d = date_add_days($d, 1)) {
            if ($d > $today) { $cells[] = ['date' => $d, 'level' => -1]; continue; }
            $lvl = !empty($done[$d]) ? 3 : (isset($self[$d]) && isset($food[$d]) ? 2 : ((isset($self[$d]) || isset($food[$d])) ? 1 : 0));
            $cells[] = ['date' => $d, 'level' => $lvl];
        }
        return $cells;
    }

    /** Média das autoavaliações (1–5) ao longo do tempo, das mais antigas para as mais recentes. */
    public static function selfSeries(int $studentId, int $limit = 12): array
    {
        $rows = array_reverse(DB::all('SELECT ref_date, scores FROM self_assessments WHERE student_id = ? ORDER BY ref_date DESC LIMIT ' . (int) $limit, [$studentId]));
        $out = [];
        foreach ($rows as $r) {
            $s = array_filter(array_map('intval', json_decode_array($r['scores'])), fn($v) => $v >= 1 && $v <= 5);
            if ($s) $out[] = ['date' => $r['ref_date'], 'avg' => round(array_sum($s) / count($s), 2)];
        }
        return $out;
    }

    /** Caminho SVG (linha e área) para a série de autoavaliações. */
    public static function chartPaths(array $series, int $w = 340, int $h = 120): array
    {
        $n = count($series);
        if ($n < 2) return ['line' => '', 'area' => ''];
        $pts = [];
        foreach ($series as $i => $p) {
            $x = round(6 + $i * ($w - 12) / ($n - 1), 1);
            $y = round($h - 8 - ($p['avg'] - 1) / 4 * ($h - 20), 1);
            $pts[] = "$x,$y";
        }
        $line = 'M' . implode(' L', $pts);
        $area = $line . ' L' . ($w - 6) . ',' . $h . ' L6,' . $h . ' Z';
        return ['line' => $line, 'area' => $area];
    }

    public static function waterToday(int $studentId, string $today): int
    {
        return (int) DB::val('SELECT COALESCE(SUM(water_ml),0) FROM nutrition_logs WHERE student_id = ? AND ref_date = ?', [$studentId, $today]);
    }
}
