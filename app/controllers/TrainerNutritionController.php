<?php
declare(strict_types=1);

/**
 * Aba "Alimentação" do Renan: acompanhamento simples de refeições e hidratação dos alunos
 * (sem prescrição nutricional). Os registros vêm do que o aluno anota na própria área.
 */
final class TrainerNutritionController
{
    /** Agrupamento das refeições exibido ao Renan. */
    public const GROUPS = [
        'cafe' => 'Café da manhã', 'lanche_manha' => 'Lanche da manhã', 'almoco' => 'Almoço',
        'lanche_tarde' => 'Lanche da tarde', 'jantar' => 'Jantar', 'outras' => 'Outras refeições',
    ];
    public const PERIODS = [7 => '7 dias', 30 => '30 dias', 90 => '90 dias'];

    public static function groupOf(?string $meal): string
    {
        return isset(self::GROUPS[(string) $meal]) && $meal !== 'outras' ? (string) $meal : 'outras';
    }

    private static function students(): array
    {
        return DB::all("SELECT u.id, u.name, u.status,
                (SELECT MAX(n.ref_date) FROM nutrition_logs n WHERE n.student_id = u.id) AS last_log
            FROM users u WHERE u.role = 'student' AND u.status IN ('active','paused') ORDER BY u.name");
    }

    public function index(): void
    {
        $students = self::students();
        $sid = input_int('aluno');
        if ($sid && !Students::find($sid)) abort(404, 'Aluno não encontrado.');
        if (!$sid && $students) {
            // Sem aluno escolhido: começa por quem registrou mais recentemente.
            $withLog = array_filter($students, fn($s) => $s['last_log']);
            usort($withLog, fn($a, $b) => strcmp((string) $b['last_log'], (string) $a['last_log']));
            $sid = (int) (($withLog[0] ?? $students[0])['id']);
        }
        $period = (int) input('periodo', 30);
        if (!isset(self::PERIODS[$period])) $period = 30;
        $view = input('ver') === 'hidratacao' ? 'hidratacao' : 'refeicoes';
        $end = today();
        $start = date_add_days($end, -($period - 1));
        $data = [
            'title' => 'Alimentação', 'nav' => 'nutrition', 'students' => $students, 'sid' => $sid,
            'student' => $sid ? Students::find($sid) : null, 'period' => $period, 'view' => $view,
            'start' => $start, 'end' => $end, 'goal' => Progress::WATER_GOAL_ML,
        ];
        if ($sid) {
            $logs = DB::all('SELECT * FROM nutrition_logs WHERE student_id = ? AND ref_date BETWEEN ? AND ? ORDER BY ref_date DESC, created_at DESC, id DESC', [$sid, $start, $end]);
            $data['notes'] = DB::all('SELECT n.*, u.name AS author FROM nutrition_notes n LEFT JOIN users u ON u.id = n.author_id WHERE n.student_id = ? AND n.topic = ? ORDER BY n.id DESC LIMIT 30', [$sid, $view]);
            $selfKey = $view === 'hidratacao' ? 'hidratacao' : 'alimentacao';
            $selfs = [];
            foreach (DB::all('SELECT ref_date, scores, notes FROM self_assessments WHERE student_id = ? AND ref_date BETWEEN ? AND ? ORDER BY ref_date DESC', [$sid, $start, $end]) as $r) {
                $sc = json_decode_array($r['scores']);
                if (isset($sc[$selfKey])) $selfs[] = ['date' => $r['ref_date'], 'score' => (int) $sc[$selfKey]];
            }
            $data['selfs'] = $selfs;
            $data['selfKey'] = $selfKey;
            $view === 'hidratacao' ? $data += self::hydration($logs, $start, $end) : $data += self::meals($logs, $period);
        }
        View::render('trainer/nutrition', $data);
    }

    /** Refeições por dia, por categoria, e regularidade dos registros. */
    private static function meals(array $logs, int $period): array
    {
        $days = [];
        $coverage = array_fill_keys(array_keys(self::GROUPS), []);
        foreach ($logs as $r) {
            if ($r['meal'] === 'agua' && !$r['description']) continue; // só água: aparece em Hidratação
            $g = self::groupOf($r['meal']);
            $days[$r['ref_date']][$g][] = $r;
            $coverage[$g][$r['ref_date']] = true;
        }
        $mealCount = array_sum(array_map(fn($d) => array_sum(array_map('count', $d)), $days));
        return [
            'days' => $days,
            'coverage' => array_map('count', $coverage),
            'daysWithMeals' => count($days),
            'avgMeals' => $days ? round($mealCount / count($days), 1) : 0,
            'mealCount' => $mealCount,
        ];
    }

    /** Totais de água por dia, semana e mês dentro do período. */
    private static function hydration(array $logs, string $start, string $end): array
    {
        $daily = [];
        for ($d = $start; $d <= $end; $d = date_add_days($d, 1)) $daily[$d] = 0;
        $entries = [];
        foreach ($logs as $r) {
            if (!$r['water_ml']) continue;
            $daily[$r['ref_date']] = ($daily[$r['ref_date']] ?? 0) + (int) $r['water_ml'];
            $entries[] = $r;
        }
        $weekly = $monthly = [];
        foreach ($daily as $d => $ml) {
            $dt = new DateTimeImmutable($d);
            $wk = $dt->modify('monday this week')->format('Y-m-d');
            $mo = $dt->format('Y-m');
            $weekly[$wk] ??= ['total' => 0, 'days' => 0, 'logged' => 0];
            $weekly[$wk]['total'] += $ml; $weekly[$wk]['days']++; $weekly[$wk]['logged'] += $ml > 0 ? 1 : 0;
            $monthly[$mo] ??= ['total' => 0, 'days' => 0, 'logged' => 0];
            $monthly[$mo]['total'] += $ml; $monthly[$mo]['days']++; $monthly[$mo]['logged'] += $ml > 0 ? 1 : 0;
        }
        $logged = array_filter($daily);
        return [
            'daily' => $daily, 'entries' => $entries,
            'weekly' => array_reverse($weekly, true), 'monthly' => array_reverse($monthly, true),
            'daysLogged' => count($logged),
            'avgLogged' => $logged ? (int) round(array_sum($logged) / count($logged)) : 0,
            'daysGoal' => count(array_filter($daily, fn($ml) => $ml >= Progress::WATER_GOAL_ML)),
        ];
    }

    public function addNote(): void
    {
        $sid = (int) input('student_id');
        Students::find($sid) ?? abort(404, 'Aluno não encontrado.');
        $topic = input('topic') === 'hidratacao' ? 'hidratacao' : 'refeicoes';
        $body = mb_substr(trim((string) input('body', '')), 0, 2000);
        $back = '/painel/alimentacao?' . http_build_query(['aluno' => $sid, 'ver' => $topic, 'periodo' => input_int('periodo') ?? 30]);
        if ($body === '') {
            flash('error', 'Escreva a observação.');
            redirect($back);
        }
        DB::insert('nutrition_notes', ['student_id' => $sid, 'author_id' => Auth::id(), 'topic' => $topic, 'body' => $body, 'created_at' => now()]);
        Audit::log('nutrition.note', 'user', $sid, ['topic' => $topic]);
        flash('success', 'Observação salva.');
        redirect($back);
    }

    public function deleteNote(int $id): void
    {
        $n = DB::one('SELECT * FROM nutrition_notes WHERE id = ?', [$id]) ?? abort(404);
        DB::delete('nutrition_notes', 'id = ?', [$id]);
        flash('success', 'Observação removida.');
        redirect('/painel/alimentacao?' . http_build_query(['aluno' => $n['student_id'], 'ver' => $n['topic']]));
    }
}
