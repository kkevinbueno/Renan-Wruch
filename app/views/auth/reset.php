<div class="auth-wrap">
  <div class="auth-card">
    <h1>Criar nova senha</h1>
    <form method="post" action="/redefinir-senha" class="card mt-lg" data-loading>
      <?= csrf_field() ?>
      <input type="hidden" name="t" value="<?= e($token) ?>">
      <div class="field">
        <label for="password">Nova senha</label>
        <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required autofocus>
      </div>
      <div class="field">
        <label for="password_confirm">Confirmar nova senha</label>
        <input id="password_confirm" type="password" name="password_confirm" autocomplete="new-password" minlength="8" required>
      </div>
      <button class="btn block mt-lg" type="submit">Salvar nova senha</button>
    </form>
  </div>
</div>
