<?php
declare(strict_types=1);

final class SubscriptionController
{
    public function show(): void
    {
        $uid = (int) Auth::id();
        View::render('student/subscription', [
            'title' => 'Assinatura', 'nav' => 'subscription',
            'plans' => Billing::plans(), 'standing' => Billing::standing($uid),
            'current' => Billing::currentSubscription($uid), 'pending' => Billing::pendingSubscription($uid),
            'payments' => DB::all('SELECT * FROM payments WHERE student_id = ? ORDER BY id DESC LIMIT 24', [$uid]),
            'mpReady' => MercadoPago::configured(),
        ]);
    }

    public function checkout(): void
    {
        $u = Auth::user();
        if (!RateLimit::hit('checkout:' . $u['id'], 10, 3600)) {
            flash('error', 'Muitas tentativas de pagamento. Aguarde um pouco.');
            redirect('/app/assinatura');
        }
        try {
            $url = Billing::startCheckout($u, (int) input('plan_id'), (string) input('mode'));
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect('/app/assinatura');
        }
        redirect($url);
    }

    /** Retorno do Mercado Pago: consulta a API antes de mostrar qualquer status. */
    public function returned(): void
    {
        $ref = (string) input('ref', '');
        $sub = DB::one('SELECT * FROM subscriptions WHERE external_reference = ? AND student_id = ?', [$ref, Auth::id()]);
        if ($sub) {
            try {
                Billing::reconcileSubscription($sub);
            } catch (Throwable $e) {
                Log::warning('Reconciliação no retorno falhou', ['e' => $e->getMessage()]);
            }
            $sub = DB::one('SELECT * FROM subscriptions WHERE id = ?', [$sub['id']]);
        }
        if ($sub && $sub['status'] === 'active') {
            flash('success', 'Pagamento confirmado pelo Mercado Pago. Obrigado!');
        } else {
            flash('info', 'Recebemos seu retorno. A confirmação do pagamento é feita pelo Mercado Pago e pode levar alguns minutos (Pix e boleto podem demorar mais).');
        }
        redirect('/app/assinatura');
    }

    public function cancel(int $id): void
    {
        $sub = DB::one('SELECT * FROM subscriptions WHERE id = ? AND student_id = ?', [$id, Auth::id()]) ?? abort(404);
        try {
            Billing::cancelRenewal($sub);
            flash('success', 'Renovação automática cancelada. Seu acesso continua até o fim do período pago.');
        } catch (UserError $e) {
            flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Cancelamento falhou', ['e' => $e->getMessage()]);
            flash('error', 'Não foi possível cancelar agora. Tente novamente ou fale com o Renan.');
        }
        redirect('/app/assinatura');
    }
}
