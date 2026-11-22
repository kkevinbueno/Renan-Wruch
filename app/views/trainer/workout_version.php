<div class="page-head">
  <div>
    <p class="muted small"><a href="/painel/treinos/<?= (int) $w['id'] ?>"><?= e(Workouts::title($w)) ?></a> / Versão <?= (int) $v['version_no'] ?></p>
    <h1>Versão <?= (int) $v['version_no'] ?></h1>
    <p><?= $v['published_at'] ? 'Publicada em ' . e(fmt_datetime($v['published_at'])) : 'Não publicada' ?> · usada em <?= plural($usage, 'registro', 'registros') ?> de treino</p>
  </div>
</div>
<?php if ($v['general_notes']): ?><div class="callout mb"><?= icon('clipboard') ?><p class="pre small"><?= e($v['general_notes']) ?></p></div><?php endif; ?>
<?= View::partial('workout_items', ['items' => $items]) ?>
