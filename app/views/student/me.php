<?php $u = $user; ?>
<div class="card block" style="display:flex;gap:16px;align-items:center;background-image:var(--glow)">
  <span class="av-wrap"><?= avatar($u, 'lg') ?></span>
  <div class="grow">
    <h1 style="font-size:2.2rem"><?= e($u['name']) ?></h1>
    <p class="small" style="color:var(--on-block-2)"><?= $modalities ? e(implode(' · ', $modalities)) : 'Aluno' ?></p>
  </div>
</div>
<div class="stats mt" style="grid-template-columns:repeat(2,minmax(0,1fr))">
  <a class="stat" href="/app/progresso"><span class="lbl">treinos feitos</span><span class="num" data-count="<?= (int) $total ?>"><?= (int) $total ?></span></a>
  <a class="stat attention" href="/app/progresso"><span class="lbl">treinos seguidos 🔥</span><span class="num" data-count="<?= (int) $streak ?>"><?= (int) $streak ?></span></a>
</div>

<div class="stack mt enter">
  <a class="todo" href="/app/progresso"><span class="ico blue"><?= icon('trend') ?></span><div class="grow"><div class="t">Meu progresso</div><div class="xs muted">Dias treinados, autoavaliações e fotos</div></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a class="todo" href="/app/avaliacoes"><span class="ico"><?= icon('camera') ?></span><div class="grow"><div class="t">Avaliações físicas</div><div class="xs muted"><?= $profile['next_assessment_on'] ? 'Próxima: ' . e(fmt_date_short($profile['next_assessment_on'])) : 'Fotos e orientações do Renan' ?></div></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a class="todo" href="/app/assinatura"><span class="ico <?= $standing['tone'] === 'ok' ? 'ok' : ($standing['tone'] === 'muted' ? '' : $standing['tone']) ?>"><?= icon('card') ?></span><div class="grow"><div class="t">Assinatura</div><div class="xs muted"><?= e($standing['label']) ?></div></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a class="todo" href="/app/perfil"><span class="ico"><?= icon('user') ?></span><div class="grow"><div class="t">Meus dados, foto e senha</div><div class="xs muted"><?= e($u['email']) ?></div></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a class="todo" href="/app/perfil#notificacoes"><span class="ico"><?= icon('bell') ?></span><div class="grow"><div class="t">Notificações</div><div class="xs muted">E-mails e lembretes</div></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a class="todo" href="/app/perfil#privacidade"><span class="ico"><?= icon('shield') ?></span><div class="grow"><div class="t">Privacidade (LGPD)</div><div class="xs muted">Seus dados e solicitações</div></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a class="todo" href="/app/historico"><span class="ico"><?= icon('clock') ?></span><div class="grow"><div class="t">Histórico de treinos</div></div><?= icon('chevron-right', 'icon-sm') ?></a>
</div>

<div class="stack mt-lg">
  <?php if (Auth::isDeveloper()): ?>
    <form method="post" action="/alternar-interface"><?= csrf_field() ?><input type="hidden" name="to" value="trainer"><button class="btn secondary block" type="submit"><?= icon('repeat', 'icon-sm') ?> Alternar para Renan</button></form>
  <?php endif; ?>
  <form method="post" action="/sair"><?= csrf_field() ?><button class="btn ghost block" type="submit"><?= icon('logout', 'icon-sm') ?> Sair da conta</button></form>
</div>
