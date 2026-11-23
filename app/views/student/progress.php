<a class="btn ghost sm" href="/app/eu"><?= icon('chevron-left', 'icon-sm') ?> Eu</a>
<div class="page-head mt"><div><h1>Seu progresso</h1></div></div>

<div class="stats" style="grid-template-columns:repeat(2,minmax(0,1fr))">
  <div class="stat"><span class="lbl">treinos feitos</span><span class="num" data-count="<?= (int) $total ?>"><?= (int) $total ?></span></div>
  <div class="stat block"><span class="lbl">treinos seguidos 🔥</span><span class="num" data-count="<?= (int) $streak ?>"><?= (int) $streak ?></span></div>
</div>

<div class="grid grid-2 mt">
  <div class="card">
    <div class="card-head"><h3>Dias ativos</h3><span class="xs muted">últimas 14 semanas</span></div>
    <div class="heat" aria-label="Mapa de dias treinados">
      <?php foreach ($heat as $i => $c): ?><i class="<?= $c['level'] > 0 ? 'l' . $c['level'] : '' ?>" style="--i:<?= $i ?><?= $c['level'] < 0 ? ';opacity:.25' : '' ?>" title="<?= e(fmt_date_short($c['date'])) ?>"></i><?php endforeach; ?>
    </div>
    <div class="legend"><span><i class="dot" style="background:var(--text)"></i> treino feito</span><span><i class="dot" style="background:var(--powder)"></i> autoavaliação + alimentação</span><span><i class="dot" style="background:color-mix(in srgb,var(--powder) 55%,var(--surface-2))"></i> algum registro</span></div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Como você tem se sentido</h3>
      <?php if (count($series) >= 2): $diff = $series[count($series) - 1]['avg'] - $series[0]['avg']; ?>
        <span class="badge <?= $diff >= 0 ? 'ok' : 'warn' ?>"><?= $diff >= 0 ? '↑' : '↓' ?> <?= number_format(abs($diff), 1, ',', '') ?></span>
      <?php endif; ?>
    </div>
    <?php if (count($series) < 2): ?>
      <p class="muted small">Faça pelo menos duas autoavaliações para ver a sua evolução aqui.</p>
      <a class="btn sm mt" href="/app/autoavaliacao">Fazer autoavaliação</a>
    <?php else: ?>
      <svg viewBox="0 0 340 120" width="100%" height="140" preserveAspectRatio="none" aria-label="Média das autoavaliações">
        <path class="chart-area" d="<?= e($paths['area']) ?>"/>
        <path class="chart-line" d="<?= e($paths['line']) ?>" vector-effect="non-scaling-stroke"/>
      </svg>
      <div class="row between xs muted"><span><?= e(fmt_date_short($series[0]['date'])) ?></span><span>média de 1 a 5</span><span><?= e(fmt_date_short($series[count($series) - 1]['date'])) ?></span></div>
    <?php endif; ?>
  </div>
</div>

<div class="card mt">
  <div class="card-head"><h3>Antes e depois</h3><a class="link small" href="/app/avaliacoes">Avaliações</a></div>
  <?php if ($first && $last): ?>
    <div class="compare">
      <figure style="margin:0"><img src="<?= e(Files::url((int) $first['file_id'])) ?>" alt="Foto de frente" style="border-radius:18px;aspect-ratio:3/4;object-fit:cover;width:100%"><figcaption class="xs muted mt"><?= e(fmt_date(substr($first['submitted_at'], 0, 10))) ?></figcaption></figure>
      <figure style="margin:0"><img src="<?= e(Files::url((int) $last['file_id'])) ?>" alt="Foto de frente" style="border-radius:18px;aspect-ratio:3/4;object-fit:cover;width:100%"><figcaption class="xs muted mt"><?= e(fmt_date(substr($last['submitted_at'], 0, 10))) ?></figcaption></figure>
    </div>
  <?php else: ?>
    <p class="muted small">A comparação aparece depois de duas avaliações físicas enviadas.</p>
  <?php endif; ?>
</div>
