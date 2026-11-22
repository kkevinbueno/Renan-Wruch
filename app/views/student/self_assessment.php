<?php $scores = $current ? json_decode_array($current['scores']) : []; ?>
<div class="page-head">
  <div><h1>Autoavaliação</h1><p>Como foram os seus últimos dias? Leva menos de um minuto.</p></div>
  <?php if ($dueDate <= today() && !$current): ?><span class="badge warn">Pendente</span><?php else: ?><span class="badge outline">Próxima: <?= e(fmt_date_short(max($dueDate, today()))) ?></span><?php endif; ?>
</div>

<div class="grid grid-main">
  <form class="card" method="post" action="/app/autoavaliacao" data-scale-form>
    <?= csrf_field() ?>
    <div class="row between mb">
      <div class="field" style="max-width:200px"><label for="d">Data de referência</label><input id="d" type="date" name="date" value="<?= e($date) ?>" max="<?= e(today()) ?>" min="<?= e(date_add_days(today(), -14)) ?>" data-reload-on-change="/app/autoavaliacao?data="></div>
      <?php if ($current): ?><span class="badge ok">Já preenchida — você pode atualizar</span><?php endif; ?>
    </div>
    <?php foreach ($categories as $key => $label): $val = (int) ($scores[$key] ?? 0); ?>
      <fieldset class="scale-row fieldset" style="margin:0">
        <legend class="strong" style="margin:0"><?= e($label) ?></legend>
        <div class="scale" role="radiogroup">
          <?php foreach (SelfEval::SCALE as $n => $opt): ?>
            <label title="<?= e(SelfEval::DESCRIPTIONS[$key][$n]) ?>">
              <input type="radio" name="scores[<?= e($key) ?>]" value="<?= $n ?>" required<?= checked($val === $n) ?> data-desc="<?= e(SelfEval::DESCRIPTIONS[$key][$n]) ?>">
              <span class="opt"><span class="emoji" aria-hidden="true"><?= $opt['emoji'] ?></span><small><?= $n ?> · <?= e($opt['label']) ?></small></span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="scale-desc" data-desc-out><?= $val ? e(SelfEval::DESCRIPTIONS[$key][$val]) : '' ?></p>
      </fieldset>
    <?php endforeach; ?>
    <div class="field mt"><label for="notes">Observações (opcional)</label><textarea id="notes" name="notes" rows="3" placeholder="Como se sentiu, dificuldades, algo que aconteceu…"><?= e($current['notes'] ?? '') ?></textarea></div>
    <p class="hint mt">As notas são a sua percepção e ajudam o Renan a ajustar o planejamento. Elas não são diagnósticos.</p>
    <button class="btn mt" type="submit">Salvar autoavaliação</button>
  </form>

  <div class="stack">
    <form class="card" method="post" action="/app/autoavaliacao/lembrete">
      <?= csrf_field() ?>
      <h3>Lembrete por e-mail</h3>
      <p class="small muted mt">Frequência definida pelo Renan: a cada <?= plural((int) $profile['self_eval_every_days'], 'dia', 'dias') ?>.</p>
      <div class="field"><label for="t">Horário preferido</label><input id="t" type="time" name="time" value="<?= e($profile['self_eval_reminder_time'] ?: '08:00') ?>" required></div>
      <button class="btn secondary sm mt" type="submit">Salvar horário</button>
    </form>
    <div class="card">
      <h3>Últimos registros</h3>
      <?php if (!$history): ?><p class="small muted mt">Nenhum registro ainda.</p><?php endif; ?>
      <div class="list mt">
        <?php foreach ($history as $h): $sc = json_decode_array($h['scores']); ?>
          <a class="list-item" href="/app/autoavaliacao?data=<?= e($h['ref_date']) ?>" style="flex-direction:column;align-items:flex-start;gap:6px">
            <span class="small strong"><?= e(fmt_date($h['ref_date'], true)) ?></span>
            <span class="score-chips"><?php foreach ($sc as $k => $v): ?><span class="score-chip<?= $v <= 2 ? ' low' : '' ?>"><?= e(Students::SELF_EVAL_CATEGORIES[$k] ?? $k) ?> <?= SelfEval::SCALE[$v]['emoji'] ?? '' ?></span><?php endforeach; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
