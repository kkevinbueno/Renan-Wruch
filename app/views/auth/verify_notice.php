<div class="auth-wrap">
  <div class="auth-card card">
    <?= icon('mail', 'icon-lg') ?>
    <h1 class="mt">Confirme seu e-mail</h1>
    <p class="muted mt">Enviamos um link de confirmação para <b><?= e($email) ?></b>. Abra o e-mail e toque no botão para continuar. Confira também a caixa de spam.</p>
    <form method="post" action="/confirmar-email/reenviar" class="mt-lg" data-loading>
      <?= csrf_field() ?>
      <button class="btn secondary block" type="submit">Reenviar link</button>
    </form>
    <form method="post" action="/sair" class="mt">
      <?= csrf_field() ?>
      <button class="btn ghost block" type="submit">Usar outro e-mail</button>
    </form>
  </div>
</div>
