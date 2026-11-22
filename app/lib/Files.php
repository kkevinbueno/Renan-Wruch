<?php
declare(strict_types=1);

/**
 * Armazenamento privado de arquivos (fora da pasta pública).
 * - Valida extensão + tipo real (finfo) + tamanho.
 * - Imagens são reprocessadas pelo GD: remove metadados (EXIF/GPS) e neutraliza arquivos disfarçados.
 * - Entrega somente por /media/{id} com sessão autenticada + URL assinada temporária.
 */
final class Files
{
    public const TYPES = [
        'image' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'],
        'video' => ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-m4v' => 'm4v'],
        'audio' => ['audio/webm' => 'webm', 'video/webm' => 'webm', 'audio/ogg' => 'ogg', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'video/mp4' => 'm4a', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/aac' => 'aac'],
        'document' => [
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/msword' => 'doc',
            'application/vnd.ms-excel' => 'xls',
        ],
    ];

    public static function limitBytes(string $kind): int
    {
        $mb = Settings::int('upload_' . $kind . '_mb') ?: 10;
        return $mb * 1024 * 1024;
    }

    public static function detectKind(string $mime, array $allowed): ?string
    {
        foreach ($allowed as $kind) {
            if (isset(self::TYPES[$kind][$mime])) {
                return $kind;
            }
        }
        return null;
    }

    /**
     * @param array $upload  entrada de $_FILES
     * @param array $opts    context, kinds (lista permitida), student_id, context_id, kind_label, sensitive, keep_png
     */
    public static function store(array $upload, array $opts): array
    {
        $err = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new UserError(match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite permitido pelo servidor.',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado.',
                UPLOAD_ERR_PARTIAL => 'O envio foi interrompido. Tente novamente.',
                default => 'Não foi possível receber o arquivo.',
            });
        }
        $tmp = (string) $upload['tmp_name'];
        if (!is_uploaded_file($tmp) && !(config('app.env') === 'testing' && is_file($tmp))) {
            throw new UserError('Envio inválido.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';
        $origName = self::cleanName((string) ($upload['name'] ?? 'arquivo'));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        // Office moderno pode ser detectado como zip.
        if (in_array($mime, ['application/zip', 'application/octet-stream'], true) && in_array($ext, ['docx', 'xlsx'], true)) {
            $mime = $ext === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        $kinds = $opts['kinds'] ?? ['image'];
        // Gravações de áudio em webm/mp4 são detectadas como vídeo pelo finfo.
        if (in_array('audio', $kinds, true) && ($opts['prefer_audio'] ?? false) && isset(self::TYPES['audio'][$mime])) {
            $kind = 'audio';
        } else {
            $kind = self::detectKind($mime, $kinds);
        }
        if (!$kind) {
            throw new UserError('Formato de arquivo não permitido (' . e($mime) . ').');
        }
        $size = (int) filesize($tmp);
        if ($size <= 0) {
            throw new UserError('Arquivo vazio.');
        }
        if ($size > self::limitBytes($kind)) {
            throw new UserError('Arquivo muito grande. Limite: ' . (self::limitBytes($kind) / 1048576) . ' MB.');
        }
        $extOut = self::TYPES[$kind][$mime];
        $rel = gmdate('Y/m') . '/' . bin2hex(random_bytes(16));
        $dir = storage_path('uploads/' . gmdate('Y/m'));
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        if ($kind === 'image') {
            [$rel, $mime, $size] = self::processImage($tmp, $rel, (bool) ($opts['keep_png'] ?? false) && $mime === 'image/png');
        } else {
            $rel .= '.' . $extOut;
            $dest = storage_path('uploads/' . $rel);
            if (!(is_uploaded_file($tmp) ? move_uploaded_file($tmp, $dest) : copy($tmp, $dest))) {
                throw new RuntimeException('Falha ao gravar arquivo.');
            }
            @chmod($dest, 0640);
        }
        $id = DB::insert('files', [
            'uploaded_by' => Auth::id(),
            'student_id' => $opts['student_id'] ?? null,
            'context' => $opts['context'],
            'context_id' => $opts['context_id'] ?? null,
            'kind' => $opts['kind_label'] ?? $kind,
            'storage_path' => $rel,
            'original_name' => $origName,
            'mime' => $mime,
            'size_bytes' => $size,
            'sensitive' => (int) ($opts['sensitive'] ?? 1),
            'created_at' => now(),
        ]);
        return self::find($id);
    }

    private static function processImage(string $tmp, string $rel, bool $keepPng): array
    {
        $data = file_get_contents($tmp);
        $img = @imagecreatefromstring($data);
        if (!$img) {
            throw new UserError('Imagem inválida ou corrompida.');
        }
        if (function_exists('exif_read_data') && str_starts_with($data, "\xFF\xD8")) {
            $exif = @exif_read_data($tmp);
            $o = (int) ($exif['Orientation'] ?? 1);
            $img = match ($o) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => $img,
            };
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $max = 2400;
        if (max($w, $h) > $max) {
            $ratio = $max / max($w, $h);
            $nw = (int) round($w * $ratio);
            $nh = (int) round($h * $ratio);
            $res = imagecreatetruecolor($nw, $nh);
            if ($keepPng) {
                imagealphablending($res, false);
                imagesavealpha($res, true);
            }
            imagecopyresampled($res, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $img = $res;
        }
        if ($keepPng) {
            imagesavealpha($img, true);
            $rel .= '.png';
            imagepng($img, storage_path('uploads/' . $rel), 7);
            $mime = 'image/png';
        } else {
            // Fundo branco para imagens com transparência convertidas em JPEG.
            $canvas = imagecreatetruecolor(imagesx($img), imagesy($img));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
            $rel .= '.jpg';
            imagejpeg($canvas, storage_path('uploads/' . $rel), 85);
            $mime = 'image/jpeg';
        }
        @chmod(storage_path('uploads/' . $rel), 0640);
        return [$rel, $mime, (int) filesize(storage_path('uploads/' . $rel))];
    }

    private static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N}\.\-_ ]+/u', '_', $name) ?: 'arquivo';
        return mb_substr($name, -120);
    }

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM files WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function fullPath(array $file): string
    {
        return storage_path('uploads/' . $file['storage_path']);
    }

    public static function delete(?int $id): void
    {
        if (!$id) return;
        $f = DB::one('SELECT * FROM files WHERE id = ?', [$id]);
        if (!$f) return;
        @unlink(self::fullPath($f));
        DB::update('files', ['deleted_at' => now()], 'id = ?', [$id]);
    }

    /** Regras de acesso a cada contexto de arquivo. */
    public static function canView(?array $user, array $file): bool
    {
        if (in_array($file['context'], ['public', 'brand'], true)) {
            return true;
        }
        if (!$user) {
            return false;
        }
        if (Auth::canTrainer()) {
            return true;
        }
        $uid = (int) $user['id'];
        return match ($file['context']) {
            // Vídeos da biblioteca/treinos: visíveis a alunos ativos.
            'exercise_video' => in_array($user['status'], ['active', 'paused'], true) || $user['role'] === 'developer',
            // Avatar do Renan aparece no chat; avatar do aluno só para ele.
            'avatar' => $file['student_id'] === null || (int) $file['student_id'] === $uid,
            default => (int) $file['student_id'] === $uid,
        };
    }

    public static function url(?int $id, int $ttl = 7200): ?string
    {
        if (!$id) return null;
        $exp = Clock::time() + $ttl;
        $exp -= $exp % 600; // estabiliza a URL por 10 min (cache do navegador)
        $uid = (int) Auth::id();
        $sig = substr(hash_hmac('sha256', "{$id}|{$exp}|{$uid}", app_key()), 0, 32);
        return "/media/{$id}?e={$exp}&s={$sig}";
    }

    public static function publicUrl(?int $id): ?string
    {
        return $id ? "/media/{$id}" : null;
    }

    public static function checkSignature(int $id, int $exp, string $sig): bool
    {
        if ($exp < Clock::time()) return false;
        $uid = (int) Auth::id();
        $expected = substr(hash_hmac('sha256', "{$id}|{$exp}|{$uid}", app_key()), 0, 32);
        return hash_equals($expected, $sig);
    }

    /** Entrega o arquivo com suporte a Range (necessário para vídeo/áudio no Safari). */
    public static function serve(array $file, bool $download = false): never
    {
        $path = self::fullPath($file);
        if (!is_file($path)) {
            abort(404, 'Arquivo não encontrado.');
        }
        $size = filesize($path);
        $start = 0;
        $end = $size - 1;
        $inlineSafe = str_starts_with($file['mime'], 'image/') || str_starts_with($file['mime'], 'video/') || str_starts_with($file['mime'], 'audio/');
        header('Content-Type: ' . $file['mime']);
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        header('Accept-Ranges: bytes');
        header(in_array($file['context'], ['public', 'brand'], true) ? 'Cache-Control: public, max-age=86400' : 'Cache-Control: private, no-store, max-age=0');
        $disp = ($download || !$inlineSafe) ? 'attachment' : 'inline';
        header("Content-Disposition: {$disp}; filename*=UTF-8''" . rawurlencode($file['original_name']));
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] === '' && $m[2] !== '') {
                $start = max(0, $size - (int) $m[2]);
            } else {
                $start = (int) $m[1];
                if ($m[2] !== '') $end = min((int) $m[2], $size - 1);
            }
            if ($start > $end || $start >= $size) {
                header("Content-Range: bytes */{$size}");
                http_response_code(416);
                exit;
            }
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        }
        header('Content-Length: ' . ($end - $start + 1));
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') exit;
        $fp = fopen($path, 'rb');
        fseek($fp, $start);
        $left = $end - $start + 1;
        while ($left > 0 && !feof($fp)) {
            $chunk = fread($fp, (int) min(8192 * 8, $left));
            echo $chunk;
            $left -= strlen($chunk);
            @flush();
        }
        fclose($fp);
        exit;
    }

    public static function forContext(string $context, int $contextId): array
    {
        return DB::all('SELECT * FROM files WHERE context = ? AND context_id = ? AND deleted_at IS NULL ORDER BY id', [$context, $contextId]);
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024) . ' KB';
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
}
