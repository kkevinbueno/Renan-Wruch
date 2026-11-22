<div class="auth-wrap">
  <div class="auth-card">
    <h1>Antes de continuar</h1>
    <p class="muted">Para usar a plataforma precisamos do seu consentimento.</p>
    <form method="post" action="/consentimento" class="card mt-lg">
      <?= csrf_field() ?>
      <label class="check"><input type="checkbox" name="consent_terms" value="1" required> <span>Li e aceito os <a href="/termos" target="_blank">Termos de Uso</a> e a <a href="/privacidade" target="_blank">Política de Privacidade</a>.</span></label>
      <label class="check"><input type="checkbox" name="consent_health" value="1" required> <span>Consinto com o tratamento dos meus dados de saúde, respostas da anamnese e fotografias corporais exclusivamente para o planejamento e acompanhamento dos treinos.</span></label>
      <button class="btn block mt-lg" type="submit">Continuar</button>
    </form>
    <form method="post" action="/sair" class="mt"><?= csrf_field() ?><button class="btn ghost block" type="submit">Sair</button></form>
  </div>
</div>
