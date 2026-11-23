<div class="page-head">
  <div><h1>Treinos</h1><p>Sua semana e os seus treinos ativos.</p></div>
</div>

<?php if (!$workouts): ?>
  <div class="hero-workout empty-state"><p class="eyebrow">Treinos</p><h2>Seu treino está sendo preparado pelo Renan.</h2><p class="muted">Assim que ele for publicado, aparece aqui.</p></div>
<?php else: ?>
  <div class="grid grid-2 enter">
    <?php foreach ($workouts as $w): $tone = mod_tone((int) $w['modality_id']); ?>
      <a class="hero-workout <?= e($tone) ?>" href="/app/treinos/<?= (int) $w['id'] ?>" style="text-decoration:none;view-transition-name:none">
        <div class="top"><span class="chip mod"><?= e($w['modality_name']) ?></span><?= icon('chevron-right') ?></div>
        <h2 style="font-size:clamp(1.9rem,8vw,2.6rem);margin:16px 0 8px"><?= e(Workouts::title($w)) ?></h2>
        <p class="meta"><?= e(weekday_names(Workouts::weekdays($w))) ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="section">
    <h2 style="margin-bottom:10px">Esta semana</h2>
    <div class="card flush"><div class="list day-list" style="padding:4px 18px">
      <?php foreach ($week as $date => $list): $isToday = $date === $today; ?>
        <div class="list-item" style="align-items:center">
          <div style="width:54px;flex:none;text-align:center">
            <div class="xs muted strong"><?= e(mb_strtoupper(PT_WEEKDAYS_SHORT[(int) (new DateTimeImmutable($date))->format('w')])) ?></div>
            <div class="num" style="font-size:1.5rem<?= $isToday ? ';color:var(--blue)' : '' ?>"><?= e((new DateTimeImmutable($date))->format('d')) ?></div>
          </div>
          <div class="grow stack-sm">
            <?php if (!$list): ?><span class="muted small">Descanso</span><?php endif; ?>
            <?php foreach ($list as $w): $st = $w['session']['status'] ?? null; ?>
              <a href="/app/treinos/<?= (int) $w['id'] ?>?data=<?= e($date) ?>" class="row nowrap <?= e(mod_tone((int) $w['modality_id'])) ?>" style="text-decoration:none">
                <span class="dot mod"></span>
                <div class="grow"><div class="list-title"><?= e(Workouts::title($w)) ?></div><div class="list-sub"><?= e($w['modality_name']) ?><?= $w['moved_from'] ? ' · remarcado de ' . e(fmt_date_short($w['moved_from'])) : '' ?></div></div>
                <span class="badge <?= $st === 'completed' ? 'ok' : ($st === 'skipped' ? 'danger' : ($st === 'postponed' ? 'warn' : ($st === 'in_progress' ? 'blue' : 'outline'))) ?>"><?= e(Schedule::sessionStatusLabel($st)) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div></div>
  </div>
<?php endif; ?>

<?php if ($requests): ?>
  <div class="section">
    <h2 style="margin-bottom:10px">Pedidos de troca de dia</h2>
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

<div class="section"><a class="link" href="/app/historico"><?= icon('clock', 'icon-sm') ?> Ver treinos anteriores</a></div>
