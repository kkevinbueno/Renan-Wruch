<div class="auth-wrap">
  <div class="auth-card card" style="text-align:center">
    <p class="muted small">Erro <?= (int) $code ?></p>
    <h1 class="mt"><?= e($message ?: ($code === 404 ? 'Página não encontrada' : ($code === 403 ? 'Acesso não permitido' : 'Não foi possível concluir'))) ?></h1>
    <p class="muted mt">Se acredita que isto é um engano, volte e tente novamente.</p>
    <div class="btn-row mt-lg" style="justify-content:center">
      <a class="btn" href="<?= e(Auth::user() ? Auth::home() : '/') ?>">Ir para o início</a>
    </div>
  </div>
</div>
