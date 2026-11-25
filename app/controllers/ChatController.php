<?php
declare(strict_types=1);

/**
 * Chat privado aluno ↔ Renan. Cada conversa é identificada pelo id do aluno.
 * Atualização "em tempo real" por consulta curta a cada poucos segundos (compatível com hospedagem compartilhada).
 */
final class ChatController
{
    /** Garante que quem pede pode ver a conversa; retorna se está do lado do treinador. */
    private function authorize(int $studentId): bool
    {
        if (Auth::canTrainer()) {
            if (!Students::find($studentId)) abort(404, 'Conversa não encontrada.');
            return true;
        }
        if (!Auth::inStudentInterface() || $studentId !== (int) Auth::id()) {
            abort(403, 'Conversa não permitida.');
        }
        $u = Auth::user();
        if (!Auth::isDeveloper() && !in_array($u['status'], ['active', 'paused'], true)) abort(403);
        return false;
    }

    private function present(array $m, int $studentId, bool $trainerSide): array
    {
        $mine = $trainerSide ? ((int) $m['sender_id'] !== $studentId) : ((int) $m['sender_id'] === $studentId);
        $deleted = !empty($m['deleted_at']);
        $file = null;
        if ($m['file_id'] && !$deleted) {
            $f = DB::one('SELECT * FROM files WHERE id = ?', [$m['file_id']]);
            if ($f && !$f['deleted_at']) {
                $file = ['url' => Files::url((int) $f['id']), 'kind' => $f['kind'], 'name' => $f['original_name'], 'size' => Files::humanSize((int) $f['size_bytes']), 'mime' => $f['mime']];
                if ($f['kind'] === 'audio') {
                    $meta = json_decode_array($m['audio_meta'] ?? null);
                    if ($meta) $file['audio'] = ['duration' => (float) ($meta['duration'] ?? 0), 'peaks' => array_map('floatval', (array) ($meta['peaks'] ?? []))];
                }
            } else {
                $file = ['removed' => true];
            }
        }
        return [
            'id' => (int) $m['id'], 'mine' => $mine, 'body' => $deleted ? null : $m['body'], 'file' => $file,
            'deleted' => $deleted,
            // Só quem enviou pode apagar para todos (conferido de novo no servidor ao apagar).
            'can_delete_all' => !$deleted && (int) $m['sender_id'] === (int) Auth::id(),
            'time' => fmt_time($m['created_at']), 'date' => to_local($m['created_at'])->format('Y-m-d'),
            'read' => (bool) $m['read_at'],
        ];
    }

    /** Condição que esconde as mensagens que o usuário apagou só para si. */
    private const NOT_HIDDEN = 'id NOT IN (SELECT message_id FROM message_hides WHERE user_id = ?)';

    public function poll(int $studentId): void
    {
        $trainerSide = $this->authorize($studentId);
        $after = (int) input('after', 0);
        $before = (int) input('before', 0);
        $uid = (int) Auth::id();
        if ($before > 0) {
            $rows = array_reverse(DB::all('SELECT * FROM messages WHERE student_id = ? AND id < ? AND ' . self::NOT_HIDDEN . ' ORDER BY id DESC LIMIT 40', [$studentId, $before, $uid]));
        } elseif ($after > 0) {
            $rows = DB::all('SELECT * FROM messages WHERE student_id = ? AND id > ? AND ' . self::NOT_HIDDEN . ' ORDER BY id LIMIT 100', [$studentId, $after, $uid]);
        } else {
            $rows = array_reverse(DB::all('SELECT * FROM messages WHERE student_id = ? AND ' . self::NOT_HIDDEN . ' ORDER BY id DESC LIMIT 40', [$studentId, $uid]));
        }
        // Exclusões feitas desde a última consulta (por qualquer lado ou em outro aparelho).
        $since = (string) input('since', '');
        $changes = [];
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since)) {
            foreach (DB::all('SELECT * FROM messages WHERE student_id = ? AND deleted_at IS NOT NULL AND deleted_at >= ?', [$studentId, $since]) as $m) {
                $changes[] = $this->present($m, $studentId, $trainerSide);
            }
            $hidden = array_map('intval', DB::col('SELECT h.message_id FROM message_hides h JOIN messages m ON m.id = h.message_id WHERE h.user_id = ? AND m.student_id = ? AND h.created_at >= ?', [$uid, $studentId, $since]));
        }
        // Marca como lidas as mensagens do outro lado.
        if ($trainerSide) {
            DB::run('UPDATE messages SET read_at = ? WHERE student_id = ? AND sender_id = ? AND read_at IS NULL', [now(), $studentId, $studentId]);
        } else {
            DB::run('UPDATE messages SET read_at = ? WHERE student_id = ? AND (sender_id IS NULL OR sender_id <> ?) AND read_at IS NULL', [now(), $studentId, $studentId]);
        }
        // Indicador de leitura das minhas mensagens.
        $readUpTo = (int) DB::val($trainerSide
            ? 'SELECT MAX(id) FROM messages WHERE student_id = ? AND sender_id <> ? AND read_at IS NOT NULL'
            : 'SELECT MAX(id) FROM messages WHERE student_id = ? AND sender_id = ? AND read_at IS NOT NULL', [$studentId, $studentId]);
        json_out([
            'ok' => true,
            'messages' => array_map(fn($m) => $this->present($m, $studentId, $trainerSide), $rows),
            'changed' => $changes,
            'hidden' => $hidden ?? [],
            'server_time' => now(),
            'read_up_to' => $readUpTo,
            'has_more' => $rows ? (bool) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND id < ? AND ' . self::NOT_HIDDEN, [$studentId, $rows[0]['id'], $uid]) : false,
        ]);
    }

    public function send(int $studentId): void
    {
        $trainerSide = $this->authorize($studentId);
        $uid = (int) Auth::id();
        if (!RateLimit::hit("chat:{$uid}", 120, 300)) json_out(['ok' => false, 'error' => 'Muitas mensagens em pouco tempo. Aguarde um instante.'], 429);
        $body = mb_substr(trim((string) input('body', '')), 0, 4000);
        $fileId = null;
        if (!empty($_FILES['file']) && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $isAudio = input('audio') === '1';
                $f = Files::store($_FILES['file'], [
                    'context' => 'chat', 'student_id' => $studentId,
                    'kinds' => $isAudio ? ['audio'] : ['image', 'video', 'audio', 'document'],
                    'prefer_audio' => $isAudio, 'sensitive' => 1,
                ]);
                $fileId = (int) $f['id'];
            } catch (UserError $e) {
                json_out(['ok' => false, 'error' => $e->getMessage()], 422);
            }
        }
        if ($body === '' && !$fileId) json_out(['ok' => false, 'error' => 'Escreva uma mensagem ou anexe um arquivo.'], 422);
        $audioMeta = null;
        if ($fileId && input('audio') === '1') {
            // Duração e forma de onda calculadas no aparelho a partir do áudio gravado (valores validados aqui).
            $duration = round(max(0, min(3600, (float) input('duration', 0))), 2);
            $peaks = array_slice(array_map(fn($v) => round(max(0, min(1, (float) $v)), 3), (array) json_decode((string) input('peaks', '[]'), true)), 0, 120);
            if ($duration > 0 || $peaks) $audioMeta = json_enc(['duration' => $duration, 'peaks' => $peaks]);
        }
        // Quem envia pelo lado do treinador aparece como o Renan para o aluno, mas o registro guarda o remetente real.
        $id = DB::insert('messages', ['student_id' => $studentId, 'sender_id' => $uid, 'body' => $body ?: null, 'file_id' => $fileId, 'audio_meta' => $audioMeta, 'created_at' => now()]);
        if ($fileId) DB::update('files', ['context_id' => $id], 'id = ?', [$fileId]);
        if ($trainerSide) {
            if ($uid !== $studentId) { // desenvolvedor testando a própria conversa não gera e-mail
                Notify::chatDigest($studentId, $studentId);
            }
        } else {
            $trainer = DB::val("SELECT id FROM users WHERE role = 'trainer' ORDER BY id LIMIT 1");
            if ($trainer) Notify::chatDigest((int) $trainer, $studentId);
        }
        $m = DB::one('SELECT * FROM messages WHERE id = ?', [$id]);
        json_out(['ok' => true, 'message' => $this->present($m, $studentId, $trainerSide)]);
    }

    /**
     * Apaga uma mensagem: "para mim" (só esconde para quem pediu) ou "para todos" (só quem enviou).
     * Permissões conferidas aqui no servidor, nunca só na tela.
     */
    public function delete(int $studentId, int $messageId): void
    {
        $this->authorize($studentId);
        $uid = (int) Auth::id();
        $m = DB::one('SELECT * FROM messages WHERE id = ? AND student_id = ?', [$messageId, $studentId]);
        if (!$m) json_out(['ok' => false, 'error' => 'Mensagem não encontrada.'], 404);
        $scope = input('scope') === 'all' ? 'all' : 'me';
        if ($scope === 'all') {
            if ((int) $m['sender_id'] !== $uid) json_out(['ok' => false, 'error' => 'Só quem enviou pode apagar a mensagem para todos.'], 403);
            if (!$m['deleted_at']) {
                DB::transaction(function () use ($m, $uid) {
                    DB::update('messages', ['deleted_at' => now(), 'deleted_by' => $uid, 'body' => null, 'audio_meta' => null], 'id = ?', [$m['id']]);
                    // O arquivo só é removido do armazenamento se nenhum outro registro depender dele.
                    if ($m['file_id'] && !Files::isReferenced((int) $m['file_id'], (int) $m['id'])) Files::delete((int) $m['file_id']);
                });
                Audit::log('chat.delete_all', 'message', (int) $m['id'], ['student_id' => $studentId, 'had_file' => (bool) $m['file_id']]);
            }
        } else {
            try {
                DB::insert('message_hides', ['message_id' => $m['id'], 'user_id' => $uid, 'created_at' => now()]);
            } catch (PDOException $e) {
                if (!DB::isUniqueViolation($e)) throw $e;
            }
        }
        $m = DB::one('SELECT * FROM messages WHERE id = ?', [$messageId]);
        json_out(['ok' => true, 'scope' => $scope, 'message' => $this->present($m, $studentId, Auth::canTrainer())]);
    }

    public function counters(): void
    {
        if (Auth::canTrainer()) {
            $c = Cron::trainerCounters();
            unset($c['trainer_ids']);
            json_out(['ok' => true] + $c);
        }
        $uid = (int) Auth::id();
        json_out(['ok' => true, 'unread' => (int) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND read_at IS NULL AND (sender_id IS NULL OR sender_id <> ?)', [$uid, $uid])]);
    }

    public function student(): void
    {
        View::render('student/chat', ['title' => 'Mensagens', 'nav' => 'chat', 'studentId' => (int) Auth::id(), 'peerName' => Settings::get('trainer_name')]);
    }

    public function trainerIndex(): void
    {
        View::render('trainer/chat', ['title' => 'Mensagens', 'nav' => 'chat', 'conversations' => self::conversations(), 'studentId' => null, 'student' => null]);
    }

    public function trainerThread(int $id): void
    {
        $s = Students::findOrFail($id);
        View::render('trainer/chat', ['title' => 'Mensagens', 'nav' => 'chat', 'conversations' => self::conversations(), 'studentId' => $id, 'student' => $s]);
    }

    public static function conversations(): array
    {
        return DB::all("SELECT u.id, u.name, u.avatar_file_id, u.status,
                (SELECT MAX(m.id) FROM messages m WHERE m.student_id = u.id) AS last_id,
                (SELECT m.created_at FROM messages m WHERE m.student_id = u.id ORDER BY m.id DESC LIMIT 1) AS last_at,
                (SELECT CASE WHEN m.deleted_at IS NOT NULL THEN 'Mensagem apagada' ELSE COALESCE(m.body, '[anexo]') END FROM messages m WHERE m.student_id = u.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                (SELECT COUNT(*) FROM messages m WHERE m.student_id = u.id AND m.sender_id = u.id AND m.read_at IS NULL AND m.deleted_at IS NULL) AS unread
            FROM users u WHERE u.role = 'student' AND u.status IN ('active','paused')
            ORDER BY last_id IS NULL, last_id DESC, u.name LIMIT 200");
    }
}
