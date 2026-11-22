<div class="page-head"><div><h1>Histórico de treinos</h1><p>Registros preservados mesmo quando o treino é atualizado.</p></div></div>
<?php if (!$rows): ?>
  <div class="card empty"><?= icon('clock', 'icon-lg') ?><h3>Nenhum treino registrado ainda</h3><p>Quando você iniciar ou concluir um treino, ele aparecerá aqui.</p></div>
<?php else: ?>
  <div class="card"><div class="list">
    <?php foreach ($rows as $r): ?>
      <a class="list-item" href="/app/sessao/<?= (int) $r['id'] ?>">
        <div style="width:64px" class="small"><b><?= e((new DateTimeImmutable($r['scheduled_date']))->format('d/m')) ?></b><div class="xs muted"><?= e(PT_WEEKDAYS_SHORT[(int) (new DateTimeImmutable($r['scheduled_date']))->format('w')]) ?></div></div>
        <div class="grow"><div class="list-title"><?= e(Workouts::title($r)) ?></div><div class="list-sub"><?= e($r['modality_name']) ?> · versão <?= (int) $r['version_no'] ?> · <?= (int) $r['done'] ?>/<?= (int) $r['total_items'] ?> exercícios</div></div>
        <span class="badge <?= $r['status'] === 'completed' ? 'ok' : ($r['status'] === 'skipped' ? 'danger' : ($r['status'] === 'postponed' ? 'warn' : 'dark')) ?>"><?= e(Schedule::sessionStatusLabel($r['status'])) ?></span>
      </a>
    <?php endforeach; ?>
  </div></div>
  <?= View::partial('pagination', ['page' => $page, 'pages' => $pages, 'base' => '/app/historico', 'query' => []]) ?>
<?php endif; ?>
