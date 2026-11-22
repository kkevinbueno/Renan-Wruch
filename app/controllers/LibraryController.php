<?php
declare(strict_types=1);

/** Biblioteca de exercícios, independente dos treinos individuais. */
final class LibraryController
{
    public function index(): void
    {
        $q = trim((string) input('q', ''));
        $mod = input_int('modalidade');
        $cat = trim((string) input('categoria', ''));
        $archived = (bool) input('arquivados');
        $where = [$archived ? 'e.archived_at IS NOT NULL' : 'e.archived_at IS NULL'];
        $params = [];
        if ($q !== '') { $where[] = '(e.name LIKE ? OR e.description LIKE ?)'; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }
        if ($mod) { $where[] = 'e.modality_id = ?'; $params[] = $mod; }
        if ($cat !== '') { $where[] = 'e.category = ?'; $params[] = $cat; }
        $rows = DB::all('SELECT e.*, m.name AS modality_name FROM exercises e JOIN modalities m ON m.id = e.modality_id WHERE ' . implode(' AND ', $where) . ' ORDER BY m.sort_order, e.category, e.name LIMIT 500', $params);
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['modality_name']][$r['category'] ?: 'Sem categoria'][] = $r;
        }
        View::render('trainer/library', [
            'title' => 'Biblioteca de exercícios', 'nav' => 'library', 'grouped' => $grouped, 'count' => count($rows),
            'modalities' => Modalities::all(), 'categories' => DB::col('SELECT DISTINCT category FROM exercises WHERE category IS NOT NULL AND archived_at IS NULL ORDER BY category'),
            'q' => $q, 'mod' => $mod, 'cat' => $cat, 'archived' => $archived,
        ]);
    }

    /** Busca usada pelo editor de treinos. */
    public function search(): void
    {
        $q = trim((string) input('q', ''));
        $mod = input_int('modality');
        $where = ['e.archived_at IS NULL'];
        $params = [];
        if ($q !== '') { $where[] = '(e.name LIKE ? OR e.category LIKE ?)'; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }
        if ($mod) { $where[] = 'e.modality_id = ?'; $params[] = $mod; }
        $rows = DB::all('SELECT e.*, m.name AS modality_name, m.fields AS modality_fields FROM exercises e JOIN modalities m ON m.id = e.modality_id WHERE ' . implode(' AND ', $where) . ' ORDER BY e.category, e.name LIMIT 60', $params);
        json_out(['ok' => true, 'results' => array_map(fn($r) => [
            'id' => (int) $r['id'], 'name' => $r['name'], 'category' => $r['category'], 'modality' => $r['modality_name'],
            'instructions' => $r['instructions'], 'notes' => $r['notes'], 'video_url' => $r['video_url'],
            'video_file_id' => $r['video_file_id'] ? (int) $r['video_file_id'] : null,
            'video_file_url' => $r['video_file_id'] ? Files::url((int) $r['video_file_id']) : null,
            'fields' => json_decode_array($r['fields']) ?: json_decode_array($r['modality_fields']),
        ], $rows)]);
    }

    public function form(?int $id = null): void
    {
        $e = $id ? (DB::one('SELECT * FROM exercises WHERE id = ?', [$id]) ?? abort(404)) : null;
        $mod = $e ? Modalities::find((int) $e['modality_id']) : null;
        View::render('trainer/library_form', [
            'title' => $e ? 'Editar exercício' : 'Novo exercício', 'nav' => 'library', 'e' => $e,
            'fields' => $e ? (json_decode_array($e['fields']) ?: ($mod['fields'] ?? [])) : [],
            'modalities' => Modalities::all(), 'fieldsCatalog' => Modalities::FIELDS,
            'categories' => DB::col('SELECT DISTINCT category FROM exercises WHERE category IS NOT NULL ORDER BY category'),
            'usage' => $id ? (int) DB::val('SELECT COUNT(DISTINCT v.workout_id) FROM workout_items i JOIN workout_versions v ON v.id = i.version_id WHERE i.exercise_id = ?', [$id]) : 0,
        ]);
    }

    public function store(?int $id = null): void
    {
        $name = mb_substr(trim((string) input('name', '')), 0, 150);
        $mod = input_int('modality_id');
        $back = $id ? "/painel/biblioteca/{$id}" : '/painel/biblioteca/novo';
        if ($name === '' || !$mod || !Modalities::find($mod)) {
            remember_old($_POST);
            flash('error', 'Informe o nome e a modalidade.');
            redirect($back);
        }
        try {
            $videoUrl = Workouts::safeUrl(input('video_url'));
        } catch (UserError $e) {
            remember_old($_POST);
            flash('error', $e->getMessage());
            redirect($back);
        }
        $data = [
            'modality_id' => $mod, 'name' => $name,
            'category' => mb_substr(trim((string) input('category', '')), 0, 80) ?: null,
            'description' => mb_substr(trim((string) input('description', '')), 0, 4000) ?: null,
            'instructions' => mb_substr(trim((string) input('instructions', '')), 0, 6000) ?: null,
            'notes' => mb_substr(trim((string) input('notes', '')), 0, 4000) ?: null,
            'video_url' => $videoUrl,
            'fields' => json_enc(Modalities::sanitizeFields((array) ($_POST['fields'] ?? []))),
            'updated_at' => now(),
        ];
        if (!empty($_FILES['video']) && ($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $f = Files::store($_FILES['video'], ['context' => 'exercise_video', 'kinds' => ['video'], 'sensitive' => 0]);
                $data['video_file_id'] = (int) $f['id'];
            } catch (UserError $e) {
                remember_old($_POST);
                flash('error', $e->getMessage());
                redirect($back);
            }
        } elseif (input('remove_video')) {
            $data['video_file_id'] = null;
        }
        if ($id) {
            DB::one('SELECT id FROM exercises WHERE id = ?', [$id]) ?? abort(404);
            DB::update('exercises', $data, 'id = ?', [$id]);
            Audit::log('library.update', 'exercise', $id);
        } else {
            $id = DB::insert('exercises', $data + ['created_at' => now()]);
            Audit::log('library.create', 'exercise', $id);
        }
        flash('success', 'Exercício salvo na biblioteca.');
        redirect('/painel/biblioteca?' . http_build_query(['modalidade' => $mod]));
    }

    public function duplicate(int $id): void
    {
        $e = DB::one('SELECT * FROM exercises WHERE id = ?', [$id]) ?? abort(404);
        unset($e['id']);
        $e['name'] = mb_substr($e['name'] . ' (cópia)', 0, 150);
        $e['created_at'] = $e['updated_at'] = now();
        $e['archived_at'] = null;
        $new = DB::insert('exercises', $e);
        flash('success', 'Exercício duplicado.');
        redirect("/painel/biblioteca/{$new}");
    }

    public function archive(int $id): void
    {
        $restore = (bool) input('restore');
        DB::update('exercises', ['archived_at' => $restore ? null : now(), 'updated_at' => now()], 'id = ?', [$id]);
        flash('success', $restore ? 'Exercício restaurado.' : 'Exercício arquivado. Treinos que já o usam não são afetados.');
        redirect('/painel/biblioteca');
    }
}
