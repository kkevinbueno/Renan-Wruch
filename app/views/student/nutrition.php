<div class="page-head">
  <div><h1>Alimentação e hidratação</h1><p>Anote seus hábitos do dia. Sem contagem de calorias.</p></div>
  <span class="badge outline"><?= icon('droplet', 'icon-sm') ?> Hoje: <?= number_format($waterToday / 1000, 1, ',', '') ?> L</span>
</div>
<div class="grid grid-main">
  <div class="stack">
    <?php if (!$byDay): ?>
      <div class="card empty"><?= icon('food', 'icon-lg') ?><h3>Nenhum registro nos últimos 30 dias</h3><p>Use o formulário para anotar refeições ou água.</p></div>
    <?php endif; ?>
    <?php foreach ($byDay as $day => $list): $water = array_sum(array_map(fn($r) => (int) $r['water_ml'], $list)); ?>
      <div class="card">
        <div class="card-head"><h3><?= e(fmt_date($day, true)) ?></h3><?php if ($water): ?><span class="badge"><?= icon('droplet', 'icon-sm') ?> <?= number_format($water / 1000, 1, ',', '') ?> L</span><?php endif; ?></div>
        <div class="list">
          <?php foreach ($list as $r): ?>
            <div class="list-item">
              <div class="grow">
                <div class="list-title"><?= e($meals[$r['meal']] ?? 'Registro') ?><?= $r['water_ml'] ? ' · ' . (int) $r['water_ml'] . ' ml de água' : '' ?></div>
                <?php if ($r['description']): ?><div class="small pre"><?= e($r['description']) ?></div><?php endif; ?>
                <?php if ($r['notes']): ?><div class="list-sub pre"><?= e($r['notes']) ?></div><?php endif; ?>
              </div>
              <form method="post" action="/app/alimentacao/<?= (int) $r['id'] ?>/excluir" data-confirm="Remover este registro?"><?= csrf_field() ?><button class="btn ghost sm icon-only" type="submit" aria-label="Remover"><?= icon('trash', 'icon-sm') ?></button></form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <form class="card" method="post" action="/app/alimentacao" style="align-self:start">
    <?= csrf_field() ?>
    <h3>Novo registro</h3>
    <div class="form-grid cols-2 mt">
      <div class="field"><label for="nd">Data</label><input id="nd" type="date" name="date" value="<?= e(today()) ?>" max="<?= e(today()) ?>"></div>
      <div class="field"><label for="meal">Refeição</label><select id="meal" name="meal"><?php foreach ($meals as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="field"><label for="desc">O que você comeu?</label><textarea id="desc" name="description" rows="3" placeholder="Ex.: arroz, feijão, frango e salada"></textarea></div>
    <div class="field">
      <label for="water">Água (ml)</label>
      <input id="water" type="number" name="water_ml" min="0" max="10000" step="50" inputmode="numeric" placeholder="0">
      <div class="pills mt" data-water-quick><button type="button" class="btn secondary sm" data-add="250">+250 ml</button><button type="button" class="btn secondary sm" data-add="500">+500 ml</button><button type="button" class="btn secondary sm" data-add="1000">+1 L</button></div>
    </div>
    <div class="field"><label for="nn">Observações</label><input id="nn" name="notes" placeholder="Fome, saciedade, horário…"></div>
    <button class="btn mt" type="submit">Salvar</button>
  </form>
</div>
