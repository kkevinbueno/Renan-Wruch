<?php
declare(strict_types=1);

/**
 * Treinos com versionamento:
 *  - cada treino tem versões (draft → scheduled → published → superseded);
 *  - a versão publicada continua visível até a nova ser publicada (agora ou na data agendada);
 *  - registros de execução apontam para a versão usada, então editar nunca altera o histórico;
 *  - itens copiados da biblioteca podem ser personalizados sem mudar o exercício original.
 */
final class Workouts
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM workouts WHERE id = ?', [$id]);
    }

    public static function findOrFail(int $id): array
    {
        return self::find($id) ?? abort(404, 'Treino não encontrado.');
    }

    public static function create(?int $studentId, array $data, bool $template = false): int
    {
        return DB::transaction(function () use ($studentId, $data, $template) {
            $wid = DB::insert('workouts', [
                'student_id' => $template ? null : $studentId,
                'is_template' => (int) $template,
                'label' => $data['label'] ?? null,
                'name' => $data['name'],
                'modality_id' => (int) $data['modality_id'],
                'weekdays' => self::weekdaysString($data['weekdays'] ?? []),
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::insert('workout_versions', [
                'workout_id' => $wid, 'version_no' => 1, 'status' => 'draft',
                'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $wid;
        });
    }

    public static function weekdaysString(array $days): ?string
    {
        $days = array_values(array_unique(array_filter(array_map('intval', $days), fn($d) => $d >= 0 && $d <= 6)));
        sort($days);
        return $days ? implode(',', $days) : null;
    }

    public static function weekdays(array $workout): array
    {
        return $workout['weekdays'] === null || $workout['weekdays'] === '' ? [] : array_map('intval', explode(',', $workout['weekdays']));
    }

    public static function published(int $workoutId): ?array
    {
        return DB::one("SELECT * FROM workout_versions WHERE workout_id = ? AND status = 'published' ORDER BY version_no DESC LIMIT 1", [$workoutId]);
    }

    /** Cópia de trabalho (rascunho ou agendada), se existir. */
    public static function workingCopy(int $workoutId): ?array
    {
        return DB::one("SELECT * FROM workout_versions WHERE workout_id = ? AND status IN ('draft','scheduled') ORDER BY version_no DESC LIMIT 1", [$workoutId]);
    }

    /** Garante um rascunho editável copiando a versão publicada. */
    public static function ensureWorkingCopy(int $workoutId): array
    {
        $wc = self::workingCopy($workoutId);
        if ($wc) return $wc;
        return DB::transaction(function () use ($workoutId) {
            $pub = self::published($workoutId);
            $no = (int) DB::val('SELECT MAX(version_no) FROM workout_versions WHERE workout_id = ?', [$workoutId]) + 1;
            $vid = DB::insert('workout_versions', [
                'workout_id' => $workoutId, 'version_no' => $no, 'status' => 'draft',
                'general_notes' => $pub['general_notes'] ?? null,
                'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($pub) {
                self::copyItems((int) $pub['id'], $vid);
            }
            return DB::one('SELECT * FROM workout_versions WHERE id = ?', [$vid]);
        });
    }

    public static function copyItems(int $fromVersion, int $toVersion): void
    {
        foreach (self::items($fromVersion) as $it) {
            unset($it['id']);
            $it['version_id'] = $toVersion;
            DB::insert('workout_items', array_intersect_key($it, array_flip(['version_id', 'position', 'exercise_id', 'name', 'instructions', 'notes', 'video_url', 'video_file_id', 'params', 'fields'])));
        }
    }

    public static function items(int $versionId): array
    {
        return DB::all('SELECT * FROM workout_items WHERE version_id = ? ORDER BY position, id', [$versionId]);
    }

    /** Conteúdo mais recente (cópia de trabalho ou publicada). */
    public static function latestVersion(int $workoutId): ?array
    {
        return self::workingCopy($workoutId) ?? self::published($workoutId);
    }

    /** Substitui os itens do rascunho pelos enviados pelo editor. */
    public static function saveItems(int $versionId, array $items): void
    {
        $v = DB::one('SELECT * FROM workout_versions WHERE id = ?', [$versionId]);
        if (!$v || !in_array($v['status'], ['draft', 'scheduled'], true)) {
            throw new UserError('Somente rascunhos podem ser editados. Crie uma nova versão.');
        }
        DB::transaction(function () use ($versionId, $items) {
            DB::delete('workout_items', 'version_id = ?', [$versionId]);
            foreach (array_values($items) as $pos => $it) {
                $name = trim((string) ($it['name'] ?? ''));
                if ($name === '') continue;
                $params = [];
                foreach (Modalities::FIELDS as $k => $_) {
                    $val = trim((string) ($it['params'][$k] ?? ''));
                    if ($val !== '') $params[$k] = mb_substr($val, 0, 60);
                }
                $exId = !empty($it['exercise_id']) ? (int) $it['exercise_id'] : null;
                if ($exId && !DB::val('SELECT id FROM exercises WHERE id = ?', [$exId])) $exId = null;
                $videoFile = !empty($it['video_file_id']) ? (int) $it['video_file_id'] : null;
                if ($videoFile && !DB::val("SELECT id FROM files WHERE id = ? AND context = 'exercise_video' AND deleted_at IS NULL", [$videoFile])) $videoFile = null;
                DB::insert('workout_items', [
                    'version_id' => $versionId,
                    'position' => $pos,
                    'exercise_id' => $exId,
                    'name' => mb_substr($name, 0, 150),
                    'instructions' => self::text($it['instructions'] ?? null),
                    'notes' => self::text($it['notes'] ?? null),
                    'video_url' => self::safeUrl($it['video_url'] ?? null),
                    'video_file_id' => $videoFile,
                    'params' => json_enc((object) $params),
                    'fields' => json_enc(Modalities::sanitizeFields((array) ($it['fields'] ?? []))),
                ]);
            }
            DB::update('workout_versions', ['updated_at' => now()], 'id = ?', [$versionId]);
        });
    }

    private static function text(mixed $v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : mb_substr($v, 0, 5000);
    }

    public static function safeUrl(mixed $v): ?string
    {
        $v = trim((string) ($v ?? ''));
        if ($v === '') return null;
        if (!preg_match('~^https?://~i', $v) || !filter_var($v, FILTER_VALIDATE_URL)) {
            throw new UserError('Link de vídeo inválido: use um endereço começando com https://');
        }
        return mb_substr($v, 0, 500);
    }

    public static function publishNow(int $versionId, bool $notify = true): void
    {
        $v = DB::one('SELECT * FROM workout_versions WHERE id = ?', [$versionId]);
        if (!$v || !in_array($v['status'], ['draft', 'scheduled'], true)) {
            throw new UserError('Esta versão não pode ser publicada.');
        }
        if (!DB::val('SELECT COUNT(*) FROM workout_items WHERE version_id = ?', [$versionId])) {
            throw new UserError('Adicione pelo menos um exercício antes de publicar.');
        }
        $w = self::find((int) $v['workout_id']);
        DB::transaction(function () use ($v) {
            DB::run("UPDATE workout_versions SET status = 'superseded', updated_at = ? WHERE workout_id = ? AND status = 'published'", [now(), $v['workout_id']]);
            DB::update('workout_versions', ['status' => 'published', 'published_at' => now(), 'publish_at' => null, 'updated_at' => now()], 'id = ?', [$v['id']]);
            DB::update('workouts', ['updated_at' => now()], 'id = ?', [$v['workout_id']]);
        });
        Audit::log('workout.publish', 'workout', (int) $v['workout_id'], ['version' => (int) $v['version_no']]);
        if ($notify && $w && $w['student_id'] && !$w['is_template']) {
            Notify::send((int) $w['student_id'], 'workout_published', ['workout' => trim(($w['label'] ? $w['label'] . ' — ' : '') . $w['name'])], ['dedupe' => 'wpub:' . $v['id'], 'pref' => 'workout_published']);
        }
    }

    public static function schedule(int $versionId, string $publishAtUtc): void
    {
        $v = DB::one('SELECT * FROM workout_versions WHERE id = ?', [$versionId]);
        if (!$v || !in_array($v['status'], ['draft', 'scheduled'], true)) {
            throw new UserError('Esta versão não pode ser agendada.');
        }
        if ($publishAtUtc <= now()) {
            throw new UserError('Escolha uma data e horário no futuro.');
        }
        if (!DB::val('SELECT COUNT(*) FROM workout_items WHERE version_id = ?', [$versionId])) {
            throw new UserError('Adicione pelo menos um exercício antes de agendar.');
        }
        DB::update('workout_versions', ['status' => 'scheduled', 'publish_at' => $publishAtUtc, 'updated_at' => now()], 'id = ?', [$versionId]);
        Audit::log('workout.schedule', 'workout', (int) $v['workout_id'], ['version' => (int) $v['version_no'], 'publish_at' => $publishAtUtc]);
    }

    public static function unschedule(int $versionId): void
    {
        DB::update('workout_versions', ['status' => 'draft', 'publish_at' => null, 'updated_at' => now()], "id = ? AND status = 'scheduled'", [$versionId]);
    }

    /** Publica versões agendadas cujo horário chegou (cron + verificação a cada acesso). */
    public static function publishDue(): int
    {
        $n = 0;
        foreach (DB::all("SELECT id FROM workout_versions WHERE status = 'scheduled' AND publish_at <= ?", [now()]) as $row) {
            try {
                self::publishNow((int) $row['id']);
                $n++;
            } catch (UserError $e) {
                Log::warning('Publicação agendada falhou', ['version' => $row['id'], 'erro' => $e->getMessage()]);
                self::unschedule((int) $row['id']);
            }
        }
        return $n;
    }

    /** Duplica um treino (para o mesmo aluno, outro aluno ou como modelo). */
    public static function duplicate(int $workoutId, ?int $targetStudent, bool $asTemplate, ?string $newName = null): int
    {
        $w = self::findOrFail($workoutId);
        $src = self::latestVersion($workoutId);
        $newId = self::create($targetStudent, [
            'label' => $asTemplate ? null : $w['label'],
            'name' => $newName ?: ($w['name'] . ($asTemplate || $targetStudent === (int) $w['student_id'] ? ' (cópia)' : '')),
            'modality_id' => $w['modality_id'],
            'weekdays' => $asTemplate ? [] : self::weekdays($w),
        ], $asTemplate);
        $draft = self::workingCopy($newId);
        if ($src) {
            self::copyItems((int) $src['id'], (int) $draft['id']);
            DB::update('workout_versions', ['general_notes' => $src['general_notes']], 'id = ?', [$draft['id']]);
        }
        Audit::log('workout.duplicate', 'workout', $newId, ['from' => $workoutId, 'template' => $asTemplate]);
        return $newId;
    }

    public static function forStudent(int $studentId, bool $includeArchived = false): array
    {
        $rows = DB::all('SELECT w.*, m.name AS modality_name FROM workouts w JOIN modalities m ON m.id = w.modality_id
            WHERE w.student_id = ? AND w.is_template = 0' . ($includeArchived ? '' : ' AND w.archived_at IS NULL') . ' ORDER BY w.sort_order, w.label, w.id', [$studentId]);
        foreach ($rows as &$r) {
            $r['published'] = self::published((int) $r['id']);
            $r['working'] = self::workingCopy((int) $r['id']);
        }
        return $rows;
    }

    public static function templates(): array
    {
        return DB::all('SELECT w.*, m.name AS modality_name, (SELECT COUNT(*) FROM workout_items i JOIN workout_versions v ON v.id = i.version_id WHERE v.workout_id = w.id AND v.status = \'draft\') AS items_count
            FROM workouts w JOIN modalities m ON m.id = w.modality_id WHERE w.is_template = 1 AND w.archived_at IS NULL ORDER BY w.name');
    }

    public static function title(array $w): string
    {
        return trim(($w['label'] ? $w['label'] . ' · ' : '') . $w['name']);
    }

    /** Itens decodificados para exibição/registro. */
    public static function decodeItems(array $items, array $modality): array
    {
        foreach ($items as &$it) {
            $it['params'] = json_decode_array($it['params']);
            $fields = json_decode_array($it['fields']);
            $it['fields'] = $fields ?: ($modality['fields'] ?? []);
        }
        return $items;
    }
}
