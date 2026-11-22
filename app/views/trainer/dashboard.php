<?php $u = Auth::user(); ?>
<div class="page-head">
  <div><h1>Olá, <?= e(first_name($u['name'])) ?></h1><p><?= e(fmt_date(today(), true)) ?></p></div>
  <a class="btn secondary sm" href="/painel/alunos"><?= icon('users', 'icon-sm') ?> Meus alunos</a>
</div>

<?php if (Auth::isDeveloper()): ?>
  <div class="callout mb"><?= icon('shield') ?><p class="small">Você está operando a interface do Renan com sua conta de desenvolvedor. Todas as ações ficam registradas com a sua identidade.</p></div>
<?php endif; ?>

<div class="stats">
  <a class="stat" href="/painel/alunos?situacao=active"><span class="num"><?= (int) $active ?></span><span class="lbl">Alunos ativos</span></a>
  <a class="stat<?= $c['pending'] ? ' attention' : '' ?>" href="/painel/alunos?situacao=pending"><span class="num"><?= (int) $c['pending'] ?></span><span class="lbl">Aguardando aprovação</span></a>
  <a class="stat<?= $c['assessments'] ? ' attention' : '' ?>" href="/painel/avaliacoes"><span class="num"><?= (int) $c['assessments'] ?></span><span class="lbl">Avaliações recebidas</span></a>
  <a class="stat<?= $c['unread'] ? ' attention' : '' ?>" href="/painel/mensagens"><span class="num"><?= (int) $c['unread'] ?></span><span class="lbl">Mensagens não lidas</span></a>
</div>

<div class="grid grid-2 section">
  <div class="card">
    <div class="card-head"><h3>Pendências</h3></div>
    <?php
      $pend = array_filter([
        $c['pending'] ? ['/painel/alunos?situacao=pending', 'users', plural($c['pending'], 'cadastro para aprovar', 'cadastros para aprovar')] : null,
        $c['onboarding'] ? ['/painel/alunos?situacao=active', 'clipboard', plural($c['onboarding'], 'avaliação inicial sem treino', 'avaliações iniciais sem treino')] : null,
        $c['assessments'] ? ['/painel/avaliacoes', 'camera', plural($c['assessments'], 'avaliação física para revisar', 'avaliações físicas para revisar')] : null,
        $c['requests'] ? ['/painel/solicitacoes', 'repeat', plural($c['requests'], 'pedido de mudança de dia', 'pedidos de mudança de dia')] : null,
        $c['data_requests'] ? ['/painel/configuracoes?aba=lgpd', 'shield', plural($c['data_requests'], 'solicitação LGPD', 'solicitações LGPD')] : null,
        $c['failed_payments'] ? ['/painel/financeiro?situacao=payment_failed', 'card', plural($c['failed_payments'], 'pagamento recusado', 'pagamentos recusados')] : null,
        $failedEmails ? ['/painel/configuracoes?aba=registros', 'mail', plural($failedEmails, 'e-mail com falha de envio', 'e-mails com falha de envio')] : null,
      ]);
    ?>
    <?php if (!$pend): ?><p class="muted small">Nenhuma pendência no momento.</p><?php endif; ?>
    <div class="list">
      <?php foreach ($pend as [$href, $ico, $label]): ?>
        <a class="list-item" href="<?= e($href) ?>"><?= icon($ico) ?><span class="grow list-title"><?= e($label) ?></span><?= icon('chevron-right', 'icon-sm') ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Treinos que precisam de atualização</h3><span class="badge"><?= (int) $needsUpdateCount ?></span></div>
    <?php if (!$needsUpdate): ?><p class="muted small">Todos os alunos ativos têm treinos publicados nos últimos <?= (int) $staleDays ?> dias.</p><?php endif; ?>
    <div class="list">
      <?php foreach ($needsUpdate as $r): ?>
        <a class="list-item" href="/painel/alunos/<?= (int) $r['id'] ?>?aba=treinos">
          <div class="grow"><div class="list-title"><?= e($r['name']) ?></div><div class="list-sub"><?= $r['last_pub'] ? 'Último treino publicado ' . e(fmt_ago($r['last_pub'])) : 'Sem treino publicado' ?></div></div>
          <?= icon('chevron-right', 'icon-sm') ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Esta semana</h3><a class="small" href="/painel/calendario">Calendário</a></div>
    <p class="small muted"><?= plural($weekSessions['completed'], 'treino realizado', 'treinos realizados') ?> · <?= plural($weekSessions['missed'], 'adiado/não realizado', 'adiados/não realizados') ?></p>
    <div class="list mt">
      <?php if (!$weekEvents): ?><p class="muted small">Nenhuma atividade agendada até domingo.</p><?php endif; ?>
      <?php foreach ($weekEvents as $ev): ?>
        <a class="list-item" href="/painel/calendario?<?= e(http_build_query(['aluno' => $ev['student_id'], 'data' => $ev['event_date'], 'dia' => $ev['event_date']])) ?>">
          <div style="width:52px" class="small"><b><?= e((new DateTimeImmutable($ev['event_date']))->format('d/m')) ?></b><div class="xs muted"><?= e($ev['start_time'] ?? '') ?></div></div>
          <div class="grow"><div class="list-title"><?= e($ev['title']) ?></div><div class="list-sub"><?= e($ev['name']) ?> · <?= e(Schedule::typeLabel($ev['type'])) ?></div></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Recentes</h3></div>
    <?php if (!$pendingList && !$assessmentList && !$unreadList): ?><p class="muted small">Sem novidades.</p><?php endif; ?>
    <div class="list">
      <?php foreach ($pendingList as $p): ?>
        <a class="list-item" href="/painel/alunos/<?= (int) $p['id'] ?>"><span class="dot warn"></span><div class="grow"><div class="list-title"><?= e($p['name']) ?></div><div class="list-sub">Novo cadastro · <?= e(fmt_ago($p['created_at'])) ?></div></div></a>
      <?php endforeach; ?>
      <?php foreach ($assessmentList as $a): ?>
        <a class="list-item" href="/painel/avaliacoes/<?= (int) $a['id'] ?>"><span class="dot accent"></span><div class="grow"><div class="list-title"><?= e($a['name']) ?></div><div class="list-sub">Avaliação física · <?= e(fmt_ago($a['submitted_at'])) ?></div></div></a>
      <?php endforeach; ?>
      <?php foreach ($unreadList as $m): ?>
        <a class="list-item" href="/painel/mensagens/<?= (int) $m['id'] ?>"><span class="dot dark"></span><div class="grow"><div class="list-title"><?= e($m['name']) ?></div><div class="list-sub truncate"><?= plural((int) $m['unread'], 'mensagem não lida', 'mensagens não lidas') ?></div></div></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if (!$cronLast || strtotime($cronLast . ' UTC') < Clock::time() - 3600): ?>
  <div class="callout warn section"><?= icon('alert') ?><p class="small">As tarefas agendadas (lembretes, publicações agendadas e fila de e-mails) <?= $cronLast ? 'não rodam desde ' . e(fmt_datetime($cronLast)) : 'ainda não foram configuradas' ?>. Veja <a href="/painel/configuracoes?aba=operacao">Configurações → Operação</a>.</p></div>
<?php endif; ?>
