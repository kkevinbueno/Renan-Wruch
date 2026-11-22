<div class="auth-wrap">
  <div class="auth-card">
    <h1>Criar conta</h1>
    <p class="muted">O cadastro é destinado a alunos da consultoria. Depois de confirmar o e-mail, o Renan aprova o seu acesso.</p>
    <?= View::partial('social') ?>
    <form method="post" action="/cadastro" class="card" data-loading>
      <?= csrf_field() ?>
      <div class="field">
        <label for="name" class="req">Nome completo</label>
        <input id="name" name="name" value="<?= e(old('name')) ?>" autocomplete="name" required minlength="3">
      </div>
      <div class="field">
        <label for="email" class="req">E-mail</label>
        <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required>
      </div>
      <div class="field">
        <label for="password" class="req">Senha</label>
        <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
        <span class="hint">Mínimo de 8 caracteres.</span>
      </div>
      <div class="field">
        <label for="password_confirm" class="req">Confirmar senha</label>
        <input id="password_confirm" type="password" name="password_confirm" autocomplete="new-password" minlength="8" required>
      </div>
      <div class="field mt-lg">
        <label class="check"><input type="checkbox" name="consent_terms" value="1" required> <span>Li e aceito os <a href="/termos" target="_blank">Termos de Uso</a> e a <a href="/privacidade" target="_blank">Política de Privacidade</a>.</span></label>
        <label class="check"><input type="checkbox" name="consent_health" value="1" required> <span>Consinto com o tratamento dos meus dados de saúde, respostas da anamnese e fotografias corporais exclusivamente para o planejamento e acompanhamento dos treinos.</span></label>
      </div>
      <button class="btn block mt-lg" type="submit">Criar conta</button>
    </form>
    <p class="muted small mt" style="text-align:center">Já tem conta? <a href="/entrar">Entrar</a></p>
  </div>
</div>
