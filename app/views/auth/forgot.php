<div class="auth-wrap">
  <div class="auth-card">
    <h1>Recuperar senha</h1>
    <p class="muted">Informe o e-mail da sua conta. Enviaremos um link para criar uma nova senha.</p>
    <form method="post" action="/esqueci-senha" class="card mt-lg" data-loading>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" autocomplete="email" required autofocus>
      </div>
      <button class="btn block mt-lg" type="submit">Enviar link</button>
    </form>
    <p class="muted small mt" style="text-align:center"><a href="/entrar">Voltar para o login</a></p>
  </div>
</div>
