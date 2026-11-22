<?php
declare(strict_types=1);

final class FinanceController
{
    public function index(): void
    {
        $status = (string) input('situacao', '');
        $where = "s.status <> 'expired'";
        $params = [];
        if (isset(Billing::STATUS_LABELS[$status])) { $where = 's.status = ?'; $params[] = $status; }
        View::render('trainer/finance', [
            'title' => 'Financeiro', 'nav' => 'finance', 'status' => $status,
            'plans' => Billing::plans(false),
            'editPlan' => ($pid = input_int('plano')) ? DB::one('SELECT * FROM plans WHERE id = ?', [$pid]) : null,
            'subs' => DB::all("SELECT s.*, u.name, p.name AS plan_name FROM subscriptions s JOIN users u ON u.id = s.student_id JOIN plans p ON p.id = s.plan_id WHERE {$where} ORDER BY s.updated_at DESC LIMIT 100", $params),
            'payments' => DB::all('SELECT pm.*, u.name FROM payments pm JOIN users u ON u.id = pm.student_id ORDER BY pm.id DESC LIMIT 50'),
            'monthTotal' => (int) DB::val("SELECT COALESCE(SUM(amount_cents),0) FROM payments WHERE status = 'approved' AND paid_at >= ?", [local_now()->modify('first day of this month')->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]),
            'mpReady' => MercadoPago::configured(), 'webhookReady' => config('mercadopago.webhook_secret') !== '',
            'webhookErrors' => DB::all("SELECT * FROM webhook_events WHERE status = 'error' ORDER BY id DESC LIMIT 10"),
        ]);
    }

    public function savePlan(): void
    {
        $id = input_int('id');
        $name = mb_substr(trim((string) input('name', '')), 0, 120);
        $price = parse_money((string) input('price', '0'));
        $months = (int) input('period_months', 1);
        if ($name === '' || $price < 100 || !in_array($months, [1, 2, 3, 6, 12], true)) {
            flash('error', 'Informe nome, preço (mínimo R$ 1,00) e periodicidade válida.');
            redirect('/painel/financeiro' . ($id ? "?plano={$id}" : ''));
        }
        $data = [
            'name' => $name, 'description' => mb_substr(trim((string) input('description', '')), 0, 2000) ?: null,
            'price_cents' => $price, 'period_months' => $months,
            'allow_recurring' => (int) (bool) input('allow_recurring'), 'allow_one_time' => (int) (bool) input('allow_one_time'),
            'active' => (int) (bool) input('active'), 'sort_order' => (int) input('sort_order', 0), 'updated_at' => now(),
        ];
        if (!$data['allow_recurring'] && !$data['allow_one_time']) {
            flash('error', 'Habilite pelo menos uma forma de cobrança.');
            redirect('/painel/financeiro' . ($id ? "?plano={$id}" : ''));
        }
        if ($id) {
            // Assinaturas existentes mantêm o valor contratado (amount_cents); novas usam o preço atualizado.
            DB::update('plans', $data, 'id = ?', [$id]);
            Audit::log('plan.update', 'plan', $id, ['price' => $price]);
        } else {
            $id = DB::insert('plans', $data + ['created_at' => now()]);
            Audit::log('plan.create', 'plan', $id, ['price' => $price]);
        }
        flash('success', 'Plano salvo.');
        redirect('/painel/financeiro');
    }

    public function sync(): void
    {
        try {
            $n = Billing::reconcileAll();
            flash('success', "Sincronização concluída ({$n} assinaturas consultadas no Mercado Pago).");
        } catch (Throwable $e) {
            flash('error', 'Falha ao sincronizar: ' . $e->getMessage());
        }
        redirect('/painel/financeiro');
    }
}
