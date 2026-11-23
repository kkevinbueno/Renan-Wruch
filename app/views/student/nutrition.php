<?php
$goal = Progress::WATER_GOAL_ML;
$pct = min(1, $waterToday / $goal);
$level = round(150 - $pct * 132, 1); // altura da água dentro do copo
$quickMeals = ['cafe' => ['🍳', 'Café da manhã', 'm-musc'], 'almoco' => ['🥗', 'Almoço', 'm-tenis'], 'lanche_tarde' => ['🍎', 'Lanche', 'm-cond'], 'jantar' => ['🍝', 'Jantar', 'm-beach']];
$doneMeals = [];
foreach ($mealsToday as $m) $doneMeals[$m['meal']][] = $m;
?>
<div class="page-head"><div><h1>Água e refeições</h1><p><?= e(fmt_date(today(), true)) ?> · sem contagem de calorias</p></div></div>

<div class="grid grid-main">
  <div class="stack">
    <div class="card water">
      <svg class="glass" viewBox="0 0 120 160" aria-hidden="true">
        <defs><clipPath id="cup"><path d="M14 8 H106 L94 150 Q93 156 86 156 H34 Q27 156 26 150 Z"/></clipPath></defs>
        <g clip-path="url(#cup)">
          <rect width="120" height="160" fill="var(--surface-2)"/>
          <g class="fill" style="transform:translateY(<?= $level ?>px)">
            <path class="wave" d="M0 0 Q15 -8 30 0 T60 0 T90 0 T120 0 T150 0 T180 0 V170 H0Z" fill="var(--powder)"/>
            <path class="wave" style="animation-duration:4.5s" d="M0 6 Q15 -2 30 6 T60 6 T90 6 T120 6 T150 6 T180 6 V170 H0Z" fill="var(--blue)" opacity=".45"/>
          </g>
        </g>
        <path d="M14 8 H106 L94 150 Q93 156 86 156 H34 Q27 156 26 150 Z" fill="none" stroke="var(--text)" stroke-width="3.5"/>
      </svg>
      <div class="grow">
        <div class="num" style="font-size:3rem;line-height:1" data-count="<?= round($waterToday / 1000, 1) ?>" data-decimals="1" data-suffix=" L"><?= number_format($waterToday / 1000, 1, ',', '') ?> L</div>
        <div class="small muted" style="margin-top:6px">de <?= number_format($goal / 1000, 1, ',', '') ?> L hoje</div>
        <div class="btn-row mt">
          <?php foreach ([250, 500] as $ml): ?>
            <form method="post" action="/app/alimentacao"><?= csrf_field() ?><input type="hidden" name="meal" value="agua"><input type="hidden" name="water_ml" value="<?= $ml ?>"><button class="btn <?= $ml === 250 ? 'pw' : 'secondary' ?> sm" type="submit">+<?= $ml ?> ml</button></form>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div>
      <div class="row between" style="margin:6px 2px 10px"><h2>Refeições de hoje</h2><span class="xs muted"><?= plural(count($mealsToday), 'registrada', 'registradas') ?></span></div>
      <div class="enter">
        <?php foreach ($quickMeals as $key => [$emo, $label, $tone]): $has = $doneMeals[$key] ?? []; ?>
          <details class="todo <?= e($tone) ?>" style="display:block">
            <summary class="row nowrap" style="gap:12px">
              <span class="ico mod"><?= $emo ?></span>
              <div class="grow"><div class="t"><?= e($label) ?></div><div class="xs muted truncate"><?= $has ? e(implode(' · ', array_filter(array_column($has, 'description')))) : 'Toque para registrar' ?></div></div>
              <?= $has ? '<span class="badge ok">' . icon('check', 'icon-sm') . '</span>' : '<span class="chip">' . icon('plus', 'icon-sm') . '</span>' ?>
            </summary>
            <form method="post" action="/app/alimentacao" class="mt">
              <?= csrf_field() ?><input type="hidden" name="meal" value="<?= e($key) ?>">
              <div class="field"><label for="d_<?= e($key) ?>">O que você comeu?</label><textarea id="d_<?= e($key) ?>" name="description" rows="2" placeholder="Ex.: arroz, feijão, frango e salada" required></textarea></div>
              <button class="btn sm mt" type="submit">Salvar</button>
            </form>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="stack">
    <details class="card more">
      <summary><?= icon('plus', 'icon-sm') ?> Outro registro (outra refeição, outro dia, observações)</summary>
      <form method="post" action="/app/alimentacao" class="mt">
        <?= csrf_field() ?>
        <div class="form-grid cols-2">
          <div class="field"><label for="nd">Data</label><input id="nd" type="date" name="date" value="<?= e(today()) ?>" max="<?= e(today()) ?>"></div>
          <div class="field"><label for="meal">Refeição</label><select id="meal" name="meal"><?php foreach ($meals as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="field"><label for="desc">O que você comeu?</label><textarea id="desc" name="description" rows="3"></textarea></div>
        <div class="field"><label for="water">Água (ml)</label><input id="water" type="number" name="water_ml" min="0" max="10000" step="50" inputmode="numeric" placeholder="0"></div>
        <div class="field"><label for="nn">Observações</label><input id="nn" name="notes" placeholder="Fome, saciedade, horário…"></div>
        <button class="btn mt" type="submit">Salvar</button>
      </form>
    </details>

    <details class="card more">
      <summary><?= icon('clock', 'icon-sm') ?> Histórico dos últimos 30 dias</summary>
      <?php if (!$byDay): ?><p class="muted small mt">Nenhum registro ainda.</p><?php endif; ?>
      <?php foreach ($byDay as $day => $list): $water = array_sum(array_map(fn($r) => (int) $r['water_ml'], $list)); ?>
        <div class="mt">
          <div class="row between"><b class="small"><?= e(fmt_date($day, true)) ?></b><?php if ($water): ?><span class="badge"><?= icon('droplet', 'icon-sm') ?> <?= number_format($water / 1000, 1, ',', '') ?> L</span><?php endif; ?></div>
          <div class="list">
            <?php foreach ($list as $r): ?>
              <div class="list-item">
                <div class="grow">
                  <div class="list-title small"><?= e($meals[$r['meal']] ?? 'Registro') ?><?= $r['water_ml'] ? ' · ' . (int) $r['water_ml'] . ' ml' : '' ?></div>
                  <?php if ($r['description']): ?><div class="small pre muted"><?= e($r['description']) ?></div><?php endif; ?>
                  <?php if ($r['notes']): ?><div class="list-sub pre"><?= e($r['notes']) ?></div><?php endif; ?>
                </div>
                <form method="post" action="/app/alimentacao/<?= (int) $r['id'] ?>/excluir" data-confirm="Remover este registro?"><?= csrf_field() ?><button class="btn ghost sm icon-only" type="submit" aria-label="Remover"><?= icon('trash', 'icon-sm') ?></button></form>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </details>
  </div>
</div>
