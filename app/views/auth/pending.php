<?php $wa = 'https://wa.me/' . Settings::get('contact_whatsapp'); ?>
<div class="auth-wrap">
  <div class="auth-card card">
    <?php if ($user['status'] === 'rejected'): ?>
      <?= icon('alert', 'icon-lg') ?>
      <h1 class="mt">Cadastro não aprovado</h1>
      <p class="muted mt">Seu cadastro não foi aprovado neste momento.<?= $user['decision_note'] ? ' Observação do Renan: ' . e($user['decision_note']) : '' ?></p>
      <p class="muted mt">Se quiser conversar sobre isso, fale diretamente com o Renan.</p>
    <?php else: ?>
      <?= icon('clock', 'icon-lg') ?>
      <h1 class="mt">Cadastro em análise</h1>
      <p class="muted mt">Obrigado, <?= e(first_name($user['name'])) ?>! Seu e-mail foi confirmado e o Renan já recebeu o seu cadastro. Você receberá um e-mail assim que ele for aprovado.</p>
      <p class="muted mt small">Até lá, as informações da consultoria ficam indisponíveis.</p>
    <?php endif; ?>
    <div class="stack-sm mt-lg">
      <a class="btn secondary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Falar no WhatsApp</a>
      <form method="post" action="/sair"><?= csrf_field() ?><button class="btn ghost block" type="submit">Sair</button></form>
    </div>
  </div>
</div>
