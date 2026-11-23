<a class="btn ghost sm" href="/app/eu"><?= icon('chevron-left', 'icon-sm') ?> Eu</a>
<div class="page-head mt">
  <div><h1>Avaliações físicas</h1><p>Próxima avaliação: <b><?= $profile['next_assessment_on'] ? e(fmt_date($profile['next_assessment_on'], true)) : 'a definir pelo Renan' ?></b> · intervalo de <?= plural((int) $profile['assessment_every_days'], 'dia', 'dias') ?></p></div>
  <a class="btn" href="/app/avaliacoes/nova"><?= icon('camera') ?> <?= $draft ? 'Continuar envio' : 'Nova avaliação' ?></a>
</div>
<?php if (!$rows): ?>
  <div class="card empty"><?= icon('camera', 'icon-lg') ?><h3>Nenhuma avaliação enviada</h3><p>Cada avaliação tem fotos de frente, lado e costas, além de medidas e observações opcionais.</p></div>
<?php else: ?>
  <div class="card"><div class="list">
    <?php foreach ($rows as $r): ?>
      <a class="list-item" href="/app/avaliacoes/<?= (int) $r['id'] ?>">
        <?= icon('camera') ?>
        <div class="grow"><div class="list-title">Enviada em <?= e(fmt_datetime($r['submitted_at'])) ?></div><div class="list-sub"><?= $r['due_on'] ? 'Prevista para ' . e(fmt_date_short($r['due_on'])) : 'Avaliação extra' ?></div></div>
        <span class="badge <?= $r['status'] === 'reviewed' ? 'ok' : 'warn' ?>"><?= $r['status'] === 'reviewed' ? 'Revisada' : 'Aguardando revisão' ?></span>
      </a>
    <?php endforeach; ?>
  </div></div>
<?php endif; ?>
