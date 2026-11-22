<div class="page-head">
  <div><h1>Assinatura</h1><p>Situação financeira: <span class="badge <?= e($standing['tone']) ?>"><?= e($standing['label']) ?></span></p></div>
</div>

<?php if ($current): ?>
  <div class="card mb">
    <div class="card-head"><h3><?= e($current['plan_name']) ?></h3><span class="badge <?= $current['status'] === 'active' ? 'ok' : ($current['status'] === 'payment_failed' ? 'danger' : '') ?>"><?= e(Billing::STATUS_LABELS[$current['status']] ?? $current['status']) ?></span></div>
    <dl class="kv">
      <dt>Forma</dt><dd><?= $current['mode'] === 'recurring' ? 'Recorrente no cartão' : 'Pagamento do período (Pix, boleto ou cartão)' ?></dd>
      <dt>Valor</dt><dd><?= money((int) $current['amount_cents']) ?> a cada <?= (int) $current['period_months'] === 1 ? 'mês' : (int) $current['period_months'] . ' meses' ?></dd>
      <dt>Válida até</dt><dd><?= $current['current_period_end'] ? e(fmt_date($current['current_period_end'])) : 'Aguardando primeira cobrança' ?></dd>
      <?php if ($current['next_charge_on'] && $current['status'] === 'active'): ?><dt>Próxima cobrança</dt><dd><?= e(fmt_date($current['next_charge_on'])) ?></dd><?php endif; ?>
    </dl>
    <?php if ($current['mode'] === 'recurring' && in_array($current['status'], ['active', 'payment_failed', 'paused'], true)): ?>
      <form method="post" action="/app/assinatura/<?= (int) $current['id'] ?>/cancelar" class="mt" data-confirm="Cancelar a renovação automática? Seu acesso continua até o fim do período pago.">
        <?= csrf_field() ?><button class="btn danger sm" type="submit">Cancelar renovação</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($pending): ?>
  <div class="callout warn mb"><?= icon('clock') ?><div class="grow"><b>Pagamento em processamento</b><p class="small">Iniciado em <?= e(fmt_datetime($pending['created_at'])) ?> (<?= e($pending['plan_name']) ?>). A confirmação vem do Mercado Pago e pode levar alguns minutos.</p></div><?php if ($pending['checkout_url']): ?><a class="btn sm secondary" href="<?= e($pending['checkout_url']) ?>">Retomar pagamento</a><?php endif; ?></div>
<?php endif; ?>

<?php if (!$mpReady): ?>
  <div class="callout mb"><?= icon('alert') ?><p class="small">Os pagamentos online ainda não foram ativados. Para contratar, fale com o Renan pelas mensagens.</p></div>
<?php endif; ?>

<?php if ($plans): ?>
  <p class="section-title">Planos disponíveis</p>
  <div class="grid grid-3">
    <?php foreach ($plans as $p): ?>
      <form class="plan" method="post" action="/app/assinatura/checkout" data-loading>
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
        <h3><?= e($p['name']) ?></h3>
        <div><span class="price"><?= money((int) $p['price_cents']) ?></span> <span class="muted small">/ <?= (int) $p['period_months'] === 1 ? 'mês' : (int) $p['period_months'] . ' meses' ?></span></div>
        <?php if ($p['description']): ?><p class="small muted pre"><?= e($p['description']) ?></p><?php endif; ?>
        <div class="stack-sm mt">
          <?php if ($p['allow_recurring']): ?><label class="check"><input type="radio" name="mode" value="recurring" checked> <span>Recorrente no cartão (renova automaticamente)</span></label><?php endif; ?>
          <?php if ($p['allow_one_time']): ?><label class="check"><input type="radio" name="mode" value="one_time"<?= $p['allow_recurring'] ? '' : ' checked' ?>> <span>Pagar o período (Pix, boleto ou cartão)</span></label><?php endif; ?>
        </div>
        <button class="btn mt" type="submit"<?= $mpReady ? '' : ' disabled' ?>>Ir para o pagamento seguro</button>
        <p class="xs muted">Você será direcionado ao Mercado Pago. Nenhum dado de cartão é armazenado no <?= e(Settings::get('brand_name')) ?>.</p>
      </form>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card empty"><?= icon('card', 'icon-lg') ?><h3>Nenhum plano disponível no momento</h3><p>O Renan ainda não configurou os planos online.</p></div>
<?php endif; ?>

<div class="section">
  <p class="section-title">Histórico de pagamentos</p>
  <div class="card">
    <?php if (!$payments): ?><p class="muted small">Nenhum pagamento registrado.</p><?php else: ?>
    <div class="table-wrap"><table class="table stack-mobile">
      <thead><tr><th>Data</th><th>Valor</th><th>Forma</th><th>Situação</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $pm): ?>
          <tr>
            <td data-label="Data"><?= e(fmt_datetime($pm['paid_at'] ?? $pm['created_at'])) ?></td>
            <td data-label="Valor"><?= money((int) $pm['amount_cents']) ?></td>
            <td data-label="Forma"><?= e(['credit_card' => 'Cartão de crédito', 'debit_card' => 'Cartão de débito', 'bank_transfer' => 'Pix', 'ticket' => 'Boleto', 'account_money' => 'Saldo Mercado Pago'][$pm['method']] ?? ($pm['method'] ?: '—')) ?></td>
            <td data-label="Situação"><span class="badge <?= $pm['status'] === 'approved' ? 'ok' : (in_array($pm['status'], ['rejected', 'cancelled'], true) ? 'danger' : 'warn') ?>"><?= e(['approved' => 'Aprovado', 'pending' => 'Pendente', 'in_process' => 'Em análise', 'rejected' => 'Recusado', 'cancelled' => 'Cancelado', 'refunded' => 'Estornado', 'charged_back' => 'Contestado'][$pm['status']] ?? $pm['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>
