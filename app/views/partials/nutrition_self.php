<?php /** @var array $selfs @var string $label */ ?>
<div class="card">
  <h3><?= e($label) ?></h3>
  <?php if (!$selfs): ?>
    <p class="small muted mt">Nenhuma nota no período.</p>
  <?php else: ?>
    <div class="list mt">
      <?php foreach (array_slice($selfs, 0, 10) as $sf): ?>
        <div class="list-item small"><span class="grow"><?= e(fmt_date($sf['date'], true)) ?></span><span class="score-face"><?= SelfEval::face($sf['score'], 'xs') ?> <?= e(SelfEval::SCALE[$sf['score']]['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
