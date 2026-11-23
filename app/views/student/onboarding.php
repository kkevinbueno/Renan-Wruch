<?php
$answers = $anamnesis['answers'];
$stepNames = [1 => 'Sobre você', 2 => 'Fotos', 3 => 'Enviar'];
$optional = [];
?>
<div class="page-head">
  <div>
    <h1>Avaliação inicial</h1>
    <p>3 passos rápidos para o Renan montar o seu treino. Salva sozinho — dá para continuar depois.</p>
  </div>
  <span class="save-state" data-save-state><?= $anamnesis['updated_at'] !== $anamnesis['created_at'] ? 'Rascunho salvo às ' . e(fmt_time($anamnesis['updated_at'])) : '' ?></span>
</div>

<div class="content narrow" style="padding:0">
<nav class="stepper" data-stepper aria-label="Etapas">
  <?php foreach ($stepNames as $n => $label): ?>
    <button type="button" data-goto="<?= $n ?>" class="<?= $n === 1 ? 'current' : '' ?>"><span class="bar"></span><small><?= $n ?>. <?= e($label) ?></small></button>
  <?php endforeach; ?>
</nav>

<form method="post" action="/app/avaliacao-inicial" data-steps data-autosave="/app/avaliacao-inicial" novalidate>
  <?= csrf_field() ?>
  <section class="step-panel card current" data-step="1">
    <?php foreach ($sections as $key => $sec): $req = array_filter($sec['fields'], fn($f) => !empty($f['required'])); $opt = array_filter($sec['fields'], fn($f) => empty($f['required'])); if ($opt) $optional[$sec['title']] = $opt; if (!$req) continue; ?>
      <fieldset class="fieldset">
        <legend><?= e($sec['title']) ?></legend>
        <?php foreach ($req as $f): ?>
          <?= View::partial('anamnesis_field', ['f' => $f, 'value' => $answers[$f['key']] ?? ($f['type'] === 'checkboxes' ? [] : '')]) ?>
        <?php endforeach; ?>
      </fieldset>
    <?php endforeach; ?>
    <?php if ($optional): ?>
      <details class="more"<?= array_filter(array_merge(...array_map(fn($fs) => array_map(fn($f) => $answers[$f['key']] ?? null, $fs), array_values($optional)))) ? ' open' : '' ?>>
        <summary><?= icon('plus', 'icon-sm') ?> Quero contar mais (opcional)</summary>
        <?php foreach ($optional as $title => $fields): ?>
          <div class="mt">
            <p class="section-title" style="margin-bottom:8px"><?= e($title) ?></p>
            <?php foreach ($fields as $f): ?>
              <?= View::partial('anamnesis_field', ['f' => $f, 'value' => $answers[$f['key']] ?? ($f['type'] === 'checkboxes' ? [] : '')]) ?>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </details>
    <?php endif; ?>
  </section>

  <section class="step-panel card" data-step="2">
    <fieldset class="fieldset">
      <legend>Fotografias</legend>
      <p class="muted small mb">Obrigatórias: frente, lado e costas. Use roupa de treino, boa iluminação e corpo inteiro no enquadramento. Você pode pré-visualizar e substituir cada foto antes de enviar.</p>
      <?= View::partial('photo_slots', ['slots' => $slots, 'photos' => $photos, 'uploadUrl' => '/app/avaliacao-inicial/foto', 'deleteBase' => '/app/avaliacao-inicial/foto/{id}/excluir', 'editable' => true]) ?>
    </fieldset>
  </section>

  <section class="step-panel card" data-step="3">
    <fieldset class="fieldset">
      <legend>Revisar e enviar</legend>
      <p class="muted">Ao enviar, o Renan é avisado e começa a montar o seu treino.</p>
      <details class="more mt"<?= $anamnesis['personal_notes'] ? ' open' : '' ?>>
        <summary><?= icon('edit', 'icon-sm') ?> Contar como você se sente hoje (opcional)</summary>
        <div class="field mt">
          <label for="personal_notes" class="sr-only">Como você se sente hoje</label>
          <textarea id="personal_notes" name="personal_notes" rows="5" placeholder="Condição física, dificuldades, disposição, o que espera do acompanhamento…"><?= e($anamnesis['personal_notes']) ?></textarea>
        </div>
      </details>
      <div class="mt" data-missing-list></div>
      <label class="check mt"><input type="checkbox" name="photo_consent" value="1"> <span>Autorizo o uso das minhas fotografias exclusivamente para o acompanhamento pelo Renan.</span></label>
    </fieldset>
    <button class="btn pw lg block mt" type="submit" name="action" value="submit"><?= icon('send') ?> Enviar avaliação inicial</button>
  </section>

  <div class="row between mt">
    <button class="btn secondary" type="button" data-prev><?= icon('chevron-left', 'icon-sm') ?> Voltar</button>
    <button class="btn" type="button" data-next>Continuar <?= icon('chevron-right', 'icon-sm') ?></button>
  </div>
</form>
</div>
