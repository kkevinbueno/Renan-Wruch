<?php
$s = Settings::all();
$wa = 'https://wa.me/' . preg_replace('/\D/', '', (string) $s['contact_whatsapp']);
?>
<section class="hero">
  <div>
    <p class="eyebrow"><span class="dot accent"></span>Consultoria esportiva online · <?= e($s['trainer_name']) ?></p>
    <h1><?= e($s['public_headline']) ?></h1>
    <p class="lead"><?= e($s['public_intro']) ?></p>
    <div class="cta">
      <a class="btn" href="/cadastro">Quero ser aluno</a>
      <a class="btn secondary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Conversar no WhatsApp</a>
    </div>
  </div>
  <div class="hero-photo">
    <?php if ($photoUrl): ?>
      <img src="<?= e($photoUrl) ?>" alt="<?= e($s['trainer_name']) ?>">
    <?php else: ?>
      <span class="hero-mono">RW</span>
    <?php endif; ?>
    <div class="hero-tag"><span class="strong"><?= e($s['trainer_name']) ?></span><span class="badge dark">Online</span></div>
  </div>
</section>

<?php if ($modalities): ?>
<div class="marquee" aria-hidden="true"><?php for ($i = 0; $i < 3; $i++): foreach ($modalities as $m): ?><span><?= e($m['name']) ?></span><?php endforeach; endfor; ?></div>
<?php endif; ?>

<?php if (trim((string) $s['public_about']) !== ''): ?>
<section class="pub-section" id="sobre">
  <p class="section-title">Quem treina você</p>
  <h2>Sobre o <?= e(first_name($s['trainer_name'])) ?></h2>
  <p class="muted pre" style="max-width:46em;font-size:1.02rem;line-height:1.7"><?= e($s['public_about']) ?></p>
</section>
<?php endif; ?>

<section class="pub-section" id="modalidades">
  <p class="section-title">Modalidades</p>
  <h2>Modalidades atendidas</h2>
  <p class="muted">Cada esporte tem um planejamento e uma forma de registro próprios.</p>
  <div class="mod-grid">
    <?php foreach ($modalities as $i => $m): ?><div class="mod" data-n="<?= sprintf('%02d', $i + 1) ?>"><?= e($m['name']) ?></div><?php endforeach; ?>
  </div>
</section>

<section class="pub-section" id="como-funciona">
  <p class="section-title">Passo a passo</p>
  <h2>Como funciona o acompanhamento</h2>
  <p class="muted">Do cadastro ao treino do dia, tudo acontece em um só lugar — no celular ou no computador.</p>
  <div class="steps">
    <div class="step"><h3>Cadastro</h3><p class="muted small mt">Crie sua conta. O Renan analisa e aprova o seu cadastro.</p></div>
    <div class="step"><h3>Avaliação inicial</h3><p class="muted small mt">Você responde a anamnese e envia fotos de frente, lado e costas.</p></div>
    <div class="step"><h3>Treino personalizado</h3><p class="muted small mt">O Renan monta o seu planejamento semanal, de acordo com sua rotina e modalidade.</p></div>
    <div class="step"><h3>Acompanhamento</h3><p class="muted small mt">Registre os treinos, faça autoavaliações e converse direto com o Renan.</p></div>
  </div>
</section>

<section class="pub-section" id="recursos">
  <p class="section-title">Plataforma</p>
  <h2>O que você encontra na plataforma</h2>
  <div class="grid grid-3 mt">
    <div class="card feature"><?= icon('dumbbell') ?><h3 class="mt">Treino do dia</h3><p class="muted small mt">Exercícios, vídeos e instruções do seu treino, com registro de séries, cargas, tempo e distância.</p></div>
    <div class="card feature"><?= icon('calendar') ?><h3 class="mt">Calendário</h3><p class="muted small mt">Programação semanal, avaliações e atividades. Pedidos de mudança de dia direto pelo app.</p></div>
    <div class="card feature"><?= icon('message') ?><h3 class="mt">Chat com o Renan</h3><p class="muted small mt">Mensagens, fotos, vídeos, documentos e áudios em uma conversa privada.</p></div>
    <div class="card feature"><?= icon('camera') ?><h3 class="mt">Avaliações por fotos</h3><p class="muted small mt">Envios periódicos com armazenamento privado e orientações do treinador.</p></div>
    <div class="card feature"><?= icon('smile') ?><h3 class="mt">Autoavaliação</h3><p class="muted small mt">Sono, alimentação, disposição, recuperação e mais, em menos de um minuto.</p></div>
    <div class="card feature"><?= icon('shield') ?><h3 class="mt">Privacidade</h3><p class="muted small mt">Dados de saúde e fotografias protegidos, com acesso restrito a você e ao Renan.</p></div>
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
  <h2>Contato</h2>
  <p class="muted">Tire suas dúvidas antes de começar.</p>
  <div class="contact-list">
    <a class="btn secondary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> <?= e($s['contact_whatsapp_label']) ?></a>
    <a class="btn secondary" href="<?= e($s['contact_instagram']) ?>" target="_blank" rel="noopener"><?= icon('instagram') ?> Instagram</a>
    <a class="btn secondary" href="mailto:<?= e($s['contact_email']) ?>"><?= icon('mail') ?> <?= e($s['contact_email']) ?></a>
  </div>
  <div class="cta-band mt-lg">
    <h2>Seu próximo treino começa aqui</h2>
    <div class="btn-row mt">
      <a class="btn" href="/cadastro">Criar minha conta</a>
      <a class="btn secondary" href="/entrar">Já sou aluno</a>
    </div>
  </div>
</section>
