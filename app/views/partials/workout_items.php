<?php /** @var array $items  Lista somente leitura de exercícios prescritos. */ ?>
<?php if (!$items): ?>
  <div class="empty"><p>Nenhum exercício nesta versão.</p></div>
<?php endif; ?>
<?php foreach ($items as $i => $it): ?>
  <article class="exercise">
    <div class="exercise-head">
      <span class="exercise-num"><?= $i + 1 ?></span>
      <div class="grow">
        <h3><?= e($it['name']) ?></h3>
        <div class="params"><?= params_summary($it['params'], $it['fields']) ?></div>
      </div>
    </div>
    <?php
      $embed = video_embed_url($it['video_url']);
      $hasBody = $it['instructions'] || $it['notes'] || $embed || $it['video_url'] || $it['video_file_id'];
    ?>
    <?php if ($hasBody): ?>
      <div class="exercise-body stack-sm">
        <?php if ($it['instructions']): ?><p class="small pre"><?= e($it['instructions']) ?></p><?php endif; ?>
        <?php if ($it['notes']): ?><p class="small muted pre"><b>Observação:</b> <?= e($it['notes']) ?></p><?php endif; ?>
        <?php if ($it['video_file_id']): ?>
          <div class="video-embed"><video controls preload="none" playsinline src="<?= e(Files::url((int) $it['video_file_id'])) ?>"></video></div>
        <?php elseif ($embed): ?>
          <details><summary class="small"><?= icon('play', 'icon-sm') ?> Ver vídeo demonstrativo</summary><div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo: <?= e($it['name']) ?>" allow="encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div></details>
        <?php elseif ($it['video_url']): ?>
          <a class="small" href="<?= e($it['video_url']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon-sm') ?> Abrir vídeo</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
