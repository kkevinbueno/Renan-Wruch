<?php $st = $session['status'] ?? null; $canStart = $date <= today(); ?>
<div class="page-head">
  <div>
    <p class="muted small"><a href="/app/treinos">Treinos</a> / <?= e($modality['name']) ?></p>
    <h1><?= e(Workouts::title($workout)) ?></h1>
    <p><?= e(fmt_date($date, true)) ?> · <?= e(Schedule::sessionStatusLabel($st)) ?> · <?= e(weekday_names(Workouts::weekdays($workout))) ?></p>
  </div>
  <div class="btn-row">
    <?php if ($canStart && $st !== 'completed'): ?>
      <form method="post" action="/app/treinos/<?= (int) $workout['id'] ?>/iniciar"><?= csrf_field() ?><input type="hidden" name="date" value="<?= e($date) ?>">
        <button class="btn" type="submit"><?= icon('play') ?> <?= $st === 'in_progress' ? 'Continuar' : 'Iniciar treino' ?></button></form>
    <?php elseif ($st === 'completed'): ?>
      <a class="btn secondary" href="/app/sessao/<?= (int) $session['id'] ?>"><?= icon('check') ?> Ver registro</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($version['general_notes']): ?>
  <div class="callout mb"><?= icon('clipboard') ?><p class="pre small"><?= e($version['general_notes']) ?></p></div>
<?php endif; ?>

<?= View::partial('workout_items', ['items' => $items]) ?>

<div class="grid grid-2 section">
  <form class="card" method="post" action="/app/treinos/<?= (int) $workout['id'] ?>/situacao">
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

  <form class="card" method="post" action="/app/treinos/<?= (int) $workout['id'] ?>/mudar-dia">
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
