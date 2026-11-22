<?php
declare(strict_types=1);

/**
 * Integração oficial via API REST do Mercado Pago (sem SDK, compatível com hospedagem compartilhada).
 * - Assinatura recorrente (cartão): POST /preapproval com status "pending" → o aluno conclui no Mercado Pago.
 * - Pagamento do período (Pix, boleto ou cartão): Checkout Pro, POST /checkout/preferences.
 * Nenhum dado de cartão passa pelo servidor do Wruch Personal.
 */
final class MercadoPago
{
    public static function configured(): bool
    {
        return config('mercadopago.access_token') !== '';
    }

    private static function api(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
    {
        if (!self::configured()) {
            throw new UserError('Pagamentos online ainda não configurados (MP_ACCESS_TOKEN).');
        }
        $headers = [
            'Authorization' => 'Bearer ' . config('mercadopago.access_token'),
            'Content-Type' => 'application/json',
        ];
        if ($idempotencyKey) {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }
        $r = Http::request($method, config('mercadopago.api_base') . $path, $headers, $body !== null ? json_enc($body) : null);
        if ($r['status'] >= 400 || $r['json'] === null) {
            Log::error('Mercado Pago API erro', ['path' => $path, 'status' => $r['status'], 'body' => mb_substr($r['body'], 0, 800)]);
            throw new RuntimeException("Mercado Pago respondeu {$r['status']} em {$path}");
        }
        return $r['json'];
    }

    public static function createPreapproval(array $sub, array $plan, array $user): array
    {
        return self::api('POST', '/preapproval', [
            'reason' => Settings::get('brand_name') . ' — ' . $plan['name'],
            'external_reference' => $sub['external_reference'],
            'payer_email' => $user['email'],
            'auto_recurring' => [
                'frequency' => (int) $plan['period_months'],
                'frequency_type' => 'months',
                'transaction_amount' => round($plan['price_cents'] / 100, 2),
                'currency_id' => 'BRL',
            ],
            'back_url' => url('/app/assinatura/retorno', ['ref' => $sub['external_reference']]),
            'status' => 'pending',
        ], 'preapproval-' . $sub['external_reference']);
    }

    public static function createPreference(array $sub, array $plan, array $user): array
    {
        $ret = url('/app/assinatura/retorno', ['ref' => $sub['external_reference']]);
        return self::api('POST', '/checkout/preferences', [
            'items' => [[
                'id' => 'plan-' . $plan['id'],
                'title' => Settings::get('brand_name') . ' — ' . $plan['name'],
                'description' => 'Consultoria esportiva online (' . $plan['period_months'] . ' ' . ($plan['period_months'] == 1 ? 'mês' : 'meses') . ')',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round($plan['price_cents'] / 100, 2),
            ]],
            'payer' => ['email' => $user['email'], 'name' => $user['name']],
            'external_reference' => $sub['external_reference'],
            'back_urls' => ['success' => $ret, 'pending' => $ret, 'failure' => $ret],
            'auto_return' => 'approved',
            'notification_url' => url('/webhooks/mercadopago'),
            'statement_descriptor' => config('mercadopago.statement_descriptor'),
        ], 'pref-' . $sub['external_reference']);
    }

    public static function payment(string $id): array
    {
        return self::api('GET', '/v1/payments/' . rawurlencode($id));
    }

    public static function preapproval(string $id): array
    {
        return self::api('GET', '/preapproval/' . rawurlencode($id));
    }

    public static function authorizedPayment(string $id): array
    {
        return self::api('GET', '/authorized_payments/' . rawurlencode($id));
    }

    public static function searchPaymentsByReference(string $ref): array
    {
        $r = self::api('GET', '/v1/payments/search?' . http_build_query(['external_reference' => $ref, 'sort' => 'date_created', 'criteria' => 'desc']));
        return $r['results'] ?? [];
    }

    public static function cancelPreapproval(string $id): array
    {
        return self::api('PUT', '/preapproval/' . rawurlencode($id), ['status' => 'cancelled']);
    }

    /**
     * Valida o cabeçalho x-signature (HMAC-SHA256) conforme a documentação:
     * manifesto "id:{data.id};request-id:{x-request-id};ts:{ts};" — partes ausentes são omitidas.
     */
    public static function validSignature(string $xSignature, string $xRequestId, string $dataId, string $secret): bool
    {
        if ($secret === '' || $xSignature === '') {
            return false;
        }
        $ts = $v1 = null;
        foreach (explode(',', $xSignature) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) continue;
            if ($kv[0] === 'ts') $ts = $kv[1];
            if ($kv[0] === 'v1') $v1 = $kv[1];
        }
        if (!$ts || !$v1) {
            return false;
        }
        if (ctype_alnum($dataId)) {
            $dataId = strtolower($dataId);
        }
        $manifest = '';
        if ($dataId !== '') $manifest .= "id:{$dataId};";
        if ($xRequestId !== '') $manifest .= "request-id:{$xRequestId};";
        $manifest .= "ts:{$ts};";
        return hash_equals(hash_hmac('sha256', $manifest, $secret), $v1);
    }
}
