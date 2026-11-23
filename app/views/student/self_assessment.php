<?php
$scores = $current ? json_decode_array($current['scores']) : [];
$questions = [
    'sono' => 'Como foi seu sono?', 'alimentacao' => 'Como está sua alimentação?', 'treinamento' => 'Como foram os treinos?', 'cardio' => 'E o fôlego no cardio?',
    'disposicao' => 'Como está sua disposição?', 'recuperacao' => 'Como está a recuperação?', 'motivacao' => 'E a motivação?', 'hidratacao' => 'Como está a hidratação?',
];
$n = count($categories);
?>
<form method="post" action="/app/autoavaliacao" data-one-by-one>
  <?= csrf_field() ?>
  <input type="hidden" name="date" value="<?= e($date) ?>">
  <div class="obo-head">
    <div class="row between"><a class="btn ghost sm icon-only" href="/app" aria-label="Fechar"><?= icon('x') ?></a><span class="small muted strong" data-obo-count></span></div>
    <div class="seg-progress" data-obo-bar style="margin:12px 0 26px"><?php for ($i = 0; $i < $n; $i++): ?><i></i><?php endfor; ?></div>
    <p class="kicker">Autoavaliação · <?= $date === today() ? 'hoje' : e(fmt_date_short($date)) ?></p>
  </div>
  <div class="page-head obo-hide-js">
    <div><h1>Autoavaliação</h1><p>Como foram os seus últimos dias? Leva menos de um minuto.</p></div>
    <?php if ($current): ?><span class="badge ok">Já preenchida — você pode atualizar</span><?php endif; ?>
  </div>

  <?php foreach ($categories as $key => $label): $val = (int) ($scores[$key] ?? 0); ?>
    <fieldset class="scale-row fieldset" style="margin:0">
      <legend><?= e($questions[$key] ?? $label) ?></legend>
      <div class="scale" role="radiogroup">
        <?php foreach (array_reverse(SelfEval::SCALE, true) as $nv => $opt): ?>
          <label title="<?= e(SelfEval::DESCRIPTIONS[$key][$nv]) ?>">
            <input type="radio" name="scores[<?= e($key) ?>]" value="<?= $nv ?>" required<?= checked($val === $nv) ?> data-desc="<?= e(SelfEval::DESCRIPTIONS[$key][$nv]) ?>">
            <span class="opt"><span class="emoji" aria-hidden="true"><?= $opt['emoji'] ?></span><small><?= e($opt['label']) ?></small></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="scale-desc" data-desc-out><?= $val ? e(SelfEval::DESCRIPTIONS[$key][$val]) : '' ?></p>
    </fieldset>
  <?php endforeach; ?>

  <div class="obo-rest">
    <h1 class="obo-only" style="font-size:2.6rem;margin-bottom:16px">Quer contar mais alguma coisa?</h1>
    <div class="field mt"><label for="notes">Observações (opcional)</label><textarea id="notes" name="notes" rows="3" placeholder="Como se sentiu, dificuldades, algo que aconteceu…"><?= e($current['notes'] ?? '') ?></textarea></div>
    <p class="hint mt">É a sua percepção — ajuda o Renan a ajustar o planejamento.</p>
    <button class="btn lg block mt" type="submit"><?= icon('check') ?> Enviar autoavaliação</button>
  </div>
  <button type="button" class="btn ghost sm mt" data-obo-back hidden><?= icon('chevron-left', 'icon-sm') ?> Voltar</button>
</form>

<div class="section">
  <details class="card more">
    <summary><?= icon('clock', 'icon-sm') ?> Registros anteriores e lembrete</summary>
    <div class="grid grid-2 mt">
      <div>
        <?php if (!$history): ?><p class="small muted">Nenhum registro ainda.</p><?php endif; ?>
        <div class="list">
          <?php foreach ($history as $h): $sc = json_decode_array($h['scores']); ?>
            <a class="list-item" href="/app/autoavaliacao?data=<?= e($h['ref_date']) ?>" style="flex-direction:column;align-items:flex-start;gap:6px">
              <span class="small strong"><?= e(fmt_date($h['ref_date'], true)) ?></span>
              <span class="score-chips"><?php foreach ($sc as $k => $v): ?><span class="score-chip<?= $v <= 2 ? ' low' : '' ?>"><?= e(Students::SELF_EVAL_CATEGORIES[$k] ?? $k) ?> <?= SelfEval::SCALE[$v]['emoji'] ?? '' ?></span><?php endforeach; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <form method="post" action="/app/autoavaliacao/lembrete">
        <?= csrf_field() ?>
        <b>Lembrete por e-mail</b>
        <p class="small muted mt">A cada <?= plural((int) $profile['self_eval_every_days'], 'dia', 'dias') ?> (definido pelo Renan).</p>
        <div class="field mt"><label for="t">Horário preferido</label><input id="t" type="time" name="time" value="<?= e($profile['self_eval_reminder_time'] ?: '08:00') ?>" required></div>
        <div class="field"><label for="d">Preencher outro dia</label><input id="d" type="date" value="<?= e($date) ?>" max="<?= e(today()) ?>" min="<?= e(date_add_days(today(), -14)) ?>" data-reload-on-change="/app/autoavaliacao?data="></div>
        <button class="btn secondary sm mt" type="submit">Salvar horário</button>
      </form>
    </div>
  </details>
</div>
