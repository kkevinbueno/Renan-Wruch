<?php
$perSetCatalog = ['reps', 'load', 'time', 'distance'];
$singleCatalog = ['duration', 'intensity', 'pace'];
$doneCount = count(array_filter($entries, fn($e) => $e['completed']));
$total = count($items);
?>
<div class="page-head">
  <div>
    <p class="muted small"><a href="/app/treinos">Treinos</a> / <?= e(fmt_date($session['scheduled_date'], true)) ?></p>
    <h1><?= e(Workouts::title($workout)) ?></h1>
    <p><?= e($modality['name']) ?> · <?= e(Schedule::sessionStatusLabel($session['status'])) ?></p>
  </div>
  <span class="save-state" data-save-state>Salvamento automático</span>
</div>

<div class="card mb">
  <div class="row between small"><span><b data-done-count><?= $doneCount ?></b> de <?= $total ?> exercícios concluídos</span><span class="muted">Versão <?= (int) $version['version_no'] ?></span></div>
  <div class="progress mt" data-total="<?= $total ?>"><span style="width:<?= $total ? round($doneCount / $total * 100) : 0 ?>%"></span></div>
</div>

<?php if ($version['general_notes']): ?>
  <div class="callout mb"><?= icon('clipboard') ?><p class="pre small"><?= e($version['general_notes']) ?></p></div>
<?php endif; ?>

<div data-session data-save-url="/app/sessao/<?= (int) $session['id'] ?>/registro">
<?php foreach ($items as $i => $it):
    $entry = $entries[(int) $it['id']] ?? null;
    $d = $entry['data'] ?? [];
    $fields = $it['fields'];
    $hasSets = in_array('sets', $fields, true);
    $perSet = $hasSets ? array_values(array_intersect($perSetCatalog, $fields)) : [];
    $single = array_values(array_intersect($hasSets ? $singleCatalog : array_merge($singleCatalog, $perSetCatalog), $fields));
    $prescribedSets = max(1, (int) ($it['params']['sets'] ?? 1));
    $rows = max($prescribedSets, count($d['sets'] ?? []));
    $embed = video_embed_url($it['video_url']);
?>
  <article class="exercise<?= !empty($entry['completed']) ? ' done' : '' ?>" data-entry data-item-id="<?= (int) $it['id'] ?>">
    <div class="exercise-head">
      <span class="exercise-num"><?= $i + 1 ?></span>
      <div class="grow">
        <h3><?= e($it['name']) ?></h3>
        <div class="params"><?= params_summary($it['params'], $fields) ?></div>
      </div>
      <button type="button" class="btn sm <?= !empty($entry['completed']) ? 'accent' : 'secondary' ?>" data-complete aria-pressed="<?= !empty($entry['completed']) ? 'true' : 'false' ?>"><?= icon('check', 'icon-sm') ?><span><?= !empty($entry['completed']) ? 'Feito' : 'Concluir' ?></span></button>
    </div>
    <div class="exercise-body">
      <?php if ($it['instructions'] || $it['notes'] || $embed || $it['video_file_id']): ?>
        <details class="mb"><summary class="small muted"><?= icon('chevron-down', 'icon-sm') ?> Instruções<?= ($embed || $it['video_file_id']) ? ' e vídeo' : '' ?></summary>
          <div class="stack-sm mt">
            <?php if ($it['instructions']): ?><p class="small pre"><?= e($it['instructions']) ?></p><?php endif; ?>
            <?php if ($it['notes']): ?><p class="small muted pre"><b>Observação:</b> <?= e($it['notes']) ?></p><?php endif; ?>
            <?php if ($it['video_file_id']): ?><div class="video-embed"><video controls preload="none" playsinline src="<?= e(Files::url((int) $it['video_file_id'])) ?>"></video></div>
            <?php elseif ($embed): ?><div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo" allowfullscreen loading="lazy"></iframe></div><?php endif; ?>
          </div>
        </details>
      <?php endif; ?>

      <?php if ($hasSets): ?>
        <div class="sets" data-sets data-fields="<?= e(implode(',', $perSet)) ?>" style="--cols: <?= max(1, count($perSet)) ?>">
          <div class="set-row set-head"><span>Série</span><?php foreach ($perSet as $f): ?><span><?= e(Modalities::FIELDS[$f]['label']) ?><?= Modalities::FIELDS[$f]['unit'] ? ' (' . e(Modalities::FIELDS[$f]['unit']) . ')' : '' ?></span><?php endforeach; ?><?php if (!$perSet): ?><span></span><?php endif; ?><span></span></div>
          <?php for ($r = 0; $r < $rows; $r++): $set = $d['sets'][$r] ?? []; ?>
            <div class="set-row" data-set>
              <span class="n"><?= $r + 1 ?></span>
              <?php foreach ($perSet as $f): ?>
                <input type="text" inputmode="<?= in_array($f, ['reps', 'load', 'distance'], true) ? 'decimal' : 'text' ?>" data-k="<?= e($f) ?>" value="<?= e($set[$f] ?? '') ?>" placeholder="<?= e($it['params'][$f] ?? '') ?>" aria-label="<?= e(Modalities::FIELDS[$f]['label']) ?> série <?= $r + 1 ?>">
              <?php endforeach; ?>
              <?php if (!$perSet): ?><span class="small muted">Série <?= $r + 1 ?></span><?php endif; ?>
              <button type="button" class="set-done" data-set-done aria-pressed="<?= !empty($set['done']) ? 'true' : 'false' ?>" aria-label="Série <?= $r + 1 ?> feita"><?= icon('check', 'icon-sm') ?></button>
            </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="btn ghost sm mt" data-add-set><?= icon('plus', 'icon-sm') ?> Série</button>
      <?php endif; ?>

      <?php if ($single): ?>
        <div class="form-grid cols-3 mt">
          <?php foreach ($single as $f): ?>
            <div class="field"><label><?= e(Modalities::FIELDS[$f]['label']) ?><?= Modalities::FIELDS[$f]['unit'] ? ' (' . e(Modalities::FIELDS[$f]['unit']) . ')' : '' ?></label><input type="text" data-k="<?= e($f) ?>" value="<?= e($d[$f] ?? '') ?>" placeholder="<?= e($it['params'][$f] ?? '') ?>"></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="field mt"><label>Observações</label><textarea rows="2" data-k="notes" placeholder="Como foi este exercício?"><?= e($d['notes'] ?? '') ?></textarea></div>
    </div>
  </article>
<?php endforeach; ?>
</div>

<form class="card section" method="post" action="/app/sessao/<?= (int) $session['id'] ?>/concluir">
  <?= csrf_field() ?>
  <h3>Finalizar</h3>
  <div class="field"><label for="s_notes">Como foi o treino? (opcional)</label><textarea id="s_notes" name="notes" rows="3"><?= e($session['notes']) ?></textarea></div>
  <button class="btn accent mt" type="submit" data-finish><?= icon('check') ?> <?= $session['status'] === 'completed' ? 'Salvar e voltar' : 'Concluir treino' ?></button>
</form>
