<?php
declare(strict_types=1);

/** Registro pessoal simples de hábitos alimentares e hidratação (sem dietas nem contagem de calorias). */
final class NutritionController
{
    public const MEALS = ['cafe' => 'Café da manhã', 'lanche_manha' => 'Lanche da manhã', 'almoco' => 'Almoço', 'lanche_tarde' => 'Lanche da tarde', 'jantar' => 'Jantar', 'ceia' => 'Ceia', 'pre_treino' => 'Pré-treino', 'pos_treino' => 'Pós-treino', 'agua' => 'Somente hidratação', 'geral' => 'Observação geral do dia'];

    public function index(): void
    {
        $uid = (int) Auth::id();
        $date = (string) input('data', today());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = today();
        $rows = DB::all('SELECT * FROM nutrition_logs WHERE student_id = ? AND ref_date >= ? ORDER BY ref_date DESC, id DESC', [$uid, date_add_days(today(), -30)]);
        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['ref_date']][] = $r;
        }
        View::render('student/nutrition', [
            'title' => 'Alimentação e hidratação', 'nav' => 'nutrition', 'date' => $date, 'byDay' => $byDay, 'meals' => self::MEALS,
            'waterToday' => (int) DB::val('SELECT COALESCE(SUM(water_ml),0) FROM nutrition_logs WHERE student_id = ? AND ref_date = ?', [$uid, today()]),
        ]);
    }

    public function save(): void
    {
        $uid = (int) Auth::id();
        $date = (string) input('date', today());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > today()) $date = today();
        $meal = (string) input('meal', 'geral');
        if (!isset(self::MEALS[$meal])) $meal = 'geral';
        $desc = mb_substr(trim((string) input('description', '')), 0, 2000);
        $water = input_int('water_ml');
        if ($water !== null && ($water < 0 || $water > 10000)) $water = null;
        if ($desc === '' && !$water) {
            flash('error', 'Escreva algo sobre a refeição ou informe a quantidade de água.');
            redirect('/app/alimentacao');
        }
        DB::insert('nutrition_logs', [
            'student_id' => $uid, 'ref_date' => $date, 'meal' => $meal, 'description' => $desc ?: null, 'water_ml' => $water ?: null,
            'notes' => mb_substr(trim((string) input('notes', '')), 0, 1000) ?: null, 'created_at' => now(),
        ]);
        flash('success', 'Registro salvo.');
        redirect('/app/alimentacao');
    }

    public function delete(int $id): void
    {
        DB::delete('nutrition_logs', 'id = ? AND student_id = ?', [$id, Auth::id()]);
        flash('success', 'Registro removido.');
        redirect('/app/alimentacao');
    }
}
