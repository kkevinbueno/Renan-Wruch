<?php
$s = Settings::all();
$wa = 'https://wa.me/' . preg_replace('/\D/', '', (string) $s['contact_whatsapp']);
$words = preg_split('/\s+/', trim((string) $s['public_headline'])) ?: [];
$lastWord = array_pop($words);
$first = first_name((string) $s['trainer_name']);
$tones = ['tenis' => 'm-tenis', 'beach-tennis' => 'm-beach-tennis', 'volei-de-areia' => 'm-volei-de-areia', 'natacao' => 'm-natacao', 'musculacao' => 'm-musculacao', 'condicionamento' => 'm-condicionamento'];
$hours = max(1, (int) $s['response_hours']);
$facts = array_filter([
    'Formação' => trim((string) $s['trainer_formation']),
    'CREF' => trim((string) $s['trainer_cref']),
    'Encontros presenciais em' => trim((string) $s['trainer_city']),
], fn ($v) => $v !== '');
// Depoimentos: só os cadastrados pelo Renan ("Nome | texto", um por linha).
$testimonials = [];
foreach (preg_split('/\R/', (string) $s['public_testimonials']) ?: [] as $line) {
    [$name, $text] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
    if ($name !== '' && $text !== '') $testimonials[] = ['name' => $name, 'text' => $text];
}
$aboutPhoto = $photoUrl ?: asset('img/renan-retrato.jpg');
?>
<section class="hero">
  <div>
    <span class="eyebrow"><span class="dot" style="background:var(--blue)"></span> Consultoria esportiva · <?= e($s['trainer_name']) ?></span>
    <h1><?php foreach ($words as $i => $w): ?><span class="w" style="--i:<?= $i ?>"><?= e($w) ?></span> <?php endforeach; ?><?php if ($lastWord !== null): ?><span class="w" style="--i:<?= count($words) ?>"><span class="hl"><?= e($lastWord) ?></span></span><?php endif; ?></h1>
    <p class="lead"><?= e($s['public_intro']) ?></p>
    <div class="cta">
      <a class="btn pw lg" href="#planos">Ver planos <?= icon('chevron-right', 'icon-sm') ?></a>
      <a class="btn secondary lg" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
    </div>
    <div class="hero-facts">
      <div><span class="num"><?= count($modalities) ?></span><small>modalidades</small></div>
      <div><span class="num">1:1</span><small>treino individual</small></div>
      <div><span class="num"><?= $hours ?>h</span><small>para responder dúvidas</small></div>
    </div>
  </div>
  <div class="hero-photo">
    <img src="<?= asset('img/renan-corrida.jpg') ?>" alt="<?= e($s['trainer_name']) ?> correndo na pista de atletismo" width="390" height="727" fetchpriority="high">
    <div class="float-card" style="top:22px;left:22px"><?= ring(1, 40, 5, icon('check', 'icon-sm')) ?><div><b>Treino no site</b><small>registro de cada série</small></div></div>
    <div class="float-card" style="bottom:22px;right:22px"><span class="ico blue"><?= icon('pin', 'icon-sm') ?></span><div><b>Presencial</b><small>encontros com o <?= e($first) ?></small></div></div>
  </div>
</section>

<?php if ($modalities): ?>
<div class="marquee" aria-hidden="true"><div class="track">
  <?php for ($i = 0; $i < 4; $i++): foreach ($modalities as $m): ?><span class="<?= e($tones[$m['slug']] ?? '') ?>"><i></i><?= e($m['name']) ?></span><?php endforeach; endfor; ?>
</div></div>
<?php endif; ?>

<section class="pub-section" id="sobre">
  <div class="about">
    <figure class="about-photo">
      <img src="<?= e($aboutPhoto) ?>" alt="Retrato de <?= e($s['trainer_name']) ?>" loading="lazy" width="900" height="900">
    </figure>
    <div>
      <p class="section-title">Quem é o <?= e($first) ?></p>
      <h2><?= e($s['trainer_name']) ?></h2>
      <?php if (trim((string) $s['public_about']) !== ''): ?>
        <p class="muted pre about-text"><?= e($s['public_about']) ?></p>
      <?php else: ?>
        <p class="muted about-text">Treinador de <?= e(mb_strtolower(implode(', ', array_column($modalities, 'name')))) ?>. Acompanhamento individual, online e presencial.</p>
      <?php endif; ?>
      <?php if ($facts): ?>
        <dl class="about-facts">
          <?php foreach ($facts as $label => $value): ?><div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?>
        </dl>
      <?php endif; ?>
      <?php if ($modalities): ?>
        <div class="about-mods">
          <?php foreach ($modalities as $m): ?><span class="mod-chip <?= e($tones[$m['slug']] ?? '') ?>"><i></i><?= e($m['name']) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="pub-section" id="como-funciona">
  <p class="section-title">Como funciona</p>
  <h2>Do cadastro ao primeiro treino</h2>
  <div class="steps">
    <div class="step"><h3>Escolha o plano</h3><p class="muted small mt">Online, com encontros presenciais ou só presencial. Você cria a conta e o <?= e($first) ?> aprova o seu acesso.</p></div>
    <div class="step"><h3>Avaliação inicial</h3><p class="muted small mt">Responda algumas perguntas sobre rotina, objetivo e histórico. Nos planos com presencial, já marque o primeiro encontro.</p></div>
    <div class="step"><h3>Treino sob medida</h3><p class="muted small mt">Seu treino fica na sua área do aluno, no site, um exercício por vez, com vídeos e instruções. Os encontros aparecem na mesma agenda.</p></div>
    <div class="step"><h3>Acompanhamento</h3><p class="muted small mt">Registre o que fez, tire dúvidas com foto, vídeo ou áudio e acompanhe sua evolução.</p></div>
  </div>
</section>

<section class="pub-section" id="planos">
  <p class="section-title">Planos</p>
  <h2>Escolha como treinar</h2>
  <p class="muted">A contratação é feita dentro da plataforma, depois que o seu cadastro for aprovado.</p>
  <?php if ($plans): ?>
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
  <?php else: ?>
  <div class="callout accent"><?= icon('message') ?><div>Os valores são combinados direto com o <?= e($first) ?>. <a class="link" href="<?= e($wa) ?>" target="_blank" rel="noopener">Fale pelo WhatsApp</a>.</div></div>
  <?php endif; ?>

  <div class="faq">
    <h3 class="faq-title">Perguntas frequentes</h3>
    <details><summary>Qual a diferença entre o online e o presencial?</summary><p>No online, o treino, os registros e a conversa com o <?= e($first) ?> acontecem pelo site. Nos planos com presencial, você também tem encontros com ele, na frequência do seu plano. O plano só presencial também inclui o treino e o chat pelo site, para você treinar entre um encontro e outro.</p></details>
    <details><summary>Como funcionam os encontros presenciais?</summary><p>Você marca o encontro nos horários livres do <?= e($first) ?>, dentro do que o seu plano permite. O local é definido por ele<?= $s['trainer_city'] !== '' ? ', em ' . e($s['trainer_city']) : '' ?>, e o encontro aparece na sua agenda com data, hora e endereço.</p></details>
    <details><summary>Em quanto tempo o <?= e($first) ?> responde?</summary><p>O <?= e($first) ?> costuma responder em até <?= $hours ?> h. Dá para mandar a dúvida com foto, vídeo da execução ou áudio.</p></details>
    <details><summary>Preciso baixar algum aplicativo?</summary><p>Não. Tudo funciona pelo site, no navegador do celular ou do computador: treino, agenda dos encontros e conversa com o <?= e($first) ?>. Se quiser, dá para adicionar o site à tela inicial do celular.</p></details>
  </div>
</section>

<?php if ($testimonials): ?>
<section class="pub-section" id="depoimentos">
  <p class="section-title">Depoimentos</p>
  <h2>Quem treina com o <?= e($first) ?></h2>
  <div class="grid grid-3">
    <?php foreach ($testimonials as $t): ?>
      <figure class="testimonial"><blockquote><?= e($t['text']) ?></blockquote><figcaption><?= e($t['name']) ?></figcaption></figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="pub-section" id="contato">
  <div class="cta-band">
    <h2 style="margin-top:0">Comece agora</h2>
    <p class="cta-lead">Acompanhamento de verdade, online e perto de você.</p>
    <div class="btn-row mt">
      <a class="btn lg" href="/cadastro">Criar minha conta</a>
      <a class="btn secondary lg" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
    </div>
  </div>
  <div class="contact-list mt">
    <a class="btn secondary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> <?= e($s['contact_whatsapp_label']) ?></a>
    <a class="btn secondary" href="<?= e($s['contact_instagram']) ?>" target="_blank" rel="noopener"><?= icon('instagram') ?> Instagram</a>
    <a class="btn secondary" href="mailto:<?= e($s['contact_email']) ?>"><?= icon('mail') ?> <?= e($s['contact_email']) ?></a>
  </div>
</section>

<?php if (!Auth::user()): ?>
<div class="sticky-cta"><a class="btn pw lg" href="/cadastro">Quero ser aluno</a></div>
<?php endif; ?>
