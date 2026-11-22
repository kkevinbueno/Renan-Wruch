<div class="page-head">
  <div>
    <p class="muted small"><a href="/painel/alunos/<?= (int) $student['id'] ?>?aba=treinos"><?= e($student['name']) ?></a> / Registro</p>
    <h1><?= e(Workouts::title($workout)) ?></h1>
    <p><?= e(fmt_date($session['scheduled_date'], true)) ?> · versão <?= (int) $version['version_no'] ?> · <?= e(Schedule::sessionStatusLabel($session['status'])) ?><?= $session['completed_at'] ? ' em ' . e(fmt_datetime($session['completed_at'])) : '' ?></p>
  </div>
</div>
<?php if ($session['notes']): ?><div class="callout mb"><?= icon('message') ?><p class="pre small"><b>Comentário do aluno:</b> <?= e($session['notes']) ?></p></div><?php endif; ?>
<?php foreach ($items as $i => $it): $en = $entries[(int) $it['id']] ?? null; $d = $en['data'] ?? []; ?>
  <article class="exercise<?= !empty($en['completed']) ? ' done' : '' ?>">
    <div class="exercise-head">
      <span class="exercise-num"><?= $i + 1 ?></span>
      <div class="grow">
        <h3><?= e($it['name']) ?></h3>
        <div class="params"><span class="muted">Prescrito:</span> <?= params_summary($it['params'], $it['fields']) ?: '—' ?></div>
      </div>
      <span class="badge <?= !empty($en['completed']) ? 'ok' : 'outline' ?>"><?= !empty($en['completed']) ? 'Concluído' : 'Não marcado' ?></span>
    </div>
    <?php if ($d): ?>
      <div class="exercise-body small">
        <?php if (!empty($d['sets'])): ?>
          <table class="table"><thead><tr><th>Série</th><?php foreach (['reps' => 'Reps', 'load' => 'Carga', 'time' => 'Tempo', 'distance' => 'Distância'] as $k => $l): if (!array_filter(array_column($d['sets'], $k))) continue; ?><th><?= $l ?></th><?php endforeach; ?><th>Feita</th></tr></thead>
          <tbody><?php foreach ($d['sets'] as $n => $set): ?><tr><td><?= $n + 1 ?></td><?php foreach (['reps', 'load', 'time', 'distance'] as $k): if (!array_filter(array_column($d['sets'], $k))) continue; ?><td><?= e($set[$k] ?? '—') ?></td><?php endforeach; ?><td><?= !empty($set['done']) ? '✓' : '—' ?></td></tr><?php endforeach; ?></tbody></table>
        <?php endif; ?>
        <div class="params"><?php foreach (['duration' => 'Duração', 'distance' => 'Distância', 'time' => 'Tempo', 'intensity' => 'Intensidade', 'pace' => 'Ritmo'] as $k => $l): if (empty($d[$k])) continue; ?><span><?= $l ?>: <b><?= e($d[$k]) ?></b></span><?php endforeach; ?></div>
        <?php if (!empty($d['notes'])): ?><p class="pre mt"><b>Observação:</b> <?= e($d['notes']) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
