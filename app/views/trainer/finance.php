<?php $p = $editPlan; ?>
<div class="page-head">
  <div><h1>Financeiro</h1><p>Recebido neste mês: <b><?= money($monthTotal) ?></b></p></div>
  <form method="post" action="/painel/financeiro/sincronizar"><?= csrf_field() ?><button class="btn secondary sm" type="submit"<?= $mpReady ? '' : ' disabled' ?>><?= icon('refresh', 'icon-sm') ?> Sincronizar com Mercado Pago</button></form>
</div>

<?php if (!$mpReady): ?><div class="callout warn mb"><?= icon('alert') ?><p class="small">Mercado Pago não configurado: defina <code>MP_ACCESS_TOKEN</code> e <code>MP_WEBHOOK_SECRET</code> no arquivo <code>.env</code> do servidor. Até lá, os alunos veem os planos, mas não conseguem pagar online.</p></div>
<?php elseif (!$webhookReady): ?><div class="callout warn mb"><?= icon('alert') ?><p class="small">Defina <code>MP_WEBHOOK_SECRET</code> para validar a assinatura das notificações do Mercado Pago.</p></div><?php endif; ?>
<?php if ($webhookErrors): ?><div class="callout danger mb"><?= icon('alert') ?><p class="small"><?= plural(count($webhookErrors), 'notificação do Mercado Pago falhou', 'notificações do Mercado Pago falharam') ?> ao processar (o Mercado Pago tenta novamente). Último erro: <?= e($webhookErrors[0]['error']) ?></p></div><?php endif; ?>

<div class="grid grid-main">
  <div class="stack">
    <div class="card">
      <div class="card-head"><h3>Assinaturas</h3>
        <form method="get"><select name="situacao" data-autosubmit style="width:auto;min-height:36px" aria-label="Filtrar situação"><option value="">Todas (exceto expiradas)</option><?php foreach (Billing::STATUS_LABELS as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $status) ?>><?= e($l) ?></option><?php endforeach; ?></select></form>
      </div>
      <?php if (!$subs): ?><p class="small muted">Nenhuma assinatura.</p><?php else: ?>
      <div class="table-wrap"><table class="table stack-mobile">
        <thead><tr><th>Aluno</th><th>Plano</th><th>Forma</th><th>Situação</th><th>Válida até</th></tr></thead>
        <tbody><?php foreach ($subs as $s): ?>
          <tr>
            <td data-label="Aluno"><a href="/painel/alunos/<?= (int) $s['student_id'] ?>?aba=dados"><?= e($s['name']) ?></a></td>
            <td data-label="Plano"><?= e($s['plan_name']) ?> · <?= money((int) $s['amount_cents']) ?></td>
            <td data-label="Forma"><?= $s['mode'] === 'recurring' ? 'Recorrente' : 'Período' ?></td>
            <td data-label="Situação"><span class="badge <?= $s['status'] === 'active' ? 'ok' : ($s['status'] === 'payment_failed' ? 'danger' : ($s['status'] === 'pending' ? 'warn' : '')) ?>"><?= e(Billing::STATUS_LABELS[$s['status']] ?? $s['status']) ?></span></td>
            <td data-label="Válida até"><?= e(fmt_date_short($s['current_period_end'])) ?></td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-head"><h3>Pagamentos recentes</h3></div>
      <?php if (!$payments): ?><p class="small muted">Nenhum pagamento recebido.</p><?php else: ?>
      <div class="table-wrap"><table class="table stack-mobile">
        <thead><tr><th>Data</th><th>Aluno</th><th>Valor</th><th>Situação</th><th>ID Mercado Pago</th></tr></thead>
        <tbody><?php foreach ($payments as $pm): ?>
          <tr><td data-label="Data"><?= e(fmt_datetime($pm['paid_at'] ?? $pm['created_at'])) ?></td><td data-label="Aluno"><?= e($pm['name']) ?></td><td data-label="Valor"><?= money((int) $pm['amount_cents']) ?></td><td data-label="Situação"><span class="badge <?= $pm['status'] === 'approved' ? 'ok' : (in_array($pm['status'], ['rejected', 'cancelled'], true) ? 'danger' : 'warn') ?>"><?= e($pm['status']) ?></span></td><td data-label="ID" class="xs muted"><?= e($pm['provider_payment_id']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stack">
    <div class="card">
      <h3>Planos</h3>
      <?php if (!$plans): ?><p class="small muted mt">Nenhum plano cadastrado.</p><?php endif; ?>
      <div class="list mt"><?php foreach ($plans as $pl): ?><a class="list-item" href="/painel/financeiro?plano=<?= (int) $pl['id'] ?>"><div class="grow"><div class="list-title"><?= e($pl['name']) ?></div><div class="list-sub"><?= money((int) $pl['price_cents']) ?> / <?= (int) $pl['period_months'] ?> <?= (int) $pl['period_months'] === 1 ? 'mês' : 'meses' ?></div></div><span class="badge <?= $pl['active'] ? 'ok' : 'outline' ?>"><?= $pl['active'] ? 'Ativo' : 'Inativo' ?></span></a><?php endforeach; ?></div>
    </div>
    <form class="card" method="post" action="/painel/planos">
      <?= csrf_field() ?>
      <?php if ($p): ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><?php endif; ?>
      <div class="card-head"><h3><?= $p ? 'Editar plano' : 'Novo plano' ?></h3><?php if ($p): ?><a class="small" href="/painel/financeiro">Novo</a><?php endif; ?></div>
      <div class="field"><label for="pn" class="req">Nome</label><input id="pn" name="name" value="<?= e($p['name'] ?? '') ?>" required placeholder="Ex.: Consultoria mensal"></div>
      <div class="form-grid cols-2 mt">
        <div class="field"><label for="pp" class="req">Preço (R$)</label><input id="pp" name="price" inputmode="decimal" value="<?= $p ? e(number_format($p['price_cents'] / 100, 2, ',', '')) : '' ?>" required placeholder="0,00"></div>
        <div class="field"><label for="pm">Periodicidade</label><select id="pm" name="period_months"><?php foreach ([1 => 'Mensal', 2 => 'Bimestral', 3 => 'Trimestral', 6 => 'Semestral', 12 => 'Anual'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $p['period_months'] ?? 1) ?>><?= $l ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label for="pd">Descrição</label><textarea id="pd" name="description" rows="3"><?= e($p['description'] ?? '') ?></textarea></div>
      <label class="check mt"><input type="checkbox" name="allow_recurring" value="1"<?= checked((bool) ($p['allow_recurring'] ?? 1)) ?>> <span>Aceitar assinatura recorrente (cartão)</span></label>
      <label class="check"><input type="checkbox" name="allow_one_time" value="1"<?= checked((bool) ($p['allow_one_time'] ?? 1)) ?>> <span>Aceitar pagamento do período (Pix, boleto, cartão)</span></label>
      <label class="check"><input type="checkbox" name="active" value="1"<?= checked((bool) ($p['active'] ?? 1)) ?>> <span>Visível para contratação</span></label>
      <div class="field"><label for="po">Ordem de exibição</label><input id="po" type="number" name="sort_order" value="<?= (int) ($p['sort_order'] ?? 0) ?>"></div>
      <p class="hint mt">Alterar o preço vale para novas contratações; assinaturas existentes mantêm o valor contratado.</p>
      <button class="btn mt" type="submit">Salvar plano</button>
    </form>
  </div>
</div>
