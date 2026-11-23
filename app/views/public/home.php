<?php
$s = Settings::all();
$wa = 'https://wa.me/' . preg_replace('/\D/', '', (string) $s['contact_whatsapp']);
$words = preg_split('/\s+/', trim((string) $s['public_headline'])) ?: [];
$lastWord = array_pop($words);
$tones = ['tenis' => 'm-tenis', 'beach-tennis' => 'm-beach-tennis', 'volei-de-areia' => 'm-volei-de-areia', 'natacao' => 'm-natacao', 'musculacao' => 'm-musculacao', 'condicionamento' => 'm-condicionamento'];
?>
<section class="hero">
  <div>
    <span class="eyebrow"><span class="dot" style="background:var(--blue)"></span> Consultoria esportiva online · <?= e($s['trainer_name']) ?></span>
    <h1><?php foreach ($words as $i => $w): ?><span class="w" style="--i:<?= $i ?>"><?= e($w) ?></span> <?php endforeach; ?><?php if ($lastWord !== null): ?><span class="w" style="--i:<?= count($words) ?>"><span class="hl"><?= e($lastWord) ?></span></span><?php endif; ?></h1>
    <p class="lead"><?= e($s['public_intro']) ?></p>
    <div class="cta">
      <a class="btn pw lg" href="/cadastro">Quero ser aluno <?= icon('chevron-right', 'icon-sm') ?></a>
      <a class="btn secondary lg" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
    </div>
    <div class="hero-facts">
      <div><span class="num"><?= count($modalities) ?></span><small>modalidades</small></div>
      <div><span class="num">1:1</span><small>chat direto</small></div>
      <div><span class="num">100%</span><small>no celular</small></div>
    </div>
  </div>
  <div class="hero-photo">
    <?php if ($photoUrl): ?>
      <img src="<?= e($photoUrl) ?>" alt="<?= e($s['trainer_name']) ?>">
    <?php else: ?>
      <span class="hero-mono">RW</span>
    <?php endif; ?>
    <div class="float-card" style="top:22px;left:22px"><?= ring(1, 40, 5, icon('check', 'icon-sm')) ?><div><b>Treino concluído</b><small>registro de cada série</small></div></div>
    <div class="float-card" style="top:120px;right:22px"><span style="font-size:1.5rem">🔥</span><div><b>Sequência de treinos</b><small>acompanhe sua evolução</small></div></div>
    <div class="float-card" style="bottom:22px;right:22px;max-width:250px;display:block"><b>Mensagem do <?= e(first_name($s['trainer_name'])) ?></b><small style="margin-top:4px">Conversa direta pelo app, com fotos, vídeos e áudios.</small></div>
  </div>
</section>

<?php if ($modalities): ?>
<div class="marquee" aria-hidden="true"><div class="track">
  <?php for ($i = 0; $i < 4; $i++): foreach ($modalities as $m): ?><span class="<?= e($tones[$m['slug']] ?? '') ?>"><i></i><?= e($m['name']) ?></span><?php endforeach; endfor; ?>
</div></div>
<?php endif; ?>

<?php if (trim((string) $s['public_about']) !== ''): ?>
<section class="pub-section" id="sobre">
  <p class="section-title">Quem treina você</p>
  <h2>Sobre o <?= e(first_name($s['trainer_name'])) ?></h2>
  <p class="muted pre" style="max-width:46em;font-size:1.05rem;line-height:1.7"><?= e($s['public_about']) ?></p>
</section>
<?php endif; ?>

<section class="pub-section" id="modalidades">
  <p class="section-title">Modalidades</p>
  <h2>Um plano para cada esporte</h2>
  <p class="muted">Cada esporte tem um planejamento e uma forma de registro próprios.</p>
  <div class="mod-grid">
    <?php foreach ($modalities as $i => $m): ?><div class="mod-card <?= e($tones[$m['slug']] ?? '') ?>" data-n="<?= sprintf('%02d', $i + 1) ?>"><?= e($m['name']) ?></div><?php endforeach; ?>
  </div>
</section>

<section class="pub-section" id="como-funciona">
  <p class="section-title">Como funciona</p>
  <h2>Quatro passos até o primeiro treino</h2>
  <div class="steps">
    <div class="step"><h3>Cadastro</h3><p class="muted small mt">Crie sua conta em um minuto. O Renan aprova o seu acesso.</p></div>
    <div class="step"><h3>Avaliação inicial</h3><p class="muted small mt">Responda algumas perguntas e envie 3 fotos.</p></div>
    <div class="step"><h3>Treino sob medida</h3><p class="muted small mt">Seu plano semanal chega no app, com vídeos e instruções.</p></div>
    <div class="step"><h3>Evolução</h3><p class="muted small mt">Registre, converse com o Renan e acompanhe seu progresso.</p></div>
  </div>
</section>

<section class="pub-section" id="recursos">
  <p class="section-title">Plataforma</p>
  <h2>Tudo no seu celular</h2>
  <div class="grid grid-3 mt">
    <div class="card feature"><?= icon('dumbbell') ?><h3 class="mt">Treino do dia</h3><p class="muted small mt">Exercícios, vídeos e registro de séries, cargas, tempo e distância.</p></div>
    <div class="card feature"><?= icon('calendar') ?><h3 class="mt">Agenda</h3><p class="muted small mt">Programação semanal, avaliações e pedidos de troca de dia.</p></div>
    <div class="card feature"><?= icon('message') ?><h3 class="mt">Chat com o Renan</h3><p class="muted small mt">Mensagens, fotos, vídeos, documentos e áudios.</p></div>
    <div class="card feature"><?= icon('camera') ?><h3 class="mt">Avaliações por fotos</h3><p class="muted small mt">Envios periódicos com armazenamento privado.</p></div>
    <div class="card feature"><?= icon('smile') ?><h3 class="mt">Autoavaliação</h3><p class="muted small mt">Sono, disposição, recuperação e mais em 30 segundos.</p></div>
    <div class="card feature"><?= icon('shield') ?><h3 class="mt">Privacidade</h3><p class="muted small mt">Seus dados e fotos visíveis só para você e o Renan.</p></div>
  </div>
</section>

<?php if ($plans): ?>
<section class="pub-section" id="planos">
  <p class="section-title">Planos</p>
  <h2>Planos</h2>
  <p class="muted">A contratação é feita dentro da plataforma, depois que o seu cadastro for aprovado.</p>
  <div class="grid grid-3">
    <?php foreach ($plans as $p): ?>
      <div class="plan">
        <h3><?= e($p['name']) ?></h3>
        <div><span class="price"><?= money((int) $p['price_cents']) ?></span> <span class="muted small">/ <?= (int) $p['period_months'] === 1 ? 'mês' : (int) $p['period_months'] . ' meses' ?></span></div>
        <?php if ($p['description']): ?><p class="muted small pre"><?= e($p['description']) ?></p><?php endif; ?>
        <a class="btn secondary mt" href="/cadastro">Começar</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="pub-section" id="contato">
  <p class="section-title">Contato</p>
  <h2>Tire suas dúvidas</h2>
  <div class="contact-list">
    <a class="btn secondary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> <?= e($s['contact_whatsapp_label']) ?></a>
    <a class="btn secondary" href="<?= e($s['contact_instagram']) ?>" target="_blank" rel="noopener"><?= icon('instagram') ?> Instagram</a>
    <a class="btn secondary" href="mailto:<?= e($s['contact_email']) ?>"><?= icon('mail') ?> <?= e($s['contact_email']) ?></a>
  </div>
  <div class="cta-band mt-lg">
    <h2 style="margin-top:0">Seu próximo treino começa aqui</h2>
    <div class="btn-row mt">
      <a class="btn lg" href="/cadastro">Criar minha conta</a>
      <a class="btn secondary lg" href="/entrar">Já sou aluno</a>
    </div>
  </div>
</section>
