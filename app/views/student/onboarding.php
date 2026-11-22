<?php
$answers = $anamnesis['answers'];
$stepNames = [1 => 'Dados', 2 => 'Objetivos', 3 => 'Rotina', 4 => 'Saúde', 5 => 'Fotos', 6 => 'Observações', 7 => 'Envio'];
$bySteps = [];
foreach ($sections as $key => $sec) $bySteps[$sec['step']][$key] = $sec;
?>
<div class="page-head">
  <div>
    <h1>Avaliação inicial</h1>
    <p>Com estas informações o Renan monta o seu planejamento. Você pode salvar e continuar depois.</p>
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
  <?php for ($n = 1; $n <= 4; $n++): ?>
    <section class="step-panel card<?= $n === 1 ? ' current' : '' ?>" data-step="<?= $n ?>">
      <?php foreach ($bySteps[$n] ?? [] as $sec): ?>
        <fieldset class="fieldset">
          <legend><?= e($sec['title']) ?></legend>
          <?php foreach ($sec['fields'] as $f): ?>
            <?= View::partial('anamnesis_field', ['f' => $f, 'value' => $answers[$f['key']] ?? ($f['type'] === 'checkboxes' ? [] : '')]) ?>
          <?php endforeach; ?>
        </fieldset>
      <?php endforeach; ?>
    </section>
  <?php endfor; ?>

  <section class="step-panel card" data-step="5">
    <fieldset class="fieldset">
      <legend>Fotografias</legend>
      <p class="muted small mb">Obrigatórias: frente, lado e costas. Use roupa de treino, boa iluminação e corpo inteiro no enquadramento. Você pode pré-visualizar e substituir cada foto antes de enviar.</p>
      <?= View::partial('photo_slots', ['slots' => $slots, 'photos' => $photos, 'uploadUrl' => '/app/avaliacao-inicial/foto', 'deleteBase' => '/app/avaliacao-inicial/foto/{id}/excluir', 'editable' => true]) ?>
    </fieldset>
  </section>

  <section class="step-panel card" data-step="6">
    <fieldset class="fieldset">
      <legend>Observações pessoais</legend>
      <div class="field">
        <label for="personal_notes">Conte como você se sente hoje</label>
        <textarea id="personal_notes" name="personal_notes" rows="7" placeholder="Condição física atual, percepções sobre o corpo, dificuldades, disposição, o que espera do acompanhamento…"><?= e($anamnesis['personal_notes']) ?></textarea>
        <span class="hint">Campo livre e opcional.</span>
      </div>
    </fieldset>
  </section>

  <section class="step-panel card" data-step="7">
    <fieldset class="fieldset">
      <legend>Revisar e enviar</legend>
      <p class="muted">Confira as etapas anteriores. Ao enviar, suas respostas e fotos são registradas com data e horário e o Renan é avisado por e-mail.</p>
      <div class="mt" data-missing-list></div>
      <label class="check mt"><input type="checkbox" name="photo_consent" value="1"> <span>Autorizo o uso das minhas fotografias exclusivamente para o acompanhamento pelo Renan.</span></label>
    </fieldset>
    <button class="btn accent block" type="submit" name="action" value="submit">Enviar avaliação inicial</button>
  </section>

  <div class="sticky-actions row between">
    <button class="btn secondary" type="button" data-prev>Voltar</button>
    <button class="btn ghost" type="submit" name="action" value="save">Salvar rascunho</button>
    <button class="btn" type="button" data-next>Continuar</button>
  </div>
</form>
</div>
