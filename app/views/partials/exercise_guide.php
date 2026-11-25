<?php
/**
 * Como fazer o exercício (aluno): vídeo, orientações do Renan, execução e cuidados da biblioteca.
 * Tudo dentro de um bloco expansível para não poluir a tela do treino.
 * @var array $it item do treino (com os campos lib_* da biblioteca)
 */
[$embed, $videoFile] = Workouts::itemVideo($it);
$libSteps = array_values(array_filter(array_map(fn($l) => preg_replace('/^\d+\.\s*/', '', trim($l)), preg_split('/\R/', (string) ($it['lib_instructions'] ?? '')) ?: [])));
$libMistakes = array_values(array_filter(array_map(fn($l) => ltrim(trim($l), '•- '), preg_split('/\R/', (string) ($it['lib_mistakes'] ?? '')) ?: [])));
$rest = trim((string) ($it['params']['rest'] ?? ''));
$hasAny = $embed || $videoFile || $it['instructions'] || $it['notes'] || $libSteps || $libMistakes;
if (!$hasAny) return;
$open = !empty($open);
?>
<details class="ex-guide"<?= $open ? ' open' : '' ?>>
  <summary><?= icon($embed || $videoFile ? 'play' : 'clipboard', 'icon-sm') ?> <span>Ver como fazer</span><?= icon('chevron-down', 'icon-sm chev') ?></summary>
  <div class="ex-guide-body">
    <?php if ($videoFile): ?>
      <div class="video-embed"><video controls preload="none" playsinline src="<?= e(Files::url($videoFile)) ?>"></video></div>
    <?php elseif ($embed): ?>
      <div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo: <?= e($it['name']) ?>" allow="encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
    <?php endif; ?>
    <?php if ($rest !== ''): ?><p class="ex-rest"><?= icon('clock', 'icon-sm') ?> Descanso entre séries: <b><?= e($rest) ?></b></p><?php endif; ?>
    <?php if ($it['instructions'] || $it['notes']): ?>
      <div class="ex-coach">
        <p class="kicker">Orientação do <?= e(first_name((string) Settings::get('trainer_name'))) ?></p>
        <?php if ($it['instructions']): ?><p class="small pre"><?= e($it['instructions']) ?></p><?php endif; ?>
        <?php if ($it['notes']): ?><p class="small pre muted"><?= e($it['notes']) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($libSteps): ?>
      <p class="kicker mt">Execução</p>
      <ol class="ex-steps"><?php foreach ($libSteps as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <?php if ($libMistakes): ?>
      <p class="kicker mt">Cuidados</p>
      <ul class="ex-mistakes"><?php foreach ($libMistakes as $m): ?><li><?= icon('alert', 'icon-sm') ?><span><?= e($m) ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</details>
