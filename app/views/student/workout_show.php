<?php $st = $session['status'] ?? null; $canStart = $date <= today(); ?>
<a class="btn ghost sm" href="/app/treinos"><?= icon('chevron-left', 'icon-sm') ?> Treinos</a>
<section class="hero-workout <?= e(mod_tone($modality)) ?> mt">
  <div class="top"><span class="chip mod"><?= e($modality['name']) ?></span><span class="eyebrow"><?= e(fmt_date_short($date)) ?> · <?= e(Schedule::sessionStatusLabel($st)) ?></span></div>
  <h2><?= e(Workouts::title($workout)) ?></h2>
  <p class="meta"><?= e(weekday_names(Workouts::weekdays($workout))) ?></p>
  <div class="btn-row mt">
    <?php if ($canStart && $st !== 'completed'): ?>
      <form method="post" action="/app/treinos/<?= (int) $workout['id'] ?>/iniciar"><?= csrf_field() ?><input type="hidden" name="date" value="<?= e($date) ?>">
        <button class="btn" type="submit"><?= icon('play') ?> <?= $st === 'in_progress' ? 'Continuar treino' : 'Começar treino' ?></button></form>
    <?php elseif ($st === 'completed'): ?>
      <a class="btn lg block" href="/app/sessao/<?= (int) $session['id'] ?>"><?= icon('check') ?> Ver registro</a>
    <?php endif; ?>
  </div>
</section>
<div class="mt"></div>

<?php if ($version['general_notes']): ?>
  <div class="callout mb"><?= icon('clipboard') ?><p class="pre small"><?= e($version['general_notes']) ?></p></div>
<?php endif; ?>

<?= View::partial('workout_items', ['items' => $items]) ?>

<details class="card more section">
  <summary><?= icon('repeat', 'icon-sm') ?> Não vai treinar hoje ou quer trocar o dia?</summary>
<div class="grid grid-2 mt">
  <form method="post" action="/app/treinos/<?= (int) $workout['id'] ?>/situacao">
    <?= csrf_field() ?>
    <input type="hidden" name="date" value="<?= e($date) ?>">
    <h3>Não vai treinar com o registro completo?</h3>
    <p class="small muted mt">Informe ao Renan a situação deste treino em <?= e(fmt_date_short($date)) ?>.</p>
    <div class="pills mt">
      <?php if ($canStart): ?><label class="pill-check"><input type="radio" name="status" value="completed"><span>Realizado</span></label><?php endif; ?>
      <label class="pill-check"><input type="radio" name="status" value="postponed" checked><span>Adiado</span></label>
      <label class="pill-check"><input type="radio" name="status" value="skipped"><span>Não realizado</span></label>
    </div>
    <div class="field"><label for="st_notes">Observação (opcional)</label><textarea id="st_notes" name="notes" rows="2"></textarea></div>
    <button class="btn secondary mt" type="submit">Registrar situação</button>
  </form>

  <form method="post" action="/app/treinos/<?= (int) $workout['id'] ?>/mudar-dia">
    <?= csrf_field() ?>
    <h3>Pedir mudança de dia</h3>
    <p class="small muted mt">O Renan aprova ou recusa. A programação muda somente após a aprovação.</p>
    <div class="form-grid cols-2 mt">
      <div class="field"><label for="od">Data original</label><input id="od" type="date" name="original_date" value="<?= e($date) ?>" required></div>
      <div class="field"><label for="nd">Nova data</label><input id="nd" type="date" name="requested_date" min="<?= e(today()) ?>" required></div>
    </div>
    <div class="field"><label for="rq_note">Motivo (opcional)</label><textarea id="rq_note" name="note" rows="2"></textarea></div>
    <button class="btn secondary mt" type="submit"><?= icon('repeat', 'icon-sm') ?> Enviar pedido</button>
  </form>
</div>
</details>
