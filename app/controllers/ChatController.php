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
        $file = null;
        if ($m['file_id']) {
            $f = DB::one('SELECT * FROM files WHERE id = ?', [$m['file_id']]);
            if ($f && !$f['deleted_at']) {
                $file = ['url' => Files::url((int) $f['id']), 'kind' => $f['kind'], 'name' => $f['original_name'], 'size' => Files::humanSize((int) $f['size_bytes']), 'mime' => $f['mime']];
            } else {
                $file = ['removed' => true];
            }
        }
        return [
            'id' => (int) $m['id'], 'mine' => $mine, 'body' => $m['body'], 'file' => $file,
            'time' => fmt_time($m['created_at']), 'date' => to_local($m['created_at'])->format('Y-m-d'),
            'read' => (bool) $m['read_at'],
        ];
    }

    public function poll(int $studentId): void
    {
        $trainerSide = $this->authorize($studentId);
        $after = (int) input('after', 0);
        $before = (int) input('before', 0);
        if ($before > 0) {
            $rows = array_reverse(DB::all('SELECT * FROM messages WHERE student_id = ? AND id < ? ORDER BY id DESC LIMIT 40', [$studentId, $before]));
        } elseif ($after > 0) {
            $rows = DB::all('SELECT * FROM messages WHERE student_id = ? AND id > ? ORDER BY id LIMIT 100', [$studentId, $after]);
        } else {
            $rows = array_reverse(DB::all('SELECT * FROM messages WHERE student_id = ? ORDER BY id DESC LIMIT 40', [$studentId]));
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
            'read_up_to' => $readUpTo,
            'has_more' => $rows ? (bool) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND id < ?', [$studentId, $rows[0]['id']]) : false,
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
        // Quem envia pelo lado do treinador aparece como o Renan para o aluno, mas o registro guarda o remetente real.
        $id = DB::insert('messages', ['student_id' => $studentId, 'sender_id' => $uid, 'body' => $body ?: null, 'file_id' => $fileId, 'created_at' => now()]);
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
                (SELECT COALESCE(m.body, '[anexo]') FROM messages m WHERE m.student_id = u.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                (SELECT COUNT(*) FROM messages m WHERE m.student_id = u.id AND m.sender_id = u.id AND m.read_at IS NULL) AS unread
            FROM users u WHERE u.role = 'student' AND u.status IN ('active','paused')
            ORDER BY last_id IS NULL, last_id DESC, u.name LIMIT 200");
    }
}
