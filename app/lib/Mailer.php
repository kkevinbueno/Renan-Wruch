<?php
declare(strict_types=1);

/**
 * Envio de e-mail sem dependências: SMTP (SSL/STARTTLS + AUTH LOGIN), mail() do PHP ou arquivo (log).
 * Na HostGator, use a conta de e-mail do próprio domínio (mail.seudominio.com, porta 465 SSL)
 * ou um provedor transacional (Brevo, Amazon SES, Mailgun, Resend via SMTP).
 */
final class Mailer
{
    public static function send(string $to, string $toName, string $subject, string $html, string $text): void
    {
        $transport = config('mail.transport');
        $message = self::build($to, $toName, $subject, $html, $text);
        match ($transport) {
            'log' => self::toFile($to, $message),
            'mail' => self::viaMail($to, $subject, $message),
            default => self::viaSmtp($to, $message),
        };
    }

    private static function encodeHeader(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private static function build(string $to, string $toName, string $subject, string $html, string $text): array
    {
        $from = (string) config('mail.from');
        $boundary = 'b' . bin2hex(random_bytes(12));
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $headers = [
            'Date' => date('r', Clock::time()),
            'From' => self::encodeHeader((string) config('mail.from_name')) . " <{$from}>",
            'To' => ($toName !== '' ? self::encodeHeader($toName) . ' ' : '') . "<{$to}>",
            'Subject' => self::encodeHeader($subject),
            'Message-ID' => '<' . bin2hex(random_bytes(16)) . "@{$domain}>",
            'MIME-Version' => '1.0',
            'Content-Type' => "multipart/alternative; boundary=\"{$boundary}\"",
            'X-Mailer' => 'WruchPersonal',
        ];
        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--{$boundary}--\r\n";
        return ['headers' => $headers, 'body' => $body];
    }

    private static function raw(array $message): string
    {
        $h = '';
        foreach ($message['headers'] as $k => $v) {
            $h .= "{$k}: {$v}\r\n";
        }
        return $h . "\r\n" . $message['body'];
    }

    private static function toFile(string $to, array $message): void
    {
        $dir = storage_path('mail');
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        $name = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '-' . preg_replace('/[^a-z0-9]+/i', '_', $to) . '.eml';
        file_put_contents($dir . '/' . $name, self::raw($message));
    }

    private static function viaMail(string $to, string $subject, array $message): void
    {
        $headers = $message['headers'];
        unset($headers['To'], $headers['Subject']);
        $h = '';
        foreach ($headers as $k => $v) $h .= "{$k}: {$v}\r\n";
        if (!mail($to, self::encodeHeader($subject), $message['body'], rtrim($h), '-f' . config('mail.from'))) {
            throw new RuntimeException('mail() retornou falha');
        }
    }

    private static function viaSmtp(string $to, array $message): void
    {
        $host = (string) config('mail.host');
        $port = (int) config('mail.port');
        $enc = (string) config('mail.encryption');
        if ($host === '') {
            throw new RuntimeException('MAIL_HOST não configurado');
        }
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new RuntimeException("Conexão SMTP falhou: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 30);
        $read = function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };
        $cmd = function (string $c, array $expect) use ($fp, $read): string {
            if ($c !== '') fwrite($fp, $c . "\r\n");
            $resp = $read();
            if (!in_array((int) substr($resp, 0, 3), $expect, true)) {
                throw new RuntimeException('SMTP: resposta inesperada para "' . (str_starts_with($c, 'AUTH') || strlen($c) > 60 ? substr($c, 0, 10) . '…' : $c) . '": ' . trim($resp));
            }
            return $resp;
        };
        try {
            $cmd('', [220]);
            $ehloHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $cmd("EHLO {$ehloHost}", [250]);
            if ($enc === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('Falha ao iniciar TLS');
                }
                $cmd("EHLO {$ehloHost}", [250]);
            }
            if (config('mail.user') !== '') {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode((string) config('mail.user')), [334]);
                $cmd(base64_encode((string) config('mail.pass')), [235]);
            }
            $cmd('MAIL FROM:<' . config('mail.from') . '>', [250]);
            $cmd("RCPT TO:<{$to}>", [250, 251]);
            $cmd('DATA', [354]);
            $raw = self::raw($message);
            // Dot-stuffing (RFC 5321).
            $raw = preg_replace('/^\./m', '..', str_replace(["\r\n", "\n"], ["\n", "\r\n"], $raw));
            fwrite($fp, $raw . "\r\n.\r\n");
            $cmd('', [250]);
            $cmd('QUIT', [221]);
        } finally {
            fclose($fp);
        }
    }
}
