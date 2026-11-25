<?php /** @var array $items  Lista somente leitura de exercícios prescritos. */ ?>
<?php if (!$items): ?>
  <div class="empty"><p>Nenhum exercício nesta versão.</p></div>
<?php endif; ?>
<?php foreach ($items as $i => $it): ?>
  <article class="exercise">
    <div class="exercise-head">
      <span class="exercise-num"><?= $i + 1 ?></span>
      <?php if (!empty($it['exercise_id'])): ?><?= ExerciseLibrary::thumb(['image_file_id' => $it['lib_image_file_id'] ?? null, 'equipment' => $it['lib_equipment'] ?? null, 'primary_muscle' => $it['lib_primary_muscle'] ?? null], 'sm') ?><?php endif; ?>
      <div class="grow">
        <h3><?= e($it['name']) ?></h3>
        <div class="params"><?= params_summary($it['params'], $it['fields']) ?></div>
      </div>
    </div>
    <div class="exercise-body"><?= View::partial('exercise_guide', ['it' => $it]) ?></div>
  </article>
<?php endforeach; ?>
