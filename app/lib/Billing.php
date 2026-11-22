<?php
declare(strict_types=1);

/**
 * Assinaturas e pagamentos.
 * Estados separados e nunca confundidos: cadastro aprovado (users.status), avaliação inicial
 * (student_profiles.onboarding_completed_at), treino publicado (workout_versions) e assinatura ativa (subscriptions).
 * Pagamentos só mudam de estado com dados obtidos da API do Mercado Pago (webhook ou reconciliação),
 * nunca porque o aluno voltou à página de sucesso.
 */
final class Billing
{
    public const STATUS_LABELS = [
        'pending' => 'Aguardando pagamento', 'active' => 'Ativa', 'payment_failed' => 'Pagamento recusado',
        'paused' => 'Pausada', 'cancelled' => 'Renovação cancelada', 'expired' => 'Expirada',
    ];

    public static function plans(bool $activeOnly = true): array
    {
        return DB::all('SELECT * FROM plans' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, price_cents');
    }

    public static function currentSubscription(int $studentId): ?array
    {
        return DB::one("SELECT s.*, p.name AS plan_name, p.period_months FROM subscriptions s JOIN plans p ON p.id = s.plan_id
            WHERE s.student_id = ? AND s.status IN ('active','payment_failed','paused','cancelled')
            ORDER BY CASE s.status WHEN 'active' THEN 0 WHEN 'payment_failed' THEN 1 ELSE 2 END, s.current_period_end DESC, s.id DESC LIMIT 1", [$studentId]);
    }

    public static function pendingSubscription(int $studentId): ?array
    {
        return DB::one("SELECT s.*, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.student_id = ? AND s.status = 'pending' ORDER BY s.id DESC LIMIT 1", [$studentId]);
    }

    /** Situação financeira resumida para exibição. */
    public static function standing(int $studentId): array
    {
        $sub = self::currentSubscription($studentId);
        if (!$sub) {
            return ['label' => 'Sem assinatura', 'tone' => 'muted', 'sub' => null];
        }
        $grace = Settings::int('billing_grace_days');
        $valid = $sub['current_period_end'] && $sub['current_period_end'] >= today();
        $inGrace = $sub['current_period_end'] && date_add_days($sub['current_period_end'], $grace) >= today();
        return match (true) {
            $sub['status'] === 'active' && ($valid || !$sub['current_period_end']) => ['label' => 'Em dia', 'tone' => 'ok', 'sub' => $sub],
            $sub['status'] === 'cancelled' && $valid => ['label' => 'Ativa até ' . fmt_date_short($sub['current_period_end']), 'tone' => 'ok', 'sub' => $sub],
            $inGrace => ['label' => 'Em carência', 'tone' => 'warn', 'sub' => $sub],
            default => ['label' => 'Inadimplente', 'tone' => 'danger', 'sub' => $sub],
        };
    }

    /** Política configurável de acesso por inadimplência. Nunca apaga dados. */
    public static function hasAccess(int $userId): bool
    {
        $u = DB::one('SELECT role FROM users WHERE id = ?', [$userId]);
        if (!$u || $u['role'] !== 'student') return true;
        if (Settings::get('billing_policy') !== 'restrict') return true;
        $p = Students::profile($userId);
        if ($p['access_override'] === 'allow') return true;
        $st = self::standing($userId);
        return in_array($st['tone'], ['ok', 'warn'], true);
    }

    public static function startCheckout(array $user, int $planId, string $mode): string
    {
        $plan = DB::one('SELECT * FROM plans WHERE id = ? AND active = 1', [$planId]);
        if (!$plan) throw new UserError('Plano indisponível.');
        if ($mode === 'recurring' && !$plan['allow_recurring']) throw new UserError('Este plano não aceita cobrança recorrente.');
        if ($mode === 'one_time' && !$plan['allow_one_time']) throw new UserError('Este plano aceita somente cobrança recorrente.');
        if (!in_array($mode, ['recurring', 'one_time'], true)) throw new UserError('Forma de cobrança inválida.');
        if (!MercadoPago::configured()) throw new UserError('Pagamentos online ainda não foram configurados. Fale com o Renan.');
        $ref = 'wp' . bin2hex(random_bytes(10));
        $subId = DB::insert('subscriptions', [
            'student_id' => $user['id'], 'plan_id' => $plan['id'], 'mode' => $mode, 'status' => 'pending',
            'external_reference' => $ref, 'amount_cents' => $plan['price_cents'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sub = DB::one('SELECT * FROM subscriptions WHERE id = ?', [$subId]);
        try {
            $r = $mode === 'recurring' ? MercadoPago::createPreapproval($sub, $plan, $user) : MercadoPago::createPreference($sub, $plan, $user);
        } catch (Throwable $e) {
            DB::update('subscriptions', ['status' => 'expired', 'updated_at' => now()], 'id = ?', [$subId]);
            throw $e instanceof UserError ? $e : new UserError('Não foi possível iniciar o pagamento agora. Tente novamente em alguns minutos.');
        }
        $checkout = (string) ($r['init_point'] ?? '');
        DB::update('subscriptions', ['provider_ref' => (string) ($r['id'] ?? ''), 'checkout_url' => $checkout, 'updated_at' => now()], 'id = ?', [$subId]);
        Audit::log('billing.checkout', 'subscription', $subId, ['plan' => $plan['id'], 'mode' => $mode]);
        if (!str_starts_with($checkout, 'https://')) throw new UserError('Resposta inesperada do Mercado Pago.');
        return $checkout;
    }

    private static function addMonths(string $date, int $months): string
    {
        return (new DateTimeImmutable($date))->modify("+{$months} months")->format('Y-m-d');
    }

    private static function subByRef(?string $ref): ?array
    {
        if (!$ref) return null;
        return DB::one('SELECT s.*, p.period_months, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.external_reference = ?', [$ref]);
    }

    /** Registra/atualiza um pagamento vindo da API do Mercado Pago. Idempotente. */
    public static function applyPayment(array $mp, ?array $sub = null): void
    {
        $sub ??= self::subByRef($mp['external_reference'] ?? null);
        if (!$sub) {
            Log::warning('Pagamento sem assinatura correspondente', ['payment' => $mp['id'] ?? null, 'ref' => $mp['external_reference'] ?? null]);
            return;
        }
        $pid = (string) $mp['id'];
        $status = (string) ($mp['status'] ?? 'unknown');
        $existing = DB::one('SELECT * FROM payments WHERE provider_payment_id = ?', [$pid]);
        $data = [
            'subscription_id' => $sub['id'],
            'student_id' => $sub['student_id'],
            'amount_cents' => (int) round(((float) ($mp['transaction_amount'] ?? 0)) * 100),
            'status' => $status,
            'status_detail' => $mp['status_detail'] ?? null,
            'method' => $mp['payment_type_id'] ?? ($mp['payment_method_id'] ?? null),
            'paid_at' => !empty($mp['date_approved']) ? gmdate('Y-m-d H:i:s', strtotime($mp['date_approved'])) : null,
            'raw' => json_enc($mp),
            'updated_at' => now(),
        ];
        DB::transaction(function () use ($existing, $data, $pid, $sub, $status) {
            if ($existing) {
                DB::update('payments', $data, 'id = ?', [$existing['id']]);
            } else {
                DB::insert('payments', $data + ['provider_payment_id' => $pid, 'created_at' => now()]);
            }
            $wasApproved = $existing && $existing['status'] === 'approved';
            if ($status === 'approved' && !$wasApproved) {
                $base = ($sub['current_period_end'] && $sub['current_period_end'] > today()) ? $sub['current_period_end'] : today();
                DB::update('subscriptions', [
                    'status' => $sub['status'] === 'cancelled' ? 'cancelled' : 'active',
                    'current_period_end' => self::addMonths($base, (int) $sub['period_months']),
                    'updated_at' => now(),
                ], 'id = ?', [$sub['id']]);
                // Outras tentativas pendentes do mesmo aluno deixam de valer.
                DB::run("UPDATE subscriptions SET status = 'expired', updated_at = ? WHERE student_id = ? AND status = 'pending' AND id <> ?", [now(), $sub['student_id'], $sub['id']]);
            } elseif (in_array($status, ['rejected', 'cancelled'], true) && !$wasApproved) {
                if ($sub['mode'] === 'recurring' && $sub['status'] === 'active') {
                    DB::update('subscriptions', ['status' => 'payment_failed', 'updated_at' => now()], 'id = ?', [$sub['id']]);
                }
            } elseif (in_array($status, ['refunded', 'charged_back'], true) && $wasApproved && $sub['mode'] === 'one_time') {
                DB::update('subscriptions', ['status' => 'cancelled', 'current_period_end' => today(), 'updated_at' => now()], 'id = ?', [$sub['id']]);
            }
        });
        if ($status === 'approved' && !($existing && $existing['status'] === 'approved')) {
            Audit::log('billing.payment_approved', 'payment', null, ['provider_id' => $pid, 'sub' => $sub['id']], null);
            Notify::send((int) $sub['student_id'], 'payment_approved', ['amount' => money($data['amount_cents'])], ['dedupe' => "payok:{$pid}"]);
        } elseif ($status === 'rejected' && !$existing) {
            $student = DB::one('SELECT name FROM users WHERE id = ?', [$sub['student_id']]);
            Notify::send((int) $sub['student_id'], 'payment_failed', [], ['dedupe' => "payfail:{$pid}"]);
            Notify::toTrainer('payment_failed_trainer', ['student' => $student['name'] ?? ''], ['dedupe' => "payfailt:{$pid}"]);
        }
    }

    public static function applyPreapproval(array $mp): void
    {
        $sub = self::subByRef($mp['external_reference'] ?? null)
            ?? DB::one('SELECT s.*, p.period_months, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.provider_ref = ?', [(string) ($mp['id'] ?? '')]);
        if (!$sub) {
            Log::warning('Preapproval sem assinatura correspondente', ['id' => $mp['id'] ?? null]);
            return;
        }
        $map = ['authorized' => 'active', 'paused' => 'paused', 'cancelled' => 'cancelled', 'pending' => 'pending'];
        $new = $map[$mp['status'] ?? ''] ?? $sub['status'];
        // Mantém "pagamento recusado" até um pagamento aprovado chegar.
        if ($new === 'active' && $sub['status'] === 'payment_failed') $new = 'payment_failed';
        $upd = ['status' => $new, 'updated_at' => now(), 'provider_ref' => (string) $mp['id']];
        if (!empty($mp['next_payment_date'])) {
            $upd['next_charge_on'] = substr((string) $mp['next_payment_date'], 0, 10);
        }
        if ($new === 'cancelled' && !$sub['cancelled_at']) {
            $upd['cancelled_at'] = now();
        }
        DB::update('subscriptions', $upd, 'id = ?', [$sub['id']]);
        if ($new !== $sub['status']) {
            Audit::log('billing.subscription_status', 'subscription', (int) $sub['id'], ['from' => $sub['status'], 'to' => $new], null);
            if ($new === 'cancelled') {
                Notify::send((int) $sub['student_id'], 'subscription_cancelled', [], ['dedupe' => 'subcancel:' . $sub['id']]);
            }
        }
    }

    public static function applyAuthorizedPayment(array $ap): void
    {
        $sub = DB::one('SELECT s.*, p.period_months FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.provider_ref = ?', [(string) ($ap['preapproval_id'] ?? '')]);
        if (!$sub || empty($ap['payment']['id'])) {
            return;
        }
        $mp = [
            'id' => $ap['payment']['id'],
            'status' => $ap['payment']['status'] ?? 'unknown',
            'status_detail' => $ap['payment']['status_detail'] ?? null,
            'transaction_amount' => $ap['transaction_amount'] ?? 0,
            'payment_type_id' => 'credit_card',
            'date_approved' => ($ap['payment']['status'] ?? '') === 'approved' ? ($ap['last_modified'] ?? $ap['date_created'] ?? gmdate('c')) : null,
            'external_reference' => $sub['external_reference'],
            'authorized_payment' => $ap,
        ];
        self::applyPayment($mp, $sub);
        if (($ap['payment']['status'] ?? '') === 'rejected') {
            DB::update('subscriptions', ['status' => 'payment_failed', 'updated_at' => now()], "id = ? AND status = 'active'", [$sub['id']]);
        }
    }

    /** Processa uma notificação do Mercado Pago. Retorna o código HTTP de resposta. */
    public static function handleWebhook(array $query, array $server, string $rawBody): int
    {
        $body = json_decode($rawBody, true) ?: [];
        $topic = (string) ($query['type'] ?? $query['topic'] ?? $body['type'] ?? $body['topic'] ?? '');
        $dataId = (string) ($query['data_id'] ?? $body['data']['id'] ?? $query['id'] ?? '');
        $secret = (string) config('mercadopago.webhook_secret');
        if ($secret !== '') {
            if (!MercadoPago::validSignature((string) ($server['HTTP_X_SIGNATURE'] ?? ''), (string) ($server['HTTP_X_REQUEST_ID'] ?? ''), (string) ($query['data_id'] ?? $dataId), $secret)) {
                Log::warning('Webhook Mercado Pago com assinatura inválida', ['topic' => $topic, 'id' => $dataId]);
                return 401;
            }
        } else {
            Log::warning('MP_WEBHOOK_SECRET não configurado: webhook aceito, mas os dados serão sempre confirmados na API.');
        }
        if ($topic === '' || $dataId === '') {
            return 200;
        }
        $key = 'mp:' . ($body['id'] ?? '') . ':' . $topic . ':' . $dataId . ':' . ($body['action'] ?? '');
        $event = DB::one('SELECT * FROM webhook_events WHERE event_key = ?', [$key]);
        if ($event && $event['status'] === 'processed') {
            return 200; // Já processado (idempotência).
        }
        if (!$event) {
            try {
                $eid = DB::insert('webhook_events', [
                    'provider' => 'mercadopago', 'event_key' => $key, 'topic' => $topic, 'resource_id' => $dataId,
                    'payload' => mb_substr($rawBody, 0, 20000), 'status' => 'received', 'received_at' => now(),
                ]);
            } catch (PDOException $e) {
                if (DB::isUniqueViolation($e)) return 200;
                throw $e;
            }
        } else {
            $eid = (int) $event['id'];
        }
        try {
            self::processTopic($topic, $dataId);
            DB::update('webhook_events', ['status' => 'processed', 'processed_at' => now(), 'error' => null], 'id = ?', [$eid]);
            return 200;
        } catch (Throwable $e) {
            DB::run('UPDATE webhook_events SET status = ?, error = ?, attempts = attempts + 1 WHERE id = ?', ['error', mb_substr($e->getMessage(), 0, 1000), $eid]);
            Log::error('Erro ao processar webhook', ['topic' => $topic, 'id' => $dataId, 'erro' => $e->getMessage()]);
            return 500; // O Mercado Pago tentará novamente.
        }
    }

    private static function processTopic(string $topic, string $id): void
    {
        match ($topic) {
            'payment' => self::applyPayment(MercadoPago::payment($id)),
            'subscription_preapproval', 'preapproval' => self::applyPreapproval(MercadoPago::preapproval($id)),
            'subscription_authorized_payment', 'authorized_payment' => self::applyAuthorizedPayment(MercadoPago::authorizedPayment($id)),
            default => null,
        };
    }

    /** Consulta o Mercado Pago e atualiza uma assinatura (página de retorno e cron). */
    public static function reconcileSubscription(array $sub): void
    {
        if (!MercadoPago::configured()) return;
        if ($sub['mode'] === 'recurring' && $sub['provider_ref']) {
            self::applyPreapproval(MercadoPago::preapproval((string) $sub['provider_ref']));
        }
        foreach (MercadoPago::searchPaymentsByReference((string) $sub['external_reference']) as $p) {
            self::applyPayment($p);
        }
    }

    public static function reconcileAll(): int
    {
        $n = 0;
        $limit = gmdate('Y-m-d H:i:s', Clock::time() - 86400 * 10);
        $subs = DB::all("SELECT * FROM subscriptions WHERE (status = 'pending' AND created_at >= ?) OR (mode = 'recurring' AND status IN ('active','payment_failed','paused'))", [$limit]);
        foreach ($subs as $s) {
            try {
                self::reconcileSubscription($s);
                $n++;
            } catch (Throwable $e) {
                Log::warning('Reconciliação falhou', ['sub' => $s['id'], 'erro' => $e->getMessage()]);
            }
        }
        // Tentativas de pagamento abandonadas há mais de 10 dias.
        DB::run("UPDATE subscriptions SET status = 'expired', updated_at = ? WHERE status = 'pending' AND created_at < ?", [now(), $limit]);
        // Pagamentos avulsos vencidos além da carência.
        $grace = Settings::int('billing_grace_days');
        DB::run("UPDATE subscriptions SET status = 'expired', updated_at = ? WHERE mode = 'one_time' AND status = 'active' AND current_period_end < ?", [now(), date_add_days(today(), -$grace)]);
        return $n;
    }

    public static function cancelRenewal(array $sub): void
    {
        if ($sub['mode'] !== 'recurring') {
            throw new UserError('Pagamentos avulsos não têm renovação automática.');
        }
        if ($sub['provider_ref'] && MercadoPago::configured()) {
            self::applyPreapproval(MercadoPago::cancelPreapproval((string) $sub['provider_ref']) + ['external_reference' => $sub['external_reference']]);
        }
        DB::update('subscriptions', ['status' => 'cancelled', 'cancel_requested_at' => now(), 'cancelled_at' => now(), 'updated_at' => now()], 'id = ?', [$sub['id']]);
        Audit::log('billing.cancel_renewal', 'subscription', (int) $sub['id']);
    }
}
