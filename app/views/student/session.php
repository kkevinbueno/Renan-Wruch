<?php
$perSetCatalog = ['reps', 'load', 'time', 'distance'];
$singleCatalog = ['duration', 'intensity', 'pace'];
$doneCount = count(array_filter($entries, fn($e) => $e['completed']));
$total = count($items);
$tone = mod_tone($modality);
?>
<div class="<?= e($tone) ?>">
<div class="row between">
  <a class="btn ghost sm" href="/app/treinos"><?= icon('chevron-left', 'icon-sm') ?> Treinos</a>
  <span class="save-state" data-save-state><?= $session['status'] === 'completed' ? 'Treino concluído' : 'Salva sozinho' ?></span>
</div>
<div class="seg-progress" style="margin:14px 0 18px" aria-hidden="true"><?php foreach ($items as $it): ?><i class="<?= !empty($entries[(int) $it['id']]['completed']) ? 'done' : '' ?>"></i><?php endforeach; ?></div>
<div class="row nowrap between" style="align-items:flex-end">
  <div class="grow">
    <p class="kicker" data-focus-count><?= e($modality['name']) ?></p>
    <h1 style="margin-top:6px"><?= e(Workouts::title($workout)) ?></h1>
  </div>
  <?= ring($total ? $doneCount / $total : 0, 52, 6, '<span data-done-count>' . $doneCount . '</span>/' . $total) ?>
</div>
<div class="progress" data-total="<?= $total ?>" hidden><span style="width:<?= $total ? round($doneCount / $total * 100) : 0 ?>%"></span></div>

<?php if ($version['general_notes']): ?>
  <div class="callout accent mt"><?= icon('clipboard') ?><p class="pre small"><?= e($version['general_notes']) ?></p></div>
<?php endif; ?>

<div data-session data-save-url="/app/sessao/<?= (int) $session['id'] ?>/registro" class="mt">
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
    $hasMedia = $embed || $it['video_file_id'];
?>
  <article class="exercise<?= !empty($entry['completed']) ? ' done' : '' ?>" data-entry data-item-id="<?= (int) $it['id'] ?>" data-rest="<?= e($it['params']['rest'] ?? '') ?>">
    <div class="exercise-head">
      <span class="exercise-num"><?= $i + 1 ?></span>
      <div class="grow">
        <h2 style="font-family:var(--font-display);font-weight:400;text-transform:uppercase;font-size:clamp(1.6rem,7vw,2.2rem);line-height:1"><?= e($it['name']) ?></h2>
        <div class="params"><?= params_summary($it['params'], $fields) ?></div>
      </div>
      <button type="button" class="btn sm <?= !empty($entry['completed']) ? 'accent' : 'secondary' ?>" data-complete aria-pressed="<?= !empty($entry['completed']) ? 'true' : 'false' ?>"><?= icon('check', 'icon-sm') ?><span><?= !empty($entry['completed']) ? 'Feito' : 'Concluir' ?></span></button>
    </div>
    <div class="exercise-body">
      <?php if ($it['instructions'] || $it['notes'] || $hasMedia): ?>
        <details class="more mb"><summary><?= icon($hasMedia ? 'play' : 'clipboard', 'icon-sm') ?> Ver como fazer</summary>
          <div class="stack-sm mt">
            <?php if ($it['video_file_id']): ?><div class="video-embed"><video controls preload="none" playsinline src="<?= e(Files::url((int) $it['video_file_id'])) ?>"></video></div>
            <?php elseif ($embed): ?><div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo" allowfullscreen loading="lazy"></iframe></div><?php endif; ?>
            <?php if ($it['instructions']): ?><p class="small pre"><?= e($it['instructions']) ?></p><?php endif; ?>
            <?php if ($it['notes']): ?><p class="small muted pre"><b>Observação:</b> <?= e($it['notes']) ?></p><?php endif; ?>
          </div>
        </details>
      <?php endif; ?>

      <?php if ($hasSets): ?>
        <div class="sets" data-sets data-fields="<?= e(implode(',', $perSet)) ?>" style="--cols: <?= max(1, count($perSet)) ?>">
          <div class="set-row set-head"><span></span><?php foreach ($perSet as $f): ?><span><?= e(Modalities::FIELDS[$f]['label']) ?><?= Modalities::FIELDS[$f]['unit'] ? ' (' . e(Modalities::FIELDS[$f]['unit']) . ')' : '' ?></span><?php endforeach; ?><?php if (!$perSet): ?><span></span><?php endif; ?><span></span></div>
          <?php for ($r = 0; $r < $rows; $r++): $set = $d['sets'][$r] ?? []; ?>
            <div class="set-row" data-set>
              <span class="n"><?= $r + 1 ?></span>
              <?php foreach ($perSet as $f): ?>
                <input type="text" inputmode="<?= in_array($f, ['reps', 'load', 'distance'], true) ? 'decimal' : 'text' ?>" data-k="<?= e($f) ?>" value="<?= e($set[$f] ?? ($it['params'][$f] ?? '')) ?>" placeholder="<?= e($it['params'][$f] ?? '') ?>" aria-label="<?= e(Modalities::FIELDS[$f]['label']) ?> série <?= $r + 1 ?>">
              <?php endforeach; ?>
              <?php if (!$perSet): ?><span class="small muted">Série <?= $r + 1 ?></span><?php endif; ?>
              <button type="button" class="set-done" data-set-done aria-pressed="<?= !empty($set['done']) ? 'true' : 'false' ?>" aria-label="Série <?= $r + 1 ?> feita"><?= icon('check') ?></button>
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
      <details class="more mt"<?= !empty($d['notes']) ? ' open' : '' ?>><summary><?= icon('edit', 'icon-sm') ?> Anotar algo</summary>
        <div class="field mt"><label class="sr-only">Observações</label><textarea rows="2" data-k="notes" placeholder="Como foi este exercício?"><?= e($d['notes'] ?? '') ?></textarea></div>
      </details>
    </div>
  </article>
<?php endforeach; ?>
  <div class="focus-nav">
    <button type="button" class="btn secondary" data-prev-ex><?= icon('chevron-left', 'icon-sm') ?> Anterior</button>
    <button type="button" class="btn" data-next-ex><span>Próximo</span> <?= icon('chevron-right', 'icon-sm') ?></button>
  </div>
</div>

<form class="card section" method="post" action="/app/sessao/<?= (int) $session['id'] ?>/concluir" data-finish-form>
  <?= csrf_field() ?>
  <h2>Finalizar treino</h2>
  <div class="field mt"><label for="s_notes">Como foi o treino? (opcional)</label><textarea id="s_notes" name="notes" rows="2" placeholder="Cansaço, dores, conquistas…"><?= e($session['notes']) ?></textarea></div>
  <button class="btn pw lg block mt" type="submit" data-finish><?= icon('check') ?> <?= $session['status'] === 'completed' ? 'Salvar e voltar' : 'Concluir treino' ?></button>
</form>
</div>
