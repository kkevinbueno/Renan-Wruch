<div class="auth-wrap">
  <div class="auth-card">
    <h1>Entrar</h1>
    <p class="muted">Acesse sua área no <?= e(Settings::get('brand_name')) ?>.</p>
    <?= View::partial('social') ?>
    <form method="post" action="/entrar" class="card" data-loading>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
      </div>
      <div class="field">
        <div class="row between"><label for="password">Senha</label><a class="small muted" href="/esqueci-senha">Esqueci a senha</a></div>
        <input id="password" type="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn block mt-lg" type="submit">Entrar</button>
    </form>
    <p class="muted small mt" style="text-align:center">Ainda não tem conta? <a href="/cadastro">Criar conta</a></p>
  </div>
</div>
