<?php
declare(strict_types=1);

final class Http
{
    /** @return array{status:int, body:string, json:?array} */
    public static function request(string $method, string $url, array $headers = [], string|array|null $body = null, int $timeout = 20): array
    {
        $ch = curl_init($url);
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = "{$k}: {$v}";
        }
        if (is_array($body)) {
            $body = http_build_query($body);
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $h,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Falha HTTP {$method} {$url}: {$err}");
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $json = json_decode((string) $resp, true);
        return ['status' => $status, 'body' => (string) $resp, 'json' => is_array($json) ? $json : null];
    }
}
