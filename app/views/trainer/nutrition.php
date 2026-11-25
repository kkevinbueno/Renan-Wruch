<?php
/** @var array $students @var ?int $sid @var ?array $student @var int $period @var string $view */
$q = fn(array $extra = []) => '/painel/alimentacao?' . http_build_query(array_merge(['aluno' => $sid, 'periodo' => $period, 'ver' => $view], $extra));
$L = fn(int $ml) => number_format($ml / 1000, 1, ',', '') . ' L';
$meals = NutritionController::MEALS;
?>
<div class="page-head"><div><h1>Alimentação</h1><p>Refeições e hidratação registradas pelos alunos. Acompanhamento de hábitos, sem prescrição nutricional.</p></div></div>

<?php if (!$students): ?>
  <div class="card empty"><?= icon('utensils', 'icon-lg') ?><h3>Nenhum aluno ativo</h3><p>Quando houver alunos ativos, os registros de refeições e água aparecem aqui.</p></div>
<?php return; endif; ?>

<form class="nut-filters" method="get" action="/painel/alimentacao" data-filter-form>
  <input type="hidden" name="ver" value="<?= e($view) ?>">
  <div class="field"><label for="na">Aluno</label><select id="na" name="aluno" data-autosubmit><?php foreach ($students as $st): ?><option value="<?= (int) $st['id'] ?>"<?= selected($st['id'], $sid) ?>><?= e($st['name']) ?><?= $st['last_log'] ? '' : ' (sem registros)' ?></option><?php endforeach; ?></select></div>
  <div class="field"><span class="label" id="np">Período</span><div class="seg" role="group" aria-labelledby="np"><?php foreach (TrainerNutritionController::PERIODS as $k => $l): ?><a href="<?= e($q(['periodo' => $k])) ?>" class="<?= $period === $k ? 'active' : '' ?>"<?= $period === $k ? ' aria-current="true"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></div></div>
  <noscript><button class="btn sm" type="submit">Ver</button></noscript>
</form>

<nav class="tabs" aria-label="Seções">
  <a href="<?= e($q(['ver' => 'refeicoes'])) ?>" class="<?= $view === 'refeicoes' ? 'active' : '' ?>"><?= icon('utensils', 'icon-sm') ?> Refeições</a>
  <a href="<?= e($q(['ver' => 'hidratacao'])) ?>" class="<?= $view === 'hidratacao' ? 'active' : '' ?>"><?= icon('droplet', 'icon-sm') ?> Hidratação</a>
</nav>

<div class="nut-region" data-filter-region>
<p class="small muted mb"><?= e($student['name']) ?> · <?= e(fmt_date_short($start)) ?> a <?= e(fmt_date_short($end)) ?></p>

<?php if ($view === 'refeicoes'): ?>
  <div class="stat-row">
    <div class="stat-mini"><span class="num"><?= (int) $daysWithMeals ?><small>/<?= (int) $period ?></small></span><span class="lbl">dias com refeições registradas</span></div>
    <div class="stat-mini"><span class="num"><?= e(str_replace('.', ',', (string) $avgMeals)) ?></span><span class="lbl">refeições por dia registrado</span></div>
    <div class="stat-mini"><span class="num"><?= (int) $mealCount ?></span><span class="lbl">registros no período</span></div>
  </div>

  <div class="grid grid-main section">
    <div class="stack">
      <div class="card">
        <h3>Histórico</h3>
        <?php if (!$days): ?>
          <div class="empty small"><p><?= e(first_name($student['name'])) ?> não registrou refeições neste período.</p></div>
        <?php endif; ?>
        <?php $di = 0; foreach ($days as $day => $groups): if ($di++ === 7): ?><details class="more"><summary><?= icon('chevron-down', 'icon-sm') ?> Ver dias anteriores (<?= count($days) - 7 ?>)</summary><?php endif; ?>
          <div class="nut-day">
            <p class="nut-day-title"><?= e(fmt_date($day, true)) ?></p>
            <?php foreach (TrainerNutritionController::GROUPS as $gk => $gl): if (empty($groups[$gk])) continue; ?>
              <?php foreach ($groups[$gk] as $r): ?>
                <div class="nut-meal">
                  <span class="nut-time"><?= e(fmt_time($r['created_at'])) ?></span>
                  <div class="grow">
                    <div class="strong small"><?= e($gk === 'outras' ? ($meals[$r['meal']] ?? 'Outra refeição') : $gl) ?></div>
                    <?php if ($r['description']): ?><div class="small pre"><?= e($r['description']) ?></div><?php endif; ?>
                    <?php if ($r['notes']): ?><div class="xs muted pre"><?= e($r['notes']) ?></div><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <?php if (count($days) > 7): ?></details><?php endif; ?>
        <p class="hint mt">Horário = momento em que o aluno fez o registro.</p>
      </div>
    </div>
    <div class="stack">
      <div class="card">
        <h3>Regularidade por refeição</h3>
        <p class="xs muted mt">Dias com registro de cada refeição no período.</p>
        <div class="reg-list mt">
          <?php foreach (TrainerNutritionController::GROUPS as $gk => $gl): $n = (int) $coverage[$gk]; ?>
            <div class="reg-row"><span class="small"><?= e($gl) ?></span><span class="reg-bar" role="img" aria-label="<?= e($gl) ?>: <?= $n ?> de <?= (int) $period ?> dias"><i style="width:<?= round(min(1, $n / max(1, $period)) * 100, 1) ?>%"></i></span><span class="small strong reg-n"><?= $n ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <?= View::partial('nutrition_self', ['selfs' => $selfs, 'label' => 'Autoavaliação · alimentação']) ?>
      <?= View::partial('nutrition_notes', ['notes' => $notes, 'sid' => $sid, 'topic' => 'refeicoes', 'period' => $period]) ?>
    </div>
  </div>

<?php else: ?>
  <?php
    $max = max([$goal, ...array_values($daily)]);
    $n = count($daily);
    $W = 640; $H = 180; $pad = 26; $gap = 2;
    $bw = max(2, ($W - $gap * ($n - 1)) / $n);
    $gy = $H - $pad - ($goal / $max) * ($H - $pad - 8);
  ?>
  <div class="stat-row">
    <div class="stat-mini"><span class="num"><?= (int) $daysLogged ?><small>/<?= (int) $period ?></small></span><span class="lbl">dias com água registrada</span></div>
    <div class="stat-mini"><span class="num"><?= e($L($avgLogged)) ?></span><span class="lbl">média nos dias registrados</span></div>
    <div class="stat-mini"><span class="num"><?= (int) $daysGoal ?></span><span class="lbl">dias com <?= e($L($goal)) ?> ou mais</span></div>
  </div>

  <div class="grid grid-main section">
    <div class="stack">
      <div class="card">
        <div class="row between"><h3>Água por dia</h3><span class="xs muted">linha tracejada: referência de <?= e($L($goal)) ?></span></div>
        <?php if (!$daysLogged): ?>
          <div class="empty small"><p><?= e(first_name($student['name'])) ?> não registrou água neste período.</p></div>
        <?php else: ?>
          <figure class="water-chart" data-chart>
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Água por dia nos últimos <?= (int) $period ?> dias. Média de <?= e($L($avgLogged)) ?> nos dias registrados.">
              <line class="grid-line" x1="0" x2="<?= $W ?>" y1="<?= $H - $pad ?>" y2="<?= $H - $pad ?>"/>
              <line class="goal-line" x1="0" x2="<?= $W ?>" y1="<?= round($gy, 1) ?>" y2="<?= round($gy, 1) ?>"/>
              <?php $i = 0; foreach ($daily as $d => $ml): $h = $ml ? max(3, ($ml / $max) * ($H - $pad - 8)) : 0; $x = $i * ($bw + $gap); ?>
                <g class="bar-hit" data-tip="<?= e(fmt_date_short($d)) ?> · <?= e($ml ? $L($ml) : 'sem registro') ?>">
                  <rect class="hit" x="<?= round($x, 1) ?>" y="0" width="<?= round($bw + $gap, 1) ?>" height="<?= $H - $pad ?>"/>
                  <?php if ($h): ?><path class="bar<?= $ml >= $goal ? ' met' : '' ?>" d="M<?= round($x, 1) ?> <?= $H - $pad ?>V<?= round($H - $pad - $h + 3, 1) ?>q0 -3 3 -3h<?= round(max(0, $bw - 6), 1) ?>q3 0 3 3V<?= $H - $pad ?>z"/><?php endif; ?>
                  <title><?= e(fmt_date_short($d)) ?>: <?= e($ml ? $L($ml) : 'sem registro') ?></title>
                </g>
              <?php $i++; endforeach; ?>
              <text class="axis" x="0" y="<?= $H - 6 ?>"><?= e(fmt_date_short(array_key_first($daily))) ?></text>
              <text class="axis" x="<?= $W ?>" y="<?= $H - 6 ?>" text-anchor="end"><?= e(fmt_date_short(array_key_last($daily))) ?></text>
            </svg>
            <div class="chart-tip" data-chart-tip hidden></div>
          </figure>
        <?php endif; ?>
      </div>
      <div class="card">
        <h3>Resumo por semana e por mês</h3>
        <div class="table-wrap mt"><table class="table stack-mobile">
          <thead><tr><th>Semana (início)</th><th>Total</th><th>Média/dia registrado</th><th>Dias registrados</th></tr></thead>
          <tbody><?php foreach ($weekly as $wk => $w): ?><tr><td data-label="Semana"><?= e(fmt_date_short($wk)) ?></td><td data-label="Total"><?= e($L($w['total'])) ?></td><td data-label="Média"><?= $w['logged'] ? e($L((int) round($w['total'] / $w['logged']))) : '—' ?></td><td data-label="Dias registrados"><?= (int) $w['logged'] ?>/<?= (int) $w['days'] ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <div class="table-wrap mt"><table class="table stack-mobile">
          <thead><tr><th>Mês</th><th>Total</th><th>Média/dia registrado</th><th>Dias registrados</th></tr></thead>
          <tbody><?php foreach ($monthly as $mo => $m): ?><tr><td data-label="Mês"><?= e(ucfirst(PT_MONTHS[(int) substr($mo, 5, 2) - 1] ?? $mo)) ?>/<?= e(substr($mo, 0, 4)) ?></td><td data-label="Total"><?= e($L($m['total'])) ?></td><td data-label="Média"><?= $m['logged'] ? e($L((int) round($m['total'] / $m['logged']))) : '—' ?></td><td data-label="Dias registrados"><?= (int) $m['logged'] ?>/<?= (int) $m['days'] ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <p class="hint mt">Os meses podem estar incompletos quando o período começa ou termina no meio do mês.</p>
      </div>
    </div>
    <div class="stack">
      <div class="card">
        <h3>Registros de água por dia</h3>
        <?php
          $perDay = [];
          foreach ($entries as $r) { $perDay[$r['ref_date']][] = $r; }
        ?>
        <?php if (!$perDay): ?><p class="small muted mt">Nenhum registro.</p><?php endif; ?>
        <div class="list mt">
          <?php $i = 0; foreach ($perDay as $d => $list): if ($i++ === 10): ?></div><details class="more mt"><summary><?= icon('chevron-down', 'icon-sm') ?> Ver dias anteriores</summary><div class="list mt"><?php endif; ?>
            <div class="list-item small"><span class="grow"><?= e(fmt_date($d, true)) ?></span><span class="strong"><?= e($L(array_sum(array_map(fn($r) => (int) $r['water_ml'], $list)))) ?></span><span class="xs muted"><?= plural(count($list), 'registro', 'registros') ?></span></div>
          <?php endforeach; ?>
        </div><?php if ($i > 10): ?></details><?php endif; ?>
      </div>
      <?= View::partial('nutrition_self', ['selfs' => $selfs, 'label' => 'Autoavaliação · hidratação']) ?>
      <?= View::partial('nutrition_notes', ['notes' => $notes, 'sid' => $sid, 'topic' => 'hidratacao', 'period' => $period]) ?>
    </div>
  </div>
<?php endif; ?>
</div>
