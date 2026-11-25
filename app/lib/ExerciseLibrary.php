<?php
declare(strict_types=1);

/**
 * Biblioteca de exercícios: catálogos de grupos musculares, equipamentos e níveis,
 * importação idempotente do catálogo (app/data) e consultas usadas pelo painel e pelo aluno.
 */
final class ExerciseLibrary
{
    /** Versão do catálogo em app/data. Ao mudar, a próxima migração importa o que faltar. */
    public const CATALOG_VERSION = '2026-11-1';

    public const MUSCLES = [
        'peitoral' => 'Peitoral', 'costas' => 'Costas', 'biceps' => 'Bíceps', 'triceps' => 'Tríceps',
        'ombros' => 'Ombros', 'trapezio' => 'Trapézio', 'antebracos' => 'Antebraços',
        'quadriceps' => 'Quadríceps', 'posteriores' => 'Posteriores de coxa', 'gluteos' => 'Glúteos',
        'panturrilhas' => 'Panturrilhas', 'core' => 'Abdômen e core', 'lombar' => 'Lombar',
        'adutores' => 'Adutores', 'abdutores' => 'Abdutores', 'corpo-inteiro' => 'Corpo inteiro',
        'mobilidade' => 'Mobilidade', 'alongamento' => 'Alongamento', 'cardio' => 'Condicionamento cardiovascular',
    ];

    public const EQUIPMENT = [
        'halteres' => 'Halteres', 'barra' => 'Barra', 'anilha' => 'Anilhas', 'maquina' => 'Máquina',
        'polia' => 'Polia e cabos', 'smith' => 'Smith', 'kettlebell' => 'Kettlebell', 'elastico' => 'Elástico',
        'peso-corporal' => 'Peso corporal', 'banco' => 'Banco', 'cardio' => 'Equipamento cardiovascular',
        'acessorios' => 'Acessórios',
    ];

    public const DIFFICULTY = ['iniciante' => 'Iniciante', 'intermediario' => 'Intermediário', 'avancado' => 'Avançado'];

    public const VIDEO_STATUS = ['ok' => 'Vídeo revisado', 'pending' => 'Vídeo pendente de revisão', 'none' => 'Sem vídeo'];

    private const LEVEL = ['i' => 'iniciante', 'm' => 'intermediario', 'a' => 'avancado'];

    /** Categoria (região) derivada do grupo muscular principal. */
    public static function categoryFor(string $muscle): string
    {
        return match ($muscle) {
            'peitoral', 'costas', 'biceps', 'triceps', 'ombros', 'trapezio', 'antebracos' => 'Membros superiores',
            'quadriceps', 'posteriores', 'gluteos', 'panturrilhas', 'adutores', 'abdutores' => 'Membros inferiores',
            'core', 'lombar' => 'Abdômen, core e lombar',
            'corpo-inteiro' => 'Corpo inteiro',
            'mobilidade' => 'Mobilidade',
            'alongamento' => 'Alongamento',
            'cardio' => 'Cardio',
            default => 'Outros',
        };
    }

    public static function slugify(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c', '°' => '']);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        return trim($s, '-');
    }

    /** Lê e valida o catálogo. Lança exceção com a linha problemática (não importa nada inválido). */
    public static function catalog(): array
    {
        $patterns = require APP_DIR . '/data/exercicios-padroes.php';
        $lines = file(APP_DIR . '/data/exercicios-musculacao.txt', FILE_IGNORE_NEW_LINES) ?: [];
        $out = [];
        foreach ($lines as $n => $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $p = array_map('trim', explode('|', $line));
            if (count($p) < 7) throw new RuntimeException('Catálogo: linha ' . ($n + 1) . ' incompleta.');
            [$pt, $en, $primary, $secondary, $equip, $lvl, $pattern] = $p;
            $note = $p[7] ?? '';
            $sec = array_values(array_filter(array_map('trim', explode(',', $secondary))));
            if (!isset(self::MUSCLES[$primary]) || array_diff($sec, array_keys(self::MUSCLES))
                || !isset(self::EQUIPMENT[$equip]) || !isset(self::LEVEL[$lvl]) || !isset($patterns[$pattern])) {
                throw new RuntimeException('Catálogo: valor inválido na linha ' . ($n + 1) . " ({$pt}).");
            }
            $slug = 'lib-' . self::slugify($pt);
            if (isset($out[$slug])) throw new RuntimeException("Catálogo: exercício repetido ({$pt}).");
            $pat = $patterns[$pattern];
            if (in_array($primary, ['mobilidade', 'alongamento', 'cardio'], true)) {
                $focus = $sec ? '. Envolve principalmente ' . self::humanList(array_map(fn($m) => self::withArticle($m), $sec)) : '';
            } else {
                $focus = ($primary === 'corpo-inteiro' ? '. Trabalha o corpo inteiro' : '. Trabalha principalmente ' . self::withArticle($primary))
                    . ($sec ? ', com participação ' . self::humanList(array_map(fn($m) => self::withArticle($m, true), $sec)) : '');
            }
            $desc = $pat['tipo'] . $focus
                . '. Equipamento: ' . mb_strtolower(self::EQUIPMENT[$equip]) . '.'
                . ($note !== '' ? ' ' . $note : '');
            $steps = $pat['passos'];
            $modality = $primary === 'cardio' ? 'condicionamento' : 'musculacao';
            $out[$slug] = [
                'slug' => $slug, 'name' => $pt, 'name_en' => $en ?: null, 'primary_muscle' => $primary,
                'secondary_muscles' => $sec ? implode(',', $sec) : null, 'equipment' => $equip,
                'difficulty' => self::LEVEL[$lvl], 'category' => self::categoryFor($primary), 'modality' => $modality,
                'description' => $desc,
                'instructions' => implode("\n", array_map(fn($i, $s) => ($i + 1) . '. ' . $s, array_keys($steps), $steps)),
                'mistakes' => implode("\n", array_map(fn($s) => '• ' . $s, $pat['erros'])),
            ];
        }
        return $out;
    }

    private const ARTICLE = [
        'peitoral' => 'o', 'costas' => 'as', 'biceps' => 'o', 'triceps' => 'o', 'ombros' => 'os', 'trapezio' => 'o',
        'antebracos' => 'os', 'quadriceps' => 'o', 'posteriores' => 'os', 'gluteos' => 'os', 'panturrilhas' => 'as',
        'core' => 'o', 'lombar' => 'a', 'adutores' => 'os', 'abdutores' => 'os', 'corpo-inteiro' => 'o',
        'mobilidade' => 'a', 'alongamento' => 'o', 'cardio' => 'o',
    ];

    /** "o peitoral" / "dos ombros" (com a preposição "de" contraída). */
    private static function withArticle(string $muscle, bool $of = false): string
    {
        $art = self::ARTICLE[$muscle] ?? 'o';
        $name = mb_strtolower(self::MUSCLES[$muscle]);
        if ($muscle === 'posteriores') $name = 'posteriores de coxa';
        if ($muscle === 'core') return $of ? 'do abdômen e do core' : 'o abdômen e o core';
        return ($of ? 'd' . $art : $art) . ' ' . $name;
    }

    private static function humanList(array $items): string
    {
        if (count($items) <= 1) return implode('', $items);
        $last = array_pop($items);
        return implode(', ', $items) . ' e ' . $last;
    }

    /** Grava os catálogos de grupos musculares e equipamentos (idempotente). */
    public static function seedLookups(): void
    {
        foreach ([['muscle_groups', self::MUSCLES], ['equipment', self::EQUIPMENT]] as [$table, $list]) {
            $i = 0;
            foreach ($list as $slug => $name) {
                $id = DB::val("SELECT id FROM {$table} WHERE slug = ?", [$slug]);
                if ($id) DB::update($table, ['name' => $name, 'sort_order' => $i], 'id = ?', [$id]);
                else DB::insert($table, ['slug' => $slug, 'name' => $name, 'sort_order' => $i]);
                $i++;
            }
        }
    }

    /**
     * Importa o catálogo. Pode ser executada várias vezes:
     * - exercício novo (por slug) é inserido;
     * - exercício que já existe nunca é sobrescrito: só campos vazios são preenchidos
     *   (preserva o que o Renan editou);
     * - exercício antigo com o mesmo nome e modalidade, sem slug, é reaproveitado em vez de duplicado.
     *
     * @return array{inserted:int, updated:int, total:int}
     */
    public static function import(): array
    {
        self::seedLookups();
        $mods = [];
        foreach (DB::all('SELECT id, slug FROM modalities') as $m) $mods[$m['slug']] = (int) $m['id'];
        $inserted = $updated = 0;
        $catalog = self::catalog();
        DB::transaction(function () use ($catalog, $mods, &$inserted, &$updated) {
            foreach ($catalog as $e) {
                $mid = $mods[$e['modality']] ?? null;
                if (!$mid) continue;
                $row = DB::one('SELECT * FROM exercises WHERE slug = ?', [$e['slug']])
                    ?? DB::one('SELECT * FROM exercises WHERE slug IS NULL AND modality_id = ? AND LOWER(name) = LOWER(?)', [$mid, $e['name']]);
                $data = [
                    'slug' => $e['slug'], 'name_en' => $e['name_en'], 'primary_muscle' => $e['primary_muscle'],
                    'secondary_muscles' => $e['secondary_muscles'], 'equipment' => $e['equipment'], 'difficulty' => $e['difficulty'],
                    'category' => $e['category'], 'description' => $e['description'], 'instructions' => $e['instructions'],
                    'mistakes' => $e['mistakes'],
                    'source' => 'Biblioteca Wruch Personal (texto próprio)',
                    'license' => 'Texto original do Wruch Personal. Vídeo e imagem: adicionar somente com autorização ou licença de uso.',
                ];
                if (!$row) {
                    DB::insert('exercises', $data + [
                        'modality_id' => $mid, 'name' => $e['name'], 'video_status' => 'pending', 'is_custom' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $inserted++;
                    continue;
                }
                $fill = [];
                foreach ($data as $k => $v) {
                    if ($v !== null && (($row[$k] ?? null) === null || $row[$k] === '')) $fill[$k] = $v;
                }
                if ($fill) {
                    if (!$row['video_url'] && !$row['video_file_id'] && ($row['video_status'] ?? 'none') === 'none') $fill['video_status'] = 'pending';
                    DB::update('exercises', $fill + ['updated_at' => now()], 'id = ?', [$row['id']]);
                    $updated++;
                }
            }
        });
        return ['inserted' => $inserted, 'updated' => $updated, 'total' => count($catalog)];
    }

    /** Roda a importação uma vez por versão do catálogo (chamada após as migrações). */
    public static function ensureImported(): void
    {
        try {
            if (!DB::val("SELECT COUNT(*) FROM migrations WHERE name = '002_atualizacao_completa'")) return;
        } catch (Throwable) {
            return;
        }
        if (Settings::get('exercise_catalog_version') === self::CATALOG_VERSION) return;
        self::import();
        Settings::set('exercise_catalog_version', self::CATALOG_VERSION);
    }

    /** Status do vídeo a partir do que foi informado. */
    public static function videoStatusFor(?string $url, ?int $fileId, string $requested): string
    {
        if ($url || $fileId) return $requested === 'pending' ? 'pending' : 'ok';
        return 'none';
    }

    public static function secondaryNames(?string $csv): array
    {
        return array_values(array_filter(array_map(fn($m) => self::MUSCLES[trim($m)] ?? null, explode(',', (string) $csv))));
    }

    /** Ícone (símbolo do sprite) que representa o equipamento. */
    public static function equipmentIcon(?string $equip): string
    {
        return 'eq-' . (isset(self::EQUIPMENT[(string) $equip]) ? $equip : 'acessorios');
    }

    /** Miniatura: imagem enviada pelo Renan ou pictograma do equipamento. */
    public static function thumb(array $e, string $cls = ''): string
    {
        if (!empty($e['image_file_id'])) {
            return '<span class="ex-thumb ' . e($cls) . '"><img src="' . e((string) Files::url((int) $e['image_file_id'])) . '" alt="" loading="lazy"></span>';
        }
        return '<span class="ex-thumb pict ' . e($cls) . ' ' . e(self::muscleTone($e['primary_muscle'] ?? null)) . '" aria-hidden="true">' . icon(self::equipmentIcon($e['equipment'] ?? null), 'icon') . '</span>';
    }

    public static function muscleTone(?string $m): string
    {
        return match (self::categoryFor((string) $m)) {
            'Membros superiores' => 'tone-up',
            'Membros inferiores' => 'tone-low',
            'Abdômen, core e lombar' => 'tone-core',
            'Cardio' => 'tone-cardio',
            'Mobilidade', 'Alongamento' => 'tone-mob',
            default => 'tone-full',
        };
    }
}
