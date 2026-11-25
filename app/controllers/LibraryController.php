<?php
declare(strict_types=1);

/** Biblioteca de exercícios do Renan (catálogo + exercícios personalizados), independente dos treinos. */
final class LibraryController
{
    private const PER_PAGE = 30;

    /** Filtros comuns da listagem e da busca do editor de treinos. */
    private static function filters(): array
    {
        $f = [
            'q' => mb_substr(trim((string) input('q', '')), 0, 80),
            'mod' => input_int('modalidade') ?? input_int('modality'),
            'musculo' => (string) input('musculo', ''),
            'equipamento' => (string) input('equipamento', ''),
            'nivel' => (string) input('nivel', ''),
            'video' => (string) input('video', ''),
            'favoritos' => (bool) input('favoritos'),
            'personalizados' => (bool) input('personalizados'),
            'arquivados' => (bool) input('arquivados'),
        ];
        if (!isset(ExerciseLibrary::MUSCLES[$f['musculo']])) $f['musculo'] = '';
        if (!isset(ExerciseLibrary::EQUIPMENT[$f['equipamento']])) $f['equipamento'] = '';
        if (!isset(ExerciseLibrary::DIFFICULTY[$f['nivel']])) $f['nivel'] = '';
        if (!in_array($f['video'], ['ok', 'pending', 'none'], true)) $f['video'] = '';
        return $f;
    }

    /** @return array{0:string,1:array} cláusula WHERE e parâmetros */
    private static function where(array $f): array
    {
        $where = [$f['arquivados'] ? 'e.archived_at IS NOT NULL' : 'e.archived_at IS NULL'];
        $params = [];
        if ($f['q'] !== '') {
            $where[] = '(e.name LIKE ? OR e.name_en LIKE ? OR e.category LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if ($f['mod']) { $where[] = 'e.modality_id = ?'; $params[] = $f['mod']; }
        if ($f['musculo'] !== '') {
            $where[] = "(e.primary_muscle = ? OR (',' || COALESCE(e.secondary_muscles, '') || ',') LIKE ?)";
            array_push($params, $f['musculo'], '%,' . $f['musculo'] . ',%');
        }
        if ($f['equipamento'] !== '') { $where[] = 'e.equipment = ?'; $params[] = $f['equipamento']; }
        if ($f['nivel'] !== '') { $where[] = 'e.difficulty = ?'; $params[] = $f['nivel']; }
        if ($f['video'] !== '') { $where[] = 'e.video_status = ?'; $params[] = $f['video']; }
        if ($f['favoritos']) { $where[] = 'e.id IN (SELECT exercise_id FROM exercise_favorites WHERE user_id = ?)'; $params[] = (int) Auth::id(); }
        if ($f['personalizados']) $where[] = 'e.is_custom = 1';
        $sql = implode(' AND ', $where);
        // MySQL não usa || para concatenar: troca pela função equivalente.
        if (DB::driver() !== 'sqlite') $sql = str_replace("(',' || COALESCE(e.secondary_muscles, '') || ',')", "CONCAT(',', COALESCE(e.secondary_muscles, ''), ',')", $sql);
        return [$sql, $params];
    }

    private static function favoriteIds(): array
    {
        return array_map('intval', DB::col('SELECT exercise_id FROM exercise_favorites WHERE user_id = ?', [(int) Auth::id()]));
    }

    public function index(): void
    {
        $f = self::filters();
        [$where, $params] = self::where($f);
        $total = (int) DB::val("SELECT COUNT(*) FROM exercises e WHERE {$where}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) input('p', 1)));
        $rows = DB::all("SELECT e.*, m.name AS modality_name, m.slug AS modality_slug FROM exercises e JOIN modalities m ON m.id = e.modality_id
            WHERE {$where} ORDER BY m.sort_order, e.name LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $params);
        $query = array_filter([
            'q' => $f['q'], 'modalidade' => $f['mod'], 'musculo' => $f['musculo'], 'equipamento' => $f['equipamento'],
            'nivel' => $f['nivel'], 'video' => $f['video'], 'favoritos' => $f['favoritos'] ? 1 : null,
            'personalizados' => $f['personalizados'] ? 1 : null, 'arquivados' => $f['arquivados'] ? 1 : null,
        ], fn($v) => $v !== null && $v !== '' && $v !== 0);
        View::render('trainer/library', [
            'title' => 'Biblioteca de exercícios', 'nav' => 'library', 'rows' => $rows, 'total' => $total,
            'page' => $page, 'pages' => $pages, 'query' => $query, 'f' => $f,
            'modalities' => Modalities::all(), 'favorites' => self::favoriteIds(),
            'stats' => [
                'all' => (int) DB::val('SELECT COUNT(*) FROM exercises WHERE archived_at IS NULL'),
                'pending' => (int) DB::val("SELECT COUNT(*) FROM exercises WHERE archived_at IS NULL AND video_status = 'pending'"),
                'ok' => (int) DB::val("SELECT COUNT(*) FROM exercises WHERE archived_at IS NULL AND video_status = 'ok'"),
            ],
        ]);
    }

    /** Busca usada pelo editor de treinos (JSON). */
    public function search(): void
    {
        $f = self::filters();
        [$where, $params] = self::where($f);
        $favs = self::favoriteIds();
        $rows = DB::all("SELECT e.*, m.name AS modality_name, m.fields AS modality_fields FROM exercises e JOIN modalities m ON m.id = e.modality_id
            WHERE {$where} ORDER BY " . ($favs ? 'CASE WHEN e.id IN (' . implode(',', $favs) . ') THEN 0 ELSE 1 END, ' : '') . 'e.name LIMIT 60', $params);
        json_out(['ok' => true, 'results' => array_map(fn($r) => self::toJson($r, $favs), $rows)]);
    }

    public static function toJson(array $r, array $favs = []): array
    {
        return [
            'id' => (int) $r['id'], 'name' => $r['name'], 'name_en' => $r['name_en'] ?? null, 'category' => $r['category'],
            'modality' => $r['modality_name'] ?? null,
            'muscle' => ExerciseLibrary::MUSCLES[$r['primary_muscle'] ?? ''] ?? null,
            'equipment' => ExerciseLibrary::EQUIPMENT[$r['equipment'] ?? ''] ?? null,
            'equipment_icon' => ExerciseLibrary::equipmentIcon($r['equipment'] ?? null),
            'tone' => ExerciseLibrary::muscleTone($r['primary_muscle'] ?? null),
            'difficulty' => ExerciseLibrary::DIFFICULTY[$r['difficulty'] ?? ''] ?? null,
            'image_url' => !empty($r['image_file_id']) ? Files::url((int) $r['image_file_id']) : null,
            'instructions' => $r['instructions'], 'notes' => $r['notes'], 'video_url' => $r['video_url'],
            'video_status' => $r['video_status'] ?? 'none',
            'video_file_id' => $r['video_file_id'] ? (int) $r['video_file_id'] : null,
            'video_file_url' => $r['video_file_id'] ? Files::url((int) $r['video_file_id']) : null,
            'favorite' => in_array((int) $r['id'], $favs, true),
            'fields' => json_decode_array($r['fields']) ?: json_decode_array($r['modality_fields'] ?? null),
        ];
    }

    /** Ficha do exercício. */
    public function show(int $id): void
    {
        $e = DB::one('SELECT e.*, m.name AS modality_name FROM exercises e JOIN modalities m ON m.id = e.modality_id WHERE e.id = ?', [$id]) ?? abort(404);
        View::render('trainer/library_show', [
            'title' => $e['name'], 'nav' => 'library', 'e' => $e,
            'favorite' => in_array($id, self::favoriteIds(), true),
            'usage' => (int) DB::val('SELECT COUNT(DISTINCT v.workout_id) FROM workout_items i JOIN workout_versions v ON v.id = i.version_id WHERE i.exercise_id = ?', [$id]),
        ]);
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
        $existing = $id ? (DB::one('SELECT * FROM exercises WHERE id = ?', [$id]) ?? abort(404)) : null;
        $name = mb_substr(trim((string) input('name', '')), 0, 150);
        $mod = input_int('modality_id');
        $back = $id ? "/painel/biblioteca/{$id}/editar" : '/painel/biblioteca/novo';
        $fail = function (string $msg) use ($back): never {
            remember_old($_POST);
            flash('error', $msg);
            redirect($back);
        };
        if ($name === '' || !$mod || !Modalities::find($mod)) $fail('Informe o nome e a modalidade.');
        // Evita duplicar um exercício com o mesmo nome na mesma modalidade.
        $dup = DB::val('SELECT id FROM exercises WHERE modality_id = ? AND LOWER(name) = LOWER(?) AND archived_at IS NULL' . ($id ? ' AND id <> ?' : ''), $id ? [$mod, $name, $id] : [$mod, $name]);
        if ($dup) $fail('Já existe um exercício com este nome nesta modalidade.');
        try {
            $videoUrl = Workouts::safeUrl(input('video_url'));
        } catch (UserError $e) {
            $fail($e->getMessage());
        }
        if ($videoUrl !== null && !video_embed_url($videoUrl)) $fail('Use um link do YouTube ou do Vimeo para o vídeo poder ser reproduzido no app.');
        $muscle = (string) input('primary_muscle', '');
        $secondary = array_values(array_intersect((array) ($_POST['secondary_muscles'] ?? []), array_keys(ExerciseLibrary::MUSCLES)));
        $equip = (string) input('equipment', '');
        $level = (string) input('difficulty', '');
        $data = [
            'modality_id' => $mod, 'name' => $name,
            'name_en' => mb_substr(trim((string) input('name_en', '')), 0, 150) ?: null,
            'primary_muscle' => isset(ExerciseLibrary::MUSCLES[$muscle]) ? $muscle : null,
            'secondary_muscles' => $secondary ? implode(',', array_diff($secondary, [$muscle])) ?: null : null,
            'equipment' => isset(ExerciseLibrary::EQUIPMENT[$equip]) ? $equip : null,
            'difficulty' => isset(ExerciseLibrary::DIFFICULTY[$level]) ? $level : null,
            'category' => mb_substr(trim((string) input('category', '')), 0, 80) ?: (isset(ExerciseLibrary::MUSCLES[$muscle]) ? ExerciseLibrary::categoryFor($muscle) : null),
            'description' => mb_substr(trim((string) input('description', '')), 0, 4000) ?: null,
            'instructions' => mb_substr(trim((string) input('instructions', '')), 0, 6000) ?: null,
            'mistakes' => mb_substr(trim((string) input('mistakes', '')), 0, 4000) ?: null,
            'notes' => mb_substr(trim((string) input('notes', '')), 0, 4000) ?: null,
            'source' => mb_substr(trim((string) input('source', '')), 0, 255) ?: null,
            'license' => mb_substr(trim((string) input('license', '')), 0, 255) ?: null,
            'video_url' => $videoUrl,
            'fields' => json_enc(Modalities::sanitizeFields((array) ($_POST['fields'] ?? []))),
            'updated_at' => now(),
        ];
        $videoFile = $existing['video_file_id'] ?? null;
        if (!empty($_FILES['video']) && ($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $f = Files::store($_FILES['video'], ['context' => 'exercise_video', 'kinds' => ['video'], 'sensitive' => 0]);
                $videoFile = $data['video_file_id'] = (int) $f['id'];
            } catch (UserError $e) {
                $fail($e->getMessage());
            }
        } elseif (input('remove_video')) {
            $videoFile = $data['video_file_id'] = null;
        }
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $f = Files::store($_FILES['image'], ['context' => 'exercise_image', 'kinds' => ['image'], 'sensitive' => 0]);
                $data['image_file_id'] = (int) $f['id'];
            } catch (UserError $e) {
                $fail($e->getMessage());
            }
        } elseif (input('remove_image')) {
            $data['image_file_id'] = null;
        }
        // Vídeo só conta como revisado quando o Renan confirma que corresponde ao exercício e que pode ser usado.
        $data['video_status'] = ExerciseLibrary::videoStatusFor($videoUrl, $videoFile ? (int) $videoFile : null, input('video_confirmed') ? 'ok' : 'pending');
        if ($id) {
            DB::update('exercises', $data, 'id = ?', [$id]);
            Audit::log('library.update', 'exercise', $id);
        } else {
            $id = DB::insert('exercises', $data + ['is_custom' => 1, 'created_by' => Auth::id(), 'created_at' => now()]);
            Audit::log('library.create', 'exercise', $id);
        }
        flash('success', 'Exercício salvo na biblioteca.');
        redirect("/painel/biblioteca/{$id}");
    }

    public function duplicate(int $id): void
    {
        $e = DB::one('SELECT * FROM exercises WHERE id = ?', [$id]) ?? abort(404);
        unset($e['id']);
        $base = mb_substr($e['name'] . ' (cópia)', 0, 140);
        $name = $base;
        for ($i = 2; DB::val('SELECT id FROM exercises WHERE modality_id = ? AND name = ?', [$e['modality_id'], $name]); $i++) $name = "{$base} {$i}";
        $e['name'] = $name;
        $e['slug'] = null;          // a cópia é um exercício personalizado do Renan
        $e['is_custom'] = 1;
        $e['created_by'] = Auth::id();
        $e['created_at'] = $e['updated_at'] = now();
        $e['archived_at'] = null;
        $new = DB::insert('exercises', $e);
        flash('success', 'Exercício duplicado. Ajuste o que quiser.');
        redirect("/painel/biblioteca/{$new}/editar");
    }

    public function archive(int $id): void
    {
        DB::one('SELECT id FROM exercises WHERE id = ?', [$id]) ?? abort(404);
        $restore = (bool) input('restore');
        DB::update('exercises', ['archived_at' => $restore ? null : now(), 'updated_at' => now()], 'id = ?', [$id]);
        flash('success', $restore ? 'Exercício restaurado.' : 'Exercício arquivado. Treinos que já o usam não são afetados.');
        redirect('/painel/biblioteca');
    }

    /** Favoritar / desfavoritar (formulário ou JSON). */
    public function favorite(int $id): void
    {
        DB::one('SELECT id FROM exercises WHERE id = ?', [$id]) ?? abort(404);
        $uid = (int) Auth::id();
        $on = !DB::val('SELECT id FROM exercise_favorites WHERE user_id = ? AND exercise_id = ?', [$uid, $id]);
        if ($on) {
            try {
                DB::insert('exercise_favorites', ['user_id' => $uid, 'exercise_id' => $id, 'created_at' => now()]);
            } catch (PDOException $e) {
                if (!DB::isUniqueViolation($e)) throw $e;
            }
        } else {
            DB::delete('exercise_favorites', 'user_id = ? AND exercise_id = ?', [$uid, $id]);
        }
        if (is_json_request()) json_out(['ok' => true, 'favorite' => $on]);
        flash('success', $on ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.');
        back('/painel/biblioteca');
    }
}
