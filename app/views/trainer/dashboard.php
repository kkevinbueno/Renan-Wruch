<?php
$u = Auth::user();
$hour = (int) local_now()->format('H');
$greet = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
$items = [];
foreach ($pendingList as $p) {
    $items[] = ['blue', 'user', 'Cadastro novo', '<b>' . e($p['name']) . '</b> quer começar · ' . e(fmt_ago($p['created_at'])),
        '<form method="post" action="/painel/alunos/' . (int) $p['id'] . '/recusar" data-inbox-form data-confirm="Recusar o cadastro de ' . e($p['name']) . '?">' . csrf_field() . '<button class="btn secondary sm" type="submit">Recusar</button></form>'
        . '<form method="post" action="/painel/alunos/' . (int) $p['id'] . '/aprovar" data-inbox-form>' . csrf_field() . '<button class="btn pw sm" type="submit">Aprovar</button></form>'];
}
foreach ($assessmentList as $a) {
    $items[] = ['blue', 'camera', 'Avaliação física recebida', '<b>' . e($a['name']) . '</b> enviou fotos · ' . e(fmt_ago($a['submitted_at'])),
        '<a class="btn sm" href="/painel/avaliacoes/' . (int) $a['id'] . '">Ver fotos</a>'];
}
foreach ($requestList as $r) {
    $items[] = ['ok', 'repeat', 'Pedido de troca de dia', '<b>' . e($r['student_name']) . '</b> · ' . e(Workouts::title($r)) . ': ' . e(fmt_date_short($r['original_date'])) . ' → ' . e(fmt_date_short($r['requested_date'])),
        '<form method="post" action="/painel/solicitacoes/' . (int) $r['id'] . '" data-inbox-form>' . csrf_field() . '<input type="hidden" name="decision" value="reject"><button class="btn secondary sm" type="submit">Recusar</button></form>'
        . '<form method="post" action="/painel/solicitacoes/' . (int) $r['id'] . '" data-inbox-form>' . csrf_field() . '<input type="hidden" name="decision" value="approve"><button class="btn pw sm" type="submit">Aceitar</button></form>'];
}
foreach (array_slice($needsUpdate, 0, 4) as $r) {
    $items[] = ['warn', 'refresh', 'Treino precisa de atualização', '<b>' . e($r['name']) . '</b> · ' . ($r['last_pub'] ? 'último treino ' . e(fmt_ago($r['last_pub'])) : 'ainda sem treino'),
        '<a class="btn sm" href="/painel/alunos/' . (int) $r['id'] . '?aba=treinos">Criar treino</a>'];
}
foreach ($failedList as $f) {
    $items[] = ['danger', 'card', 'Pagamento recusado', '<b>' . e($f['name']) . '</b> · a cobrança não passou',
        '<a class="btn secondary sm" href="/painel/mensagens/' . (int) $f['student_id'] . '">Mandar mensagem</a>'];
}
if ($c['data_requests']) {
    $items[] = ['', 'shield', 'Privacidade (LGPD)', plural($c['data_requests'], 'solicitação aguardando resposta', 'solicitações aguardando resposta'), '<a class="btn secondary sm" href="/painel/configuracoes?aba=lgpd">Responder</a>'];
}
if ($failedEmails && Auth::isDeveloper()) {
    $items[] = ['danger', 'mail', 'E-mails com falha', plural($failedEmails, 'e-mail não enviado', 'e-mails não enviados') . ' nos últimos 7 dias', '<a class="btn secondary sm" href="/painel/configuracoes?aba=registros">Ver</a>'];
}
$extra = max(0, $needsUpdateCount - 4);
?>
<div class="page-head">
  <div><p class="muted strong"><?= e(fmt_date(today(), true)) ?></p><h1 style="margin-top:4px"><?= e($greet) ?>, <?= e(first_name($u['name'])) ?></h1></div>
  <a class="btn" href="/painel/alunos"><?= icon('plus', 'icon-sm') ?> Novo treino</a>
</div>

<?php if (Auth::isDeveloper()): ?>
  <div class="callout mb"><?= icon('shield') ?><p class="small">Você está na visão do Renan com a sua conta de desenvolvedor. As ações ficam registradas com a sua identidade.</p></div>
<?php endif; ?>

<div class="stats enter">
  <a class="stat" href="/painel/alunos?situacao=active"><span class="lbl">alunos ativos</span><span class="num" data-count="<?= (int) $active ?>"><?= (int) $active ?></span></a>
  <a class="stat<?= $c['pending'] ? ' attention' : '' ?>" href="/painel/alunos?situacao=pending"><span class="lbl">aguardando aprovação</span><span class="num" data-count="<?= (int) $c['pending'] ?>"><?= (int) $c['pending'] ?></span></a>
  <a class="stat" href="/painel/calendario"><span class="lbl">treinos feitos na semana</span><span class="num" data-count="<?= (int) $weekSessions['completed'] ?>"><?= (int) $weekSessions['completed'] ?></span></a>
  <a class="stat block" href="/painel/financeiro"><span class="lbl">recebido no mês</span><span class="num" data-count="<?= round($received / 100) ?>" data-prefix="R$ "><?= e(money($received)) ?></span></a>
</div>

<div class="grid grid-main section">
  <div class="card flush" style="align-self:start">
    <div class="card-head" style="padding:18px 18px 6px"><div class="row"><h2>Caixa de entrada</h2><?php if ($items): ?><span class="badge blue"><?= count($items) + $extra ?></span><?php endif; ?></div><span class="xs muted hide-sm">Tudo que precisa de você</span></div>
    <?php if (!$items): ?>
      <div class="empty"><span style="font-size:2.4rem">🎉</span><h3 class="mt">Tudo em dia!</h3><p class="small">Nenhuma pendência no momento.</p></div>
    <?php endif; ?>
    <div class="enter">
      <?php foreach ($items as [$tone, $ico, $kind, $desc, $actions]): ?>
        <div class="inbox-item">
          <span class="ico <?= e($tone) ?>"><?= icon($ico) ?></span>
          <div class="grow"><div class="kind" style="color:var(--<?= $tone === 'blue' || $tone === '' ? 'blue' : e($tone) ?>)"><?= e($kind) ?></div><div style="margin-top:2px"><?= $desc ?></div></div>
          <div class="actions"><?= $actions ?></div>
        </div>
      <?php endforeach; ?>
      <?php if ($extra): ?><div class="inbox-item"><span class="grow small muted">+ <?= plural($extra, 'aluno', 'alunos') ?> com treino para atualizar</span><a class="link small" href="/painel/alunos">Ver alunos</a></div><?php endif; ?>
    </div>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card-head"><h3>Hoje na agenda</h3><a class="link small" href="/painel/calendario">Agenda</a></div>
      <?php $agenda = $todayEvents ?: $weekEvents; ?>
      <?php if (!$agenda): ?><p class="muted small">Nada agendado para esta semana.</p><?php elseif (!$todayEvents): ?><p class="xs muted mb">Nada hoje — próximos da semana:</p><?php endif; ?>
      <div class="list day-list">
        <?php foreach ($agenda as $ev): ?>
          <a class="list-item" href="/painel/calendario?<?= e(http_build_query(['aluno' => $ev['student_id'], 'data' => $ev['event_date'], 'dia' => $ev['event_date']])) ?>" style="min-height:0;padding:10px 0">
            <span class="small strong" style="width:48px;flex:none"><?= $ev['start_time'] ? e(substr($ev['start_time'], 0, 5)) : e((new DateTimeImmutable($ev['event_date']))->format('d/m')) ?></span>
            <div class="grow"><div class="list-title small"><?= e($ev['name']) ?></div><div class="list-sub"><?= e($ev['title']) ?></div></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><h3>Alunos</h3><a class="link small" href="/painel/alunos">Ver todos</a></div>
      <?php if (!$students): ?><p class="muted small">Nenhum aluno ativo ainda.</p><?php endif; ?>
      <div class="list">
        <?php foreach ($students as $s):
          $days = $s['last_act'] ? (int) floor((Clock::time() - strtotime($s['last_act'] . ' UTC')) / 86400) : null;
          $st = $days === null ? 'danger' : ($days <= 3 ? 'ok' : ($days <= 8 ? 'warn' : 'danger'));
        ?>
          <a class="list-item" href="/painel/alunos/<?= (int) $s['id'] ?>" style="min-height:0;padding:10px 0">
            <span class="av-wrap <?= e(mod_tone((int) $s['modality_id'])) ?>"><?= avatar($s, 'sm') ?><i class="status <?= $st ?>"></i></span>
            <div class="grow"><div class="list-title small"><?= e($s['name']) ?></div></div>
            <span class="xs muted"><?= $days === null ? 'sem treinos' : ($days === 0 ? 'treinou hoje' : ($days === 1 ? 'ontem' : 'há ' . $days . ' dias')) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php if (Auth::isDeveloper() && (!$cronLast || strtotime($cronLast . ' UTC') < Clock::time() - 3600)): ?>
  <div class="callout warn section"><?= icon('alert') ?><p class="small">As tarefas agendadas <?= $cronLast ? 'não rodam desde ' . e(fmt_datetime($cronLast)) : 'ainda não foram configuradas' ?>. Veja <a href="/painel/configuracoes?aba=registros">Configurações → Técnico</a>.</p></div>
<?php endif; ?>
