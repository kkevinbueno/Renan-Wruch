<?php
declare(strict_types=1);

/**
 * Modalidades atendidas e seus campos de prescrição/registro.
 * Cada esporte tem o próprio conjunto de campos — não há um modelo único para todos.
 */
final class Modalities
{
    /** Catálogo de campos disponíveis. 'per_set' = registrado série a série pelo aluno. */
    public const FIELDS = [
        'sets'      => ['label' => 'Séries', 'unit' => '', 'per_set' => false, 'placeholder' => '3'],
        'reps'      => ['label' => 'Repetições', 'unit' => '', 'per_set' => true, 'placeholder' => '10-12'],
        'load'      => ['label' => 'Carga', 'unit' => 'kg', 'per_set' => true, 'placeholder' => '20'],
        'rest'      => ['label' => 'Descanso', 'unit' => '', 'per_set' => false, 'placeholder' => '60s'],
        'intensity' => ['label' => 'Intensidade', 'unit' => '', 'per_set' => false, 'placeholder' => 'RPE 7 / moderada'],
        'duration'  => ['label' => 'Duração', 'unit' => '', 'per_set' => false, 'placeholder' => '10 min'],
        'distance'  => ['label' => 'Distância', 'unit' => 'm', 'per_set' => true, 'placeholder' => '400'],
        'time'      => ['label' => 'Tempo', 'unit' => '', 'per_set' => true, 'placeholder' => '1:45'],
        'pace'      => ['label' => 'Ritmo', 'unit' => '', 'per_set' => false, 'placeholder' => '1:50/100m'],
        'intervals' => ['label' => 'Intervalos', 'unit' => '', 'per_set' => false, 'placeholder' => '20s entre tiros'],
    ];

    public const DEFAULTS = [
        ['slug' => 'tenis', 'name' => 'Tênis de quadra', 'fields' => ['sets', 'reps', 'duration', 'intensity']],
        ['slug' => 'beach-tennis', 'name' => 'Beach tennis', 'fields' => ['sets', 'reps', 'duration', 'intensity']],
        ['slug' => 'volei-de-areia', 'name' => 'Vôlei de areia', 'fields' => ['sets', 'reps', 'duration', 'intensity']],
        ['slug' => 'natacao', 'name' => 'Natação', 'fields' => ['sets', 'distance', 'time', 'pace', 'intervals']],
        ['slug' => 'musculacao', 'name' => 'Musculação', 'fields' => ['sets', 'reps', 'load', 'rest', 'intensity']],
        ['slug' => 'condicionamento', 'name' => 'Condicionamento físico', 'fields' => ['sets', 'reps', 'duration', 'distance', 'intensity', 'rest']],
    ];

    public static function all(bool $activeOnly = true): array
    {
        $rows = DB::all('SELECT * FROM modalities' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, name');
        foreach ($rows as &$r) {
            $r['fields'] = json_decode_array($r['fields']);
        }
        return $rows;
    }

    public static function find(int $id): ?array
    {
        $r = DB::one('SELECT * FROM modalities WHERE id = ?', [$id]);
        if ($r) {
            $r['fields'] = json_decode_array($r['fields']);
        }
        return $r;
    }

    public static function map(): array
    {
        $out = [];
        foreach (self::all(false) as $m) {
            $out[$m['id']] = $m;
        }
        return $out;
    }

    public static function sanitizeFields(array $fields): array
    {
        return array_values(array_filter($fields, fn($f) => is_string($f) && isset(self::FIELDS[$f])));
    }
}
