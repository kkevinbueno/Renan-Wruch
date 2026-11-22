<div class="page-head"><div><h1>Meu perfil</h1><p><?= e($user['email']) ?> · conta criada em <?= e(fmt_date(to_local($user['created_at'])->format('Y-m-d'))) ?></p></div></div>
<div class="grid grid-2">
  <div class="stack">
    <form class="card" method="post" action="/app/perfil">
      <?= csrf_field() ?>
      <h3>Dados pessoais</h3>
      <div class="field mt"><label for="pn">Nome completo</label><input id="pn" name="name" value="<?= e($user['name']) ?>" required></div>
      <div class="form-grid cols-2 mt">
        <div class="field"><label for="pp">Telefone</label><input id="pp" type="tel" name="phone" value="<?= e($profile['phone']) ?>"></div>
        <div class="field"><label for="pb">Nascimento</label><input id="pb" type="date" name="birth_date" value="<?= e($profile['birth_date']) ?>"></div>
      </div>
      <button class="btn mt" type="submit">Salvar</button>
    </form>

    <form class="card" method="post" action="/app/perfil/avatar" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <h3>Foto de perfil</h3>
      <div class="row mt"><?= avatar($user, 'lg') ?><input type="file" name="avatar" accept="image/*" required class="grow"></div>
      <button class="btn secondary mt" type="submit">Atualizar foto</button>
    </form>

    <form class="card" method="post" action="/app/perfil/senha">
      <?= csrf_field() ?>
      <h3><?= $user['password_hash'] ? 'Alterar senha' : 'Criar senha' ?></h3>
      <?php if (!$user['password_hash']): ?><p class="small muted mt">Você entra com Google/Apple. Crie uma senha para também poder entrar com e-mail.</p><?php endif; ?>
      <?php if ($user['password_hash']): ?><div class="field mt"><label for="cp">Senha atual</label><input id="cp" type="password" name="current_password" autocomplete="current-password" required></div><?php endif; ?>
      <div class="field mt"><label for="np">Nova senha</label><input id="np" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
      <div class="field"><label for="np2">Confirmar nova senha</label><input id="np2" type="password" name="password_confirm" minlength="8" autocomplete="new-password" required></div>
      <button class="btn secondary mt" type="submit">Salvar senha</button>
    </form>
  </div>

  <div class="stack">
    <div class="card">
      <h3>Conta</h3>
      <dl class="kv mt">
        <dt>Situação</dt><dd><?= badge_status($user['status']) ?></dd>
        <dt>Ciclo</dt><dd><?= (int) $profile['cycle'] ?></dd>
        <dt>Avaliação inicial</dt><dd><?= $profile['onboarding_completed_at'] ? 'Enviada em ' . e(fmt_datetime($profile['onboarding_completed_at'])) : 'Pendente' ?></dd>
        <dt>Assinatura</dt><dd><span class="badge <?= e($standing['tone']) ?>"><?= e($standing['label']) ?></span> <a class="small" href="/app/assinatura">ver</a></dd>
      </dl>
    </div>

    <form class="card" method="post" action="/app/perfil/notificacoes">
      <?= csrf_field() ?>
      <h3>Preferências de e-mail</h3>
      <p class="small muted mt">Avisos essenciais (conta, segurança, aprovação e pagamentos) são sempre enviados.</p>
      <div class="mt">
        <?php foreach ($prefs as $k => $p): ?>
          <label class="check"><input type="checkbox" name="prefs[<?= e($k) ?>]" value="1"<?= checked($p['enabled']) ?>> <span><?= e($p['label']) ?></span></label>
        <?php endforeach; ?>
      </div>
      <button class="btn secondary mt" type="submit">Salvar preferências</button>
    </form>

    <div class="card">
      <h3>Privacidade e seus dados (LGPD)</h3>
      <p class="small muted mt">Você pode baixar uma cópia dos seus dados ou registrar um pedido de acesso, correção ou exclusão.</p>
      <a class="btn secondary sm mt" href="/app/perfil/exportar"><?= icon('download', 'icon-sm') ?> Baixar meus dados (JSON)</a>
      <form method="post" action="/app/perfil/lgpd" class="mt">
        <?= csrf_field() ?>
        <div class="field"><label for="lt">Tipo de solicitação</label><select id="lt" name="type"><option value="access">Acesso aos dados</option><option value="correction">Correção de dados</option><option value="deletion">Exclusão de dados</option></select></div>
        <div class="field"><label for="ld">Detalhes</label><textarea id="ld" name="details" rows="2"></textarea></div>
        <button class="btn secondary sm mt" type="submit">Registrar solicitação</button>
      </form>
      <?php if ($requests): ?>
        <div class="list mt">
          <?php foreach ($requests as $r): ?>
            <div class="list-item small"><div class="grow"><?= e(['access' => 'Acesso', 'correction' => 'Correção', 'deletion' => 'Exclusão'][$r['request_type']]) ?> · <?= e(fmt_datetime($r['created_at'])) ?><?php if ($r['response']): ?><div class="muted pre"><?= e($r['response']) ?></div><?php endif; ?></div><span class="badge <?= $r['status'] === 'done' ? 'ok' : 'warn' ?>"><?= $r['status'] === 'done' ? 'Atendida' : 'Em aberto' ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
