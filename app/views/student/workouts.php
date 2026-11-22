<div class="page-head">
  <div><h1>Treinos</h1><p>Sua programação desta semana.</p></div>
  <a class="btn secondary sm" href="/app/historico"><?= icon('clock', 'icon-sm') ?> Histórico</a>
</div>

<?php if (!$workouts): ?>
  <div class="card empty"><?= icon('dumbbell', 'icon-lg') ?><h3>Seu treino está sendo preparado pelo Renan.</h3><p>Assim que ele for publicado, aparecerá aqui.</p></div>
<?php else: ?>
  <div class="card flush">
    <div class="list" style="padding:0 18px">
      <?php foreach ($week as $date => $list): ?>
        <?php $isToday = $date === $today; ?>
        <div class="list-item" style="align-items:flex-start">
          <div style="width:58px;flex:none">
            <div class="xs muted"><?= e(PT_WEEKDAYS_SHORT[(int) (new DateTimeImmutable($date))->format('w')]) ?></div>
            <div class="strong"<?= $isToday ? ' style="color:var(--accent)"' : '' ?>><?= e((new DateTimeImmutable($date))->format('d/m')) ?></div>
          </div>
          <div class="grow stack-sm">
            <?php if (!$list): ?><span class="muted small">Descanso</span><?php endif; ?>
            <?php foreach ($list as $w): $st = $w['session']['status'] ?? null; ?>
              <div class="row between">
                <a href="/app/treinos/<?= (int) $w['id'] ?>?data=<?= e($date) ?>" class="grow" style="text-decoration:none">
                  <div class="list-title"><?= e(Workouts::title($w)) ?></div>
                  <div class="list-sub"><?= e($w['modality_name']) ?><?= $w['moved_from'] ? ' · remarcado de ' . e(fmt_date_short($w['moved_from'])) : '' ?></div>
                </a>
                <span class="badge <?= $st === 'completed' ? 'ok' : ($st === 'skipped' ? 'danger' : ($st === 'postponed' ? 'warn' : ($st === 'in_progress' ? 'dark' : 'outline'))) ?>"><?= e(Schedule::sessionStatusLabel($st)) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="section">
    <p class="section-title">Meus treinos</p>
    <div class="grid grid-2">
      <?php foreach ($workouts as $w): ?>
        <a class="card" href="/app/treinos/<?= (int) $w['id'] ?>" style="text-decoration:none">
          <div class="row between nowrap"><h3 class="truncate"><?= e(Workouts::title($w)) ?></h3><?= icon('chevron-right') ?></div>
          <p class="small muted mt"><?= e($w['modality_name']) ?> · <?= e(weekday_names(Workouts::weekdays($w))) ?></p>
          <p class="xs muted">Atualizado em <?= e(fmt_date(to_local($w['published']['published_at'])->format('Y-m-d'))) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($requests): ?>
  <div class="section">
    <p class="section-title">Pedidos de mudança de dia</p>
    <div class="card"><div class="list">
      <?php foreach ($requests as $r): ?>
        <div class="list-item">
          <div class="grow"><div class="list-title"><?= e(Workouts::title($r)) ?></div><div class="list-sub"><?= e(fmt_date_short($r['original_date'])) ?> → <?= e(fmt_date_short($r['requested_date'])) ?><?= $r['decision_note'] ? ' · ' . e($r['decision_note']) : '' ?></div></div>
          <span class="badge <?= $r['status'] === 'approved' ? 'ok' : ($r['status'] === 'rejected' ? 'danger' : 'warn') ?>"><?= ['pending' => 'Aguardando', 'approved' => 'Aprovado', 'rejected' => 'Recusado'][$r['status']] ?></span>
        </div>
      <?php endforeach; ?>
    </div></div>
  </div>
<?php endif; ?>
