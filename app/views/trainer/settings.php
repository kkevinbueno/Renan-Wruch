<?php
$dev = Auth::isDeveloper();
$groups = ['perfil' => ['Meu perfil', ['conta' => 'Conta', 'consultoria' => 'Nomes', 'notificacoes' => 'E-mails']], 'publica' => ['Página pública', ['pagina' => 'Página']], 'regras' => ['Regras', ['operacao' => 'Acompanhamento', 'modalidades' => 'Modalidades', 'lgpd' => 'Privacidade']]];
if ($dev) $groups['tecnico'] = ['Técnico', ['registros' => 'Registros']];
$current = 'perfil';
foreach ($groups as $g => [, $subs]) if (isset($subs[$tab])) $current = $g;
?>
<a class="btn ghost sm" href="/painel/mais"><?= icon('chevron-left', 'icon-sm') ?> Mais</a>
<div class="page-head mt"><div><h1>Configurações</h1></div></div>
<nav class="tabs"><?php foreach ($groups as $g => [$label, $subs]): ?><a href="?aba=<?= e(array_key_first($subs)) ?>" class="<?= $current === $g ? 'active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<?php if (count($groups[$current][1]) > 1): ?>
  <div class="seg mb"><?php foreach ($groups[$current][1] as $k => $l): ?><a href="?aba=<?= e($k) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($l) ?></a><?php endforeach; ?></div>
<?php endif; ?>

<?php if (in_array($tab, ['consultoria', 'pagina', 'notificacoes', 'operacao'], true) || $tab === 'lgpd'): ?>
<form method="post" action="/painel/configuracoes" class="<?= $tab === 'lgpd' ? '' : 'card' ?>" id="settings-form" <?= $tab === 'lgpd' ? 'hidden' : '' ?>>
  <?= csrf_field() ?>
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <?php if ($tab === 'consultoria'): ?>
    <div class="form-grid cols-2">
      <div class="field"><label for="bn">Nome da plataforma</label><input id="bn" name="brand_name" value="<?= e($s['brand_name']) ?>"></div>
      <div class="field"><label for="tn">Nome do treinador</label><input id="tn" name="trainer_name" value="<?= e($s['trainer_name']) ?>"></div>
      <?php if ($dev): ?><div class="field"><label for="ac">Cor de destaque (e-mails)</label><div class="row nowrap"><input id="ac" type="color" name="accent_color" value="<?= e($s['accent_color']) ?>" style="width:56px;padding:4px;min-height:44px"><span class="small muted">O site usa a paleta preto, azul-pó e branco.</span></div></div><?php else: ?><input type="hidden" name="accent_color" value="<?= e($s['accent_color']) ?>"><?php endif; ?>
    </div>
  <?php elseif ($tab === 'pagina'): ?>
    <p class="small muted mb">Use apenas informações verdadeiras e verificadas. Depoimentos, certificações e resultados não são exibidos automaticamente.</p>
    <div class="field"><label for="ph">Título principal</label><input id="ph" name="public_headline" value="<?= e($s['public_headline']) ?>"></div>
    <div class="field"><label for="pi">Texto de apresentação</label><textarea id="pi" name="public_intro" rows="3"><?= e($s['public_intro']) ?></textarea></div>
    <div class="field"><label for="pa">Sobre o Renan</label><textarea id="pa" name="public_about" rows="6" placeholder="Formação, experiência e forma de trabalho (deixe em branco para ocultar a seção)"><?= e($s['public_about']) ?></textarea></div>
    <div class="form-grid cols-2 mt">
      <div class="field"><label for="cw">WhatsApp (somente números com DDI)</label><input id="cw" name="contact_whatsapp" value="<?= e($s['contact_whatsapp']) ?>"></div>
      <div class="field"><label for="cwl">WhatsApp (como exibir)</label><input id="cwl" name="contact_whatsapp_label" value="<?= e($s['contact_whatsapp_label']) ?>"></div>
      <div class="field"><label for="ci">Instagram (URL)</label><input id="ci" name="contact_instagram" value="<?= e($s['contact_instagram']) ?>"></div>
      <div class="field"><label for="ce">E-mail de contato</label><input id="ce" type="email" name="contact_email" value="<?= e($s['contact_email']) ?>"></div>
    </div>
  <?php elseif ($tab === 'notificacoes'): ?>
    <p class="section-title">E-mails para mim</p>
    <label class="check"><input type="checkbox" name="notify_trainer_new_signup" value="1"<?= checked($s['notify_trainer_new_signup'] === '1') ?>> <span>Novo cadastro aguardando aprovação</span></label>
    <label class="check"><input type="checkbox" name="notify_trainer_assessment" value="1"<?= checked($s['notify_trainer_assessment'] === '1') ?>> <span>Avaliação física recebida</span></label>
    <label class="check"><input type="checkbox" name="notify_trainer_requests" value="1"<?= checked($s['notify_trainer_requests'] === '1') ?>> <span>Pedido de mudança de dia</span></label>
    <label class="check"><input type="checkbox" name="notify_trainer_daily_digest" value="1"<?= checked($s['notify_trainer_daily_digest'] === '1') ?>> <span>Resumo diário de pendências</span></label>
    <div class="form-grid cols-2 mt">
      <div class="field"><label for="dh">Horário do resumo diário</label><select id="dh" name="trainer_digest_hour"><?php for ($h = 5; $h <= 22; $h++): ?><option value="<?= $h ?>"<?= selected($h, $s['trainer_digest_hour']) ?>><?= sprintf('%02d:00', $h) ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="cd">Agrupar avisos de mensagens a cada (min)</label><input id="cd" type="number" name="chat_digest_minutes" min="5" max="240" value="<?= (int) $s['chat_digest_minutes'] ?>"></div>
    </div>
    <p class="hint mt">Avisos ao aluno (treino publicado, lembretes, calendário) podem ser desligados por cada aluno no próprio perfil. Avisos essenciais sempre são enviados.</p>
  <?php elseif ($tab === 'operacao'): ?>
    <p class="section-title">Acesso por inadimplência</p>
    <div class="form-grid cols-2">
      <div class="field"><label for="bp">Política</label><select id="bp" name="billing_policy"><option value="off"<?= selected('off', $s['billing_policy']) ?>>Não restringir o acesso</option><option value="restrict"<?= selected('restrict', $s['billing_policy']) ?>>Restringir treinos sem assinatura em dia</option></select></div>
      <div class="field"><label for="bg">Carência (dias)</label><input id="bg" type="number" name="billing_grace_days" min="0" max="60" value="<?= (int) $s['billing_grace_days'] ?>"></div>
    </div>
    <p class="hint">Com a restrição ativa, o aluno inadimplente mantém acesso a mensagens, perfil e assinatura. Nenhum dado é apagado. Você pode liberar alunos individualmente.</p>
    <p class="section-title mt-lg">Padrões para novos alunos</p>
    <div class="form-grid cols-3">
      <div class="field"><label for="dse">Autoavaliação a cada (dias)</label><input id="dse" type="number" name="default_self_eval_days" min="1" max="60" value="<?= (int) $s['default_self_eval_days'] ?>"></div>
      <div class="field"><label for="dad">Avaliação física a cada (dias)</label><input id="dad" type="number" name="default_assessment_days" min="7" max="365" value="<?= (int) $s['default_assessment_days'] ?>"></div>
      <div class="field"><label for="wsd">Treino “precisa de atualização” após (dias)</label><input id="wsd" type="number" name="workout_stale_days" min="7" max="365" value="<?= (int) $s['workout_stale_days'] ?>"></div>
    </div>
    <?php if (!$dev): ?>
      <?php foreach (['upload_image_mb', 'upload_video_mb', 'upload_audio_mb', 'upload_document_mb'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= (int) $s[$k] ?>"><?php endforeach; ?>
    <?php else: ?>
    <p class="section-title mt-lg">Limites de arquivos (MB) · técnico</p>
    <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
      <div class="field"><label for="ui">Imagens</label><input id="ui" type="number" name="upload_image_mb" value="<?= (int) $s['upload_image_mb'] ?>"></div>
      <div class="field"><label for="uv">Vídeos</label><input id="uv" type="number" name="upload_video_mb" value="<?= (int) $s['upload_video_mb'] ?>"></div>
      <div class="field"><label for="ua">Áudios</label><input id="ua" type="number" name="upload_audio_mb" value="<?= (int) $s['upload_audio_mb'] ?>"></div>
      <div class="field"><label for="ud">Documentos</label><input id="ud" type="number" name="upload_document_mb" value="<?= (int) $s['upload_document_mb'] ?>"></div>
    </div>
    <p class="hint">O servidor também limita uploads (upload_max_filesize atual: <?= e(ini_get('upload_max_filesize')) ?>, post_max_size: <?= e(ini_get('post_max_size')) ?>).</p>
    <?php endif; ?>
  <?php elseif ($tab === 'lgpd'): ?>
    <div class="field"><label for="rm">Excluir fotos de alunos encerrados após (meses)</label><input id="rm" type="number" name="retention_months_after_end" min="0" max="120" value="<?= (int) $s['retention_months_after_end'] ?>"></div>
  <?php endif; ?>
  <button class="btn mt-lg" type="submit">Salvar</button>
</form>
<?php endif; ?>

<?php if ($tab === 'pagina'): ?>
  <form class="card mt" method="post" action="/painel/configuracoes/foto" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <h3>Foto da página pública</h3>
    <div class="row mt"><?php if ((int) $s['public_photo_file_id']): ?><img src="<?= e(Files::publicUrl((int) $s['public_photo_file_id'])) ?>" alt="" style="width:72px;height:90px;object-fit:cover;border-radius:10px"><?php endif; ?><input type="file" name="photo" accept="image/*" required class="grow"></div>
    <button class="btn secondary sm mt" type="submit">Enviar foto</button>
  </form>
<?php elseif ($tab === 'operacao' && $dev): ?>
  <div class="card mt">
    <h3>Tarefas agendadas e e-mail · técnico</h3>
    <dl class="kv mt">
      <dt>Última execução do cron</dt><dd><?= $cronLast ? e(fmt_datetime($cronLast)) : '<span class="badge danger">nunca</span>' ?></dd>
      <dt>Envio de e-mail</dt><dd><?= e($mailTransport) ?> · remetente <?= e(config('mail.from')) ?></dd>
      <dt>Google / Apple</dt><dd><?= OAuth::googleEnabled() ? 'Google ativo' : 'Google não configurado' ?> · <?= OAuth::appleEnabled() ? 'Apple ativo' : 'Apple não configurado' ?></dd>
      <dt>Mercado Pago</dt><dd><?= MercadoPago::configured() ? 'Configurado' : 'Não configurado' ?></dd>
      <dt>Versão</dt><dd><?= e(APP_VERSION) ?></dd>
    </dl>
    <form method="post" action="/painel/configuracoes/email-teste" class="mt"><?= csrf_field() ?><button class="btn secondary sm" type="submit"><?= icon('mail', 'icon-sm') ?> Enviar e-mail de teste para mim</button></form>
  </div>
<?php elseif ($tab === 'modalidades'): ?>
  <form class="card" method="post" action="/painel/configuracoes/modalidades">
    <?= csrf_field() ?>
    <p class="small muted mb">Defina os campos de prescrição padrão de cada esporte. Cada exercício pode sobrescrever os campos.</p>
    <?php foreach ($modalities as $m): ?>
      <div class="list-item" style="flex-direction:column;align-items:stretch;gap:8px">
        <div class="row">
          <input name="name[<?= (int) $m['id'] ?>]" value="<?= e($m['name']) ?>" class="grow" aria-label="Nome da modalidade">
          <input type="number" name="sort[<?= (int) $m['id'] ?>]" value="<?= (int) $m['sort_order'] ?>" style="width:80px" aria-label="Ordem">
          <label class="check"><input type="checkbox" name="active[<?= (int) $m['id'] ?>]" value="1"<?= checked((bool) $m['active']) ?>> <span>Ativa</span></label>
        </div>
        <div class="pills"><?php foreach (Modalities::FIELDS as $k => $meta): ?><label class="pill-check"><input type="checkbox" name="fields[<?= (int) $m['id'] ?>][]" value="<?= e($k) ?>"<?= checked(in_array($k, $m['fields'], true)) ?>><span><?= e($meta['label']) ?></span></label><?php endforeach; ?></div>
      </div>
    <?php endforeach; ?>
    <button class="btn mt" type="submit">Salvar modalidades</button>
  </form>
<?php elseif ($tab === 'lgpd'): ?>
  <div class="card mb">
    <h3>Retenção de dados</h3>
    <p class="small muted mt">Fotografias de alunos encerrados ou recusados podem ser excluídas automaticamente. Use 0 para desativar. Recomenda-se definir o prazo com orientação jurídica.</p>
    <button class="btn secondary sm mt" type="button" data-show-form="settings-form">Alterar prazo (atual: <?= (int) $s['retention_months_after_end'] ?: 'desativado' ?><?= (int) $s['retention_months_after_end'] ? ' meses' : '' ?>)</button>
  </div>
  <p class="small mb"><a class="link" href="?aba=registros"><?= icon('shield', 'icon-sm') ?> Ver registro de ações (inclui ações de desenvolvedores)</a></p>
  <div class="card">
    <h3>Solicitações dos titulares</h3>
    <?php if (!$requests): ?><p class="small muted mt">Nenhuma solicitação.</p><?php endif; ?>
    <div class="list mt">
      <?php foreach ($requests as $r): ?>
        <div class="list-item" style="align-items:flex-start">
          <div class="grow">
            <div class="list-title"><?= e($r['name']) ?> · <?= e(['access' => 'Acesso', 'correction' => 'Correção', 'deletion' => 'Exclusão'][$r['request_type']]) ?></div>
            <div class="list-sub"><?= e($r['email']) ?> · <?= e(fmt_datetime($r['created_at'])) ?><?= $r['status'] === 'open' ? ' · prazo: ' . e(fmt_date_short(date_add_days(to_local($r['created_at'])->format('Y-m-d'), 15))) : '' ?></div>
            <?php if ($r['details']): ?><p class="small pre mt"><?= e($r['details']) ?></p><?php endif; ?>
            <?php if ($r['status'] === 'open'): ?>
              <form method="post" action="/painel/configuracoes/lgpd/<?= (int) $r['id'] ?>" class="row mt">
                <?= csrf_field() ?>
                <input class="grow" name="response" placeholder="Registro do atendimento (opcional)">
                <button class="btn sm" type="submit">Marcar como atendida</button>
              </form>
              <p class="hint mt"><?= $r['request_type'] === 'deletion' ? 'Para excluir, abra o aluno → Dados e anamnese → Excluir dados definitivamente.' : 'O aluno pode baixar seus dados em Meu perfil → Baixar meus dados.' ?> <a href="/painel/alunos/<?= (int) $r['user_id'] ?>?aba=dados">Abrir aluno</a></p>
            <?php elseif ($r['response']): ?><p class="small muted mt pre"><?= e($r['response']) ?></p><?php endif; ?>
          </div>
          <span class="badge <?= $r['status'] === 'open' ? 'warn' : 'ok' ?>"><?= $r['status'] === 'open' ? 'Em aberto' : 'Atendida' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php elseif ($tab === 'conta'): ?>
  <div class="grid grid-2">
    <form class="card" method="post" action="/painel/conta/avatar" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <h3>Foto de perfil</h3>
      <div class="row mt"><?= avatar($user, 'lg') ?><input type="file" name="avatar" accept="image/*" required class="grow"></div>
      <button class="btn secondary sm mt" type="submit">Atualizar</button>
    </form>
    <form class="card" method="post" action="/painel/conta/senha">
      <?= csrf_field() ?>
      <h3>Senha</h3>
      <?php if ($user['password_hash']): ?><div class="field mt"><label for="cp">Senha atual</label><input id="cp" type="password" name="current_password" autocomplete="current-password" required></div><?php endif; ?>
      <div class="field mt"><label for="np">Nova senha</label><input id="np" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
      <div class="field"><label for="np2">Confirmar</label><input id="np2" type="password" name="password_confirm" minlength="8" autocomplete="new-password" required></div>
      <button class="btn secondary sm mt" type="submit">Alterar senha</button>
    </form>
  </div>
<?php elseif ($tab === 'registros'): ?>
  <div class="card">
    <h3>Registro de ações (auditoria)</h3>
    <p class="small muted">Últimas 200 ações. Ações feitas por desenvolvedores na interface do Renan aparecem com a identidade real.</p>
    <div class="table-wrap mt"><table class="table stack-mobile">
      <thead><tr><th>Quando</th><th>Quem</th><th>Interface</th><th>Ação</th><th>Detalhes</th></tr></thead>
      <tbody><?php foreach ($logs as $l): ?>
        <tr><td data-label="Quando" class="small"><?= e(fmt_datetime($l['created_at'])) ?></td><td data-label="Quem" class="small"><?= $l['name'] ? e($l['name']) . ($l['role'] === 'developer' ? ' <span class="badge outline">dev</span>' : '') : '<span class="muted">sistema</span>' ?></td><td data-label="Interface" class="small"><?= e($l['acting_as'] ?? '—') ?></td><td data-label="Ação" class="small"><code><?= e($l['action']) ?></code></td><td data-label="Detalhes" class="xs muted"><?= e(trim(($l['entity'] ? $l['entity'] . '#' . $l['entity_id'] . ' ' : '') . ($l['meta'] ?? ''))) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
  <div class="card mt">
    <h3>E-mails recentes</h3>
    <div class="table-wrap mt"><table class="table stack-mobile">
      <thead><tr><th>Criado</th><th>Para</th><th>Modelo</th><th>Situação</th><th>Erro</th></tr></thead>
      <tbody><?php foreach ($emails as $m): ?>
        <tr><td data-label="Criado" class="small"><?= e(fmt_datetime($m['created_at'])) ?></td><td data-label="Para" class="small"><?= e($m['to_email']) ?></td><td data-label="Modelo" class="small"><?= e($m['template']) ?></td><td data-label="Situação"><span class="badge <?= $m['status'] === 'sent' ? 'ok' : ($m['status'] === 'failed' ? 'danger' : '') ?>"><?= e($m['status']) ?><?= $m['attempts'] > 1 ? ' · ' . (int) $m['attempts'] . 'x' : '' ?></span></td><td data-label="Erro" class="xs muted"><?= e(mb_strimwidth((string) $m['last_error'], 0, 120, '…')) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
<?php endif; ?>
