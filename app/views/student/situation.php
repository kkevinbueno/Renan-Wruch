<?php $wa = 'https://wa.me/' . Settings::get('contact_whatsapp'); ?>
<div class="content narrow" style="padding:0">
  <div class="card">
    <?php if ($billingBlocked): ?>
      <?= icon('card', 'icon-lg') ?>
      <h1 class="mt">Assinatura pendente</h1>
      <p class="muted mt">Para continuar acessando os treinos, regularize sua assinatura. Seu histórico está preservado.</p>
      <p class="mt"><span class="badge <?= e($standing['tone']) ?>"><?= e($standing['label']) ?></span></p>
      <div class="btn-row mt-lg"><a class="btn" href="/app/assinatura">Ver planos e pagamento</a><a class="btn secondary" href="/app/mensagens">Falar com o Renan</a></div>
    <?php elseif ($user['status'] === 'paused'): ?>
      <?= icon('clock', 'icon-lg') ?>
      <h1 class="mt">Acompanhamento pausado</h1>
      <p class="muted mt">Seu acompanhamento está pausado no momento. Você ainda pode conversar com o Renan, consultar sua assinatura e seus dados.</p>
      <div class="btn-row mt-lg"><a class="btn" href="/app/mensagens">Mensagens</a><a class="btn secondary" href="/app/perfil">Meu perfil</a></div>
    <?php elseif ($user['status'] === 'ended'): ?>
      <?= icon('archive', 'icon-lg') ?>
      <h1 class="mt">Acompanhamento encerrado</h1>
      <p class="muted mt">Seu ciclo de acompanhamento foi encerrado. Seu histórico continua salvo — para voltar, fale com o Renan.</p>
      <div class="btn-row mt-lg"><a class="btn" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a><a class="btn secondary" href="/app/perfil">Meus dados</a></div>
    <?php else: ?>
      <h1>Tudo certo</h1>
      <div class="btn-row mt-lg"><a class="btn" href="/app">Ir para o início</a></div>
    <?php endif; ?>
  </div>
</div>
