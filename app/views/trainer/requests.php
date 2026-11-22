<div class="page-head"><div><h1>Pedidos de mudança de dia</h1><p>A programação oficial só muda depois da sua aprovação.</p></div></div>
<?php if (!$pending): ?><div class="card empty"><?= icon('repeat', 'icon-lg') ?><h3>Nenhum pedido pendente</h3></div><?php endif; ?>
<div class="stack">
  <?php foreach ($pending as $r): ?>
    <div class="card">
      <div class="row between">
        <div><h3><?= e($r['student_name']) ?></h3><p class="small muted"><?= e(Workouts::title($r)) ?> · pedido <?= e(fmt_ago($r['created_at'])) ?></p></div>
        <div class="strong"><?= e(fmt_date($r['original_date'], true)) ?> → <?= e(fmt_date($r['requested_date'], true)) ?></div>
      </div>
      <?php if ($r['note']): ?><p class="small mt pre">“<?= e($r['note']) ?>”</p><?php endif; ?>
      <form method="post" action="/painel/solicitacoes/<?= (int) $r['id'] ?>" class="row mt">
        <?= csrf_field() ?>
        <input class="grow" name="decision_note" placeholder="Mensagem para o aluno (opcional)" aria-label="Mensagem para o aluno">
        <button class="btn danger sm" name="decision" value="reject" type="submit">Recusar</button>
        <button class="btn accent sm" name="decision" value="approve" type="submit"><?= icon('check', 'icon-sm') ?> Aprovar</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($decided): ?>
  <div class="section"><p class="section-title">Respondidos recentemente</p>
    <div class="card"><div class="list"><?php foreach ($decided as $r): ?>
      <div class="list-item small"><div class="grow"><b><?= e($r['student_name']) ?></b> · <?= e(Workouts::title($r)) ?> · <?= e(fmt_date_short($r['original_date'])) ?> → <?= e(fmt_date_short($r['requested_date'])) ?></div><span class="badge <?= $r['status'] === 'approved' ? 'ok' : 'danger' ?>"><?= $r['status'] === 'approved' ? 'Aprovado' : 'Recusado' ?></span></div>
    <?php endforeach; ?></div></div>
  </div>
<?php endif; ?>
