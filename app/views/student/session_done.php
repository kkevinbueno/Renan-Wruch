<?php $feelings = ['😫' => 'Muito cansativo', '😕' => 'Difícil', '🙂' => 'Bom', '😄' => 'Muito bom', '🤩' => 'Excelente']; ?>
<section class="done-screen <?= e(mod_tone((int) $workout['modality_id'])) ?>" data-confetti>
  <div class="trophy" aria-hidden="true">🏆</div>
  <h1>Treino<br>concluído!</h1>
  <p style="opacity:.85;margin-top:10px">Mandou bem, <?= e(first_name(Auth::user()['name'])) ?>. O Renan já pode ver o seu registro.</p>
  <div class="done-stats">
    <div><span class="num" data-count="<?= (int) $minutes ?>"><?= (int) $minutes ?: '—' ?></span><small>minutos</small></div>
    <div><span class="num" data-count="<?= $volume ?: $sets ?>"><?= $volume ? number_format($volume, 0, ',', '.') : $sets ?></span><small><?= $volume ? 'kg levantados' : 'séries feitas' ?></small></div>
    <div><span class="num"><?= (int) $streak ?>🔥</span><small>treinos seguidos</small></div>
  </div>
  <form method="post" action="/app/sessao/<?= (int) $session['id'] ?>/concluir" class="card" style="margin-top:14px;background:color-mix(in srgb, var(--on-block) 10%, transparent);border:0;color:inherit;text-align:left">
    <?= csrf_field() ?>
    <b>Como foi?</b>
    <div class="row between mt" style="flex-wrap:nowrap">
      <?php foreach ($feelings as $emo => $label): ?>
        <button type="submit" name="feeling" value="<?= $emo ?>" class="btn ghost" style="font-size:1.8rem;padding:0 8px;min-height:54px;color:inherit" aria-label="<?= e($label) ?>"><?= $emo ?></button>
      <?php endforeach; ?>
    </div>
  </form>
  <div class="btn-row mt">
    <a class="btn pw lg block" href="/app" style="background:var(--powder);border-color:var(--powder);color:var(--ink)">Voltar ao início</a>
  </div>
</section>
