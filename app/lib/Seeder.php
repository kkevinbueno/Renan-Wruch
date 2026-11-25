<?php
declare(strict_types=1);

/** Dados essenciais de funcionamento (modalidades). Não cria alunos, números ou conteúdos fictícios. */
final class Seeder
{
    public static function essentials(): void
    {
        foreach (Modalities::DEFAULTS as $i => $m) {
            if (!DB::val('SELECT id FROM modalities WHERE slug = ?', [$m['slug']])) {
                DB::insert('modalities', [
                    'slug' => $m['slug'], 'name' => $m['name'], 'fields' => json_enc($m['fields']),
                    'active' => 1, 'sort_order' => $i,
                ]);
            }
        }
        // Biblioteca de exercícios: importa o catálogo uma vez por versão (sem duplicar).
        ExerciseLibrary::ensureImported();
    }

    /**
     * Biblioteca inicial OPCIONAL (php cron/seed-library.php): apenas nomes e categorias comuns,
     * sem instruções técnicas — o Renan completa ou exclui conforme sua metodologia.
     */
    public static function starterLibrary(): int
    {
        $lib = [
            'musculacao' => [
                'Membros inferiores' => ['Agachamento livre', 'Leg press 45°', 'Cadeira extensora', 'Mesa flexora', 'Stiff', 'Elevação pélvica', 'Panturrilha em pé'],
                'Membros superiores' => ['Supino reto', 'Remada curvada', 'Puxada frontal', 'Desenvolvimento com halteres', 'Rosca direta', 'Tríceps na polia'],
                'Core' => ['Prancha frontal', 'Prancha lateral', 'Abdominal supra'],
            ],
            'natacao' => [
                'Aquecimento' => ['Nado livre leve', 'Educativo de braçada'],
                'Principal' => ['Tiros de crawl', 'Pernada com prancha', 'Pull buoy'],
                'Volta à calma' => ['Nado solto'],
            ],
            'tenis' => [
                'Técnica' => ['Forehand cruzado', 'Backhand paralelo', 'Saque', 'Voleio'],
                'Físico específico' => ['Deslocamento lateral', 'Split step com reação'],
            ],
            'beach-tennis' => [
                'Técnica' => ['Saque', 'Smash', 'Lob', 'Voleio de defesa'],
                'Físico específico' => ['Deslocamento na areia', 'Saltos com aterrissagem'],
            ],
            'volei-de-areia' => [
                'Técnica' => ['Manchete', 'Toque', 'Ataque', 'Bloqueio', 'Saque'],
                'Físico específico' => ['Saltos verticais na areia', 'Deslocamento defensivo'],
            ],
            'condicionamento' => [
                'Cardio' => ['Corrida contínua', 'Tiros de corrida', 'Bicicleta'],
                'Funcional' => ['Burpee', 'Polichinelo', 'Kettlebell swing', 'Corda naval'],
            ],
        ];
        $n = 0;
        foreach ($lib as $slug => $cats) {
            $mid = (int) DB::val('SELECT id FROM modalities WHERE slug = ?', [$slug]);
            if (!$mid) continue;
            foreach ($cats as $cat => $names) {
                foreach ($names as $name) {
                    if (DB::val('SELECT id FROM exercises WHERE modality_id = ? AND name = ?', [$mid, $name])) continue;
                    DB::insert('exercises', ['modality_id' => $mid, 'name' => $name, 'category' => $cat, 'created_at' => now(), 'updated_at' => now()]);
                    $n++;
                }
            }
        }
        return $n;
    }
}
