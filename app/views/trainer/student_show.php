<?php $id = (int) $s['id']; ?>
<?php $renderAdvanced = function () use ($s, $id, $profile, $modalities) { ?>
  <div class="stack">
    <form class="card" method="post" action="/painel/alunos/<?= $id ?>/configuracoes" >
      <?= csrf_field() ?>
      <h3>Configurações individuais</h3>
      <div class="field mt"><label for="cm">Modalidade principal</label><select id="cm" name="modality_id"><option value="">—</option><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $profile['modality_id']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
      <p class="section-title mt-lg">Autoavaliação</p>
      <div class="field"><label for="sed">Frequência (a cada quantos dias)</label><input id="sed" type="number" name="self_eval_every_days" min="1" max="60" value="<?= (int) $profile['self_eval_every_days'] ?>"></div>
      <div class="field"><span class="label">Categorias</span><div class="pills"><?php foreach (Students::SELF_EVAL_CATEGORIES as $k => $l): ?><label class="pill-check"><input type="checkbox" name="categories[]" value="<?= e($k) ?>"<?= checked(in_array($k, $profile['categories'], true)) ?>><span><?= e($l) ?></span></label><?php endforeach; ?></div></div>
      <p class="section-title mt-lg">Avaliação física</p>
      <div class="form-grid cols-2">
        <div class="field"><label for="aed">Intervalo (dias)</label><input id="aed" type="number" name="assessment_every_days" min="7" max="365" value="<?= (int) $profile['assessment_every_days'] ?>"></div>
        <div class="field"><label for="nao">Próxima avaliação</label><input id="nao" type="date" name="next_assessment_on" value="<?= e($profile['next_assessment_on']) ?>"></div>
        <div class="field"><label for="ard">Lembrar com antecedência (dias)</label><input id="ard" type="number" name="assessment_remind_days_before" min="0" max="30" value="<?= (int) $profile['assessment_remind_days_before'] ?>"></div>
        <div class="field"><label for="arc">Quantidade de avisos</label><input id="arc" type="number" name="assessment_reminders_count" min="1" max="5" value="<?= (int) $profile['assessment_reminders_count'] ?>"></div>
      </div>
      <label class="check mt"><input type="checkbox" name="assessment_overdue_alert" value="1"<?= checked((bool) $profile['assessment_overdue_alert']) ?>> <span>Alertas de atraso (aluno e para mim)</span></label>
      <p class="section-title mt-lg">Acesso</p>
      <label class="check"><input type="checkbox" name="access_override" value="allow"<?= checked($profile['access_override'] === 'allow') ?>> <span>Liberar acesso mesmo sem assinatura ativa</span></label>
      <div class="field mt"><label for="tn">Anotações privadas</label><textarea id="tn" name="trainer_notes" rows="4" placeholder="Visíveis apenas na interface do treinador"><?= e($profile['trainer_notes']) ?></textarea></div>
      <button class="btn mt" type="submit">Salvar configurações</button>
    </form>
      <?php if ($s['status'] === 'active'): ?>
        <div class="card">
          <h3>Situação do acompanhamento</h3>
          <div class="btn-row mt">
            <form method="post" action="/painel/alunos/<?= $id ?>/situacao" data-confirm="Pausar o acompanhamento? O aluno mantém acesso apenas a mensagens, perfil e assinatura."><?= csrf_field() ?><input type="hidden" name="status" value="paused"><button class="btn secondary sm">Pausar</button></form>
            <form method="post" action="/painel/alunos/<?= $id ?>/situacao" data-confirm="Encerrar o acompanhamento? O histórico será preservado."><?= csrf_field() ?><input type="hidden" name="status" value="ended"><button class="btn danger sm">Encerrar</button></form>
          </div>
        </div>
      <?php endif; ?>
  </div>
<?php }; ?>
<?php $tone = mod_tone($profile['modality_id'] ?: (DB::val('SELECT modality_id FROM workouts WHERE student_id = ? AND archived_at IS NULL AND is_template = 0 ORDER BY id LIMIT 1', [$id]) ?: null)); ?>
<a class="btn ghost sm" href="/painel/alunos"><?= icon('chevron-left', 'icon-sm') ?> Alunos</a>
<div class="page-head mt">
  <div class="row nowrap" style="gap:16px">
    <span class="av-wrap <?= e($tone) ?>"><?= avatar($s, 'lg') ?></span>
    <div>
      <h1><?= e($s['name']) ?></h1>
      <p class="row small" style="gap:6px;margin-top:8px"><?= badge_status($s['status']) ?> <span class="badge <?= e($standing['tone']) ?>"><?= e($standing['label']) ?></span> <span class="muted"><?= e($s['email']) ?><?= $profile['phone'] ? ' · ' . e($profile['phone']) : '' ?></span></p>
    </div>
  </div>
  <div class="btn-row">
    <a class="btn secondary" href="/painel/mensagens/<?= $id ?>"><?= icon('message', 'icon-sm') ?> Mensagem<?= $unread ? ' (' . $unread . ')' : '' ?></a>
    <?php if ($s['status'] === 'active'): ?><a class="btn" href="/painel/alunos/<?= $id ?>?aba=treinos#novo-treino"><?= icon('plus', 'icon-sm') ?> Novo treino</a><?php endif; ?>
  </div>
</div>

<?php if ($s['status'] === 'pending'): ?>
  <div class="card mb" style="border-color:var(--border-strong)">
    <h3>Cadastro aguardando aprovação</h3>
    <p class="small muted mt">Criado em <?= e(fmt_datetime($s['created_at'])) ?> · e-mail <?= $s['email_verified_at'] ? 'confirmado' : 'ainda não confirmado' ?>.</p>
    <div class="grid grid-2 mt">
      <form method="post" action="/painel/alunos/<?= $id ?>/aprovar"><?= csrf_field() ?><div class="field"><label for="an">Mensagem (opcional)</label><input id="an" name="note"></div><button class="btn accent mt" type="submit"><?= icon('check', 'icon-sm') ?> Aprovar</button></form>
      <form method="post" action="/painel/alunos/<?= $id ?>/recusar" data-confirm="Recusar este cadastro? O usuário será avisado por e-mail."><?= csrf_field() ?><div class="field"><label for="rn">Motivo (enviado ao usuário)</label><input id="rn" name="note"></div><button class="btn danger mt" type="submit">Recusar</button></form>
    </div>
  </div>
<?php elseif ($s['status'] === 'rejected'): ?>
  <div class="callout danger mb"><?= icon('alert') ?><div class="grow"><b>Cadastro recusado</b><?= $s['decision_note'] ? '<p class="small">' . e($s['decision_note']) . '</p>' : '' ?></div><form method="post" action="/painel/alunos/<?= $id ?>/aprovar"><?= csrf_field() ?><button class="btn sm secondary">Aprovar mesmo assim</button></form></div>
<?php elseif (in_array($s['status'], ['paused', 'ended'], true)): ?>
  <form class="card mb" method="post" action="/painel/alunos/<?= $id ?>/reativar">
    <?= csrf_field() ?>
    <h3>Acompanhamento <?= $s['status'] === 'paused' ? 'pausado' : 'encerrado' ?></h3>
    <p class="small muted mt">Ao reativar, todo o histórico é preservado.</p>
    <label class="check mt"><input type="checkbox" name="new_cycle" value="1"<?= checked($s['status'] === 'ended') ?>> <span>Iniciar novo ciclo de acompanhamento (ciclo <?= (int) $profile['cycle'] + 1 ?>)</span></label>
    <label class="check"><input type="checkbox" name="new_onboarding" value="1"> <span>Pedir nova avaliação inicial (anamnese e fotos) neste ciclo</span></label>
    <button class="btn accent mt" type="submit">Reativar aluno</button>
  </form>
<?php endif; ?>

<?php $mainTabs = ['treinos' => 'Treinos', 'progresso' => 'Progresso', 'dados' => 'Dados']; $groupTab = ['avaliacoes' => 'progresso', 'autoavaliacoes' => 'progresso', 'visao' => 'dados'][$tab] ?? $tab; ?>
<div class="row between">
  <nav class="tabs" aria-label="Seções do aluno" style="margin-bottom:0">
    <?php foreach ($mainTabs as $k => $label): ?><a href="/painel/alunos/<?= $id ?>?aba=<?= e($k) ?>" class="<?= $groupTab === $k ? 'active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
  </nav>
  <a class="link small" href="/painel/alunos/<?= $id ?>?aba=calendario"><?= icon('calendar', 'icon-sm') ?> Agenda do aluno</a>
</div>
<div class="mb"></div>

<?php if ($tab === 'progresso'): ?>
  <div class="stats">
    <div class="stat"><span class="lbl">treinos feitos</span><span class="num" data-count="<?= (int) $total ?>"><?= (int) $total ?></span></div>
    <div class="stat block"><span class="lbl">treinos seguidos 🔥</span><span class="num"><?= (int) $streak ?></span></div>
    <div class="stat"><span class="lbl">próxima avaliação</span><span class="num" style="font-size:1.7rem"><?= $profile['next_assessment_on'] ? e(fmt_date_short($profile['next_assessment_on'])) : '—' ?></span></div>
    <div class="stat"><span class="lbl">último acesso</span><span class="num" style="font-size:1.7rem"><?= e(fmt_ago($s['last_activity_at'])) ?></span></div>
  </div>
  <div class="grid grid-2 mt">
    <div class="card">
      <div class="card-head"><h3>Dias ativos</h3><span class="xs muted">últimas 14 semanas</span></div>
      <div class="heat"><?php foreach ($heat as $i => $c): ?><i class="<?= $c['level'] > 0 ? 'l' . $c['level'] : '' ?>" style="--i:<?= $i ?><?= $c['level'] < 0 ? ';opacity:.25' : '' ?>" title="<?= e(fmt_date_short($c['date'])) ?>"></i><?php endforeach; ?></div>
    </div>
    <div class="card">
      <div class="card-head"><h3>Autoavaliações (média)</h3></div>
      <?php if (count($series) < 2): ?><p class="muted small">Ainda não há registros suficientes.</p><?php else: ?>
        <svg viewBox="0 0 340 120" width="100%" height="140" preserveAspectRatio="none"><path class="chart-area" d="<?= e($paths['area']) ?>"/><path class="chart-line" d="<?= e($paths['line']) ?>" vector-effect="non-scaling-stroke"/></svg>
        <div class="row between xs muted"><span><?= e(fmt_date_short($series[0]['date'])) ?></span><span><?= e(fmt_date_short($series[count($series) - 1]['date'])) ?></span></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card mt">
    <div class="card-head"><h3>Últimos treinos</h3></div>
    <?php if (!$sessions): ?><p class="small muted">Nenhum registro ainda.</p><?php endif; ?>
    <div class="list">
      <?php foreach ($sessions as $ss): ?>
        <a class="list-item" href="/painel/alunos/<?= $id ?>/sessoes/<?= (int) $ss['id'] ?>"><div class="grow"><div class="list-title"><?= e(Workouts::title($ss)) ?></div><div class="list-sub"><?= e(fmt_date($ss['scheduled_date'], true)) ?><?= $ss['notes'] ? ' · “' . e(mb_strimwidth($ss['notes'], 0, 60, '…')) . '”' : '' ?></div></div><span class="badge <?= $ss['status'] === 'completed' ? 'ok' : ($ss['status'] === 'skipped' ? 'danger' : ($ss['status'] === 'postponed' ? 'warn' : 'blue')) ?>"><?= e(Schedule::sessionStatusLabel($ss['status'])) ?></span></a>
      <?php endforeach; ?>
    </div>
  </div>
  <h2 class="section" style="margin-bottom:12px">Avaliações físicas</h2>
<?php endif; ?>

<?php if ($tab === 'visao'): ?>
  <div class="grid grid-main">
    <div class="stack">
      <div class="card">
        <div class="card-head"><h3>Hoje</h3><a class="small" href="?aba=calendario">Calendário</a></div>
        <?php if (!$profile['onboarding_completed_at']): ?><p class="small muted">Avaliação inicial ainda não enviada.</p>
        <?php elseif (!$today): ?><p class="small muted">Nenhum treino previsto para hoje.</p><?php endif; ?>
        <div class="list">
          <?php foreach ($today as $w): ?>
            <a class="list-item" href="/painel/treinos/<?= (int) $w['id'] ?>"><?= icon('dumbbell') ?><div class="grow"><div class="list-title"><?= e(Workouts::title($w)) ?></div><div class="list-sub"><?= e($w['modality_name']) ?></div></div><span class="badge"><?= e(Schedule::sessionStatusLabel($w['session']['status'] ?? null)) ?></span></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-head"><h3>Últimos registros de treino</h3><a class="small" href="?aba=treinos">Todos</a></div>
        <?php if (!$sessions): ?><p class="small muted">Nenhum registro ainda.</p><?php endif; ?>
        <div class="list">
          <?php foreach ($sessions as $ss): ?>
            <a class="list-item" href="/painel/alunos/<?= $id ?>/sessoes/<?= (int) $ss['id'] ?>"><div class="grow"><div class="list-title"><?= e(Workouts::title($ss)) ?></div><div class="list-sub"><?= e(fmt_date($ss['scheduled_date'], true)) ?><?= $ss['notes'] ? ' · “' . e(mb_strimwidth($ss['notes'], 0, 60, '…')) . '”' : '' ?></div></div><span class="badge <?= $ss['status'] === 'completed' ? 'ok' : ($ss['status'] === 'skipped' ? 'danger' : ($ss['status'] === 'postponed' ? 'warn' : 'dark')) ?>"><?= e(Schedule::sessionStatusLabel($ss['status'])) ?></span></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card">
        <h3>Resumo</h3>
        <dl class="kv mt">
          <dt>Ciclo atual</dt><dd><?= (int) $profile['cycle'] ?> · desde <?= e(fmt_date_short($profile['cycle_started_on'])) ?></dd>
          <dt>Avaliação inicial</dt><dd><?= $profile['onboarding_completed_at'] ? e(fmt_datetime($profile['onboarding_completed_at'])) : 'Pendente' ?></dd>
          <dt>Próxima avaliação física</dt><dd><?= $profile['next_assessment_on'] ? e(fmt_date($profile['next_assessment_on'], true)) : '—' ?></dd>
          <dt>Última autoavaliação</dt><dd><?= $lastSelf ? e(fmt_date($lastSelf['ref_date'])) : '—' ?></dd>
          <dt>Último acesso</dt><dd><?= e(fmt_ago($s['last_activity_at'])) ?></dd>
        </dl>
        <?php if (count($cycles) > 1): ?><p class="section-title mt-lg">Ciclos</p><div class="small"><?php foreach ($cycles as $cy): ?><div>Ciclo <?= (int) $cy['number'] ?>: <?= e(fmt_date_short($cy['started_on'])) ?> → <?= $cy['ended_on'] ? e(fmt_date_short($cy['ended_on'])) : 'atual' ?></div><?php endforeach; ?></div><?php endif; ?>
      </div>
    </div>
    <?php $renderAdvanced(); ?>
  </div>

<?php endif; ?>
<?php if ($tab === 'treinos'): ?>
  <div class="grid grid-main">
    <div class="stack">
      <?php if (!$workouts): ?><div class="card empty"><?= icon('dumbbell', 'icon-lg') ?><h3>Nenhum treino criado</h3><p>Crie o primeiro treino ao lado. Enquanto não houver treino publicado, o aluno vê “Seu treino está sendo preparado pelo Renan.”</p></div><?php endif; ?>
      <?php foreach ($workouts as $w): ?>
        <div class="card">
          <div class="row between nowrap">
            <div class="grow">
              <h3 class="truncate"><?= e(Workouts::title($w)) ?></h3>
              <p class="small muted"><?= e($w['modality_name']) ?> · <?= e(weekday_names(Workouts::weekdays($w))) ?><?= $w['starts_on'] || $w['ends_on'] ? ' · ' . e(fmt_date_short($w['starts_on'])) . ' a ' . e(fmt_date_short($w['ends_on'])) : '' ?></p>
            </div>
            <a class="btn sm" href="/painel/treinos/<?= (int) $w['id'] ?>"><?= icon('edit', 'icon-sm') ?> Editar</a>
          </div>
          <div class="row mt small" style="gap:6px">
            <?php if ($w['published']): ?><span class="badge ok">Publicado · v<?= (int) $w['published']['version_no'] ?> · <?= e(fmt_date_short(to_local($w['published']['published_at'])->format('Y-m-d'))) ?></span><?php else: ?><span class="badge outline">Não publicado</span><?php endif; ?>
            <?php if ($w['working'] && $w['working']['status'] === 'scheduled'): ?><span class="badge warn">v<?= (int) $w['working']['version_no'] ?> agendada para <?= e(fmt_datetime($w['working']['publish_at'])) ?></span>
            <?php elseif ($w['working'] && $w['published']): ?><span class="badge">Rascunho v<?= (int) $w['working']['version_no'] ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="card">
        <div class="card-head"><h3>Histórico de execução</h3></div>
        <?php if (!$sessions): ?><p class="small muted">Nenhum registro.</p><?php else: ?>
        <div class="table-wrap"><table class="table stack-mobile">
          <thead><tr><th>Data</th><th>Treino</th><th>Situação</th><th>Exercícios</th><th></th></tr></thead>
          <tbody><?php foreach ($sessions as $ss): ?>
            <tr>
              <td data-label="Data"><?= e(fmt_date_short($ss['scheduled_date'])) ?></td>
              <td data-label="Treino"><?= e(Workouts::title($ss)) ?> <span class="xs muted">v<?= (int) $ss['version_no'] ?></span></td>
              <td data-label="Situação"><span class="badge <?= $ss['status'] === 'completed' ? 'ok' : ($ss['status'] === 'skipped' ? 'danger' : ($ss['status'] === 'postponed' ? 'warn' : 'dark')) ?>"><?= e(Schedule::sessionStatusLabel($ss['status'])) ?></span></td>
              <td data-label="Exercícios"><?= (int) $ss['done'] ?>/<?= (int) $ss['total_items'] ?></td>
              <td class="right"><a class="btn ghost sm" href="/painel/alunos/<?= $id ?>/sessoes/<?= (int) $ss['id'] ?>">Ver</a></td>
            </tr>
          <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
      </div>

      <?php if ($requests): ?>
        <div class="card"><div class="card-head"><h3>Pedidos de mudança</h3><a class="small" href="/painel/solicitacoes">Responder</a></div>
          <div class="list"><?php foreach ($requests as $rq): ?><div class="list-item small"><div class="grow"><?= e(Workouts::title($rq)) ?>: <?= e(fmt_date_short($rq['original_date'])) ?> → <?= e(fmt_date_short($rq['requested_date'])) ?></div><span class="badge <?= $rq['status'] === 'approved' ? 'ok' : ($rq['status'] === 'rejected' ? 'danger' : 'warn') ?>"><?= ['pending' => 'Pendente', 'approved' => 'Aprovado', 'rejected' => 'Recusado'][$rq['status']] ?></span></div><?php endforeach; ?></div>
        </div>
      <?php endif; ?>

      <?php if ($archived): ?>
        <details class="card"><summary class="strong">Treinos arquivados (<?= count($archived) ?>)</summary>
          <div class="list mt"><?php foreach ($archived as $aw): ?><div class="list-item"><div class="grow"><?= e(Workouts::title($aw)) ?> <span class="xs muted">· <?= e($aw['modality_name']) ?></span></div><form method="post" action="/painel/treinos/<?= (int) $aw['id'] ?>/arquivar"><?= csrf_field() ?><input type="hidden" name="restore" value="1"><button class="btn ghost sm">Restaurar</button></form></div><?php endforeach; ?></div>
        </details>
      <?php endif; ?>
    </div>

    <form class="card" id="novo-treino" method="post" action="/painel/alunos/<?= $id ?>/treinos" style="align-self:start">
      <?= csrf_field() ?>
      <h3>Novo treino</h3>
      <div class="form-grid cols-2 mt">
        <div class="field"><label for="nl">Identificação</label><input id="nl" name="label" placeholder="Treino A" list="labels"><datalist id="labels"><option value="Treino A"><option value="Treino B"><option value="Treino C"><option value="Treino D"></datalist></div>
        <div class="field"><label for="nm" class="req">Modalidade</label><select id="nm" name="modality_id" required><?php foreach ($modalities as $m): if (!$m['active']) continue; ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $profile['modality_id']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label for="nn" class="req">Nome</label><input id="nn" name="name" required placeholder="Ex.: Força de membros inferiores"></div>
      <div class="field"><span class="label">Dias da semana</span><div class="pills"><?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><label class="pill-check"><input type="checkbox" name="weekdays[]" value="<?= $d ?>"><span><?= PT_WEEKDAYS_SHORT[$d] ?></span></label><?php endforeach; ?></div></div>
      <div class="form-grid cols-2 mt">
        <div class="field"><label for="so">Início (opcional)</label><input id="so" type="date" name="starts_on"></div>
        <div class="field"><label for="eo">Fim (opcional)</label><input id="eo" type="date" name="ends_on"></div>
      </div>
      <?php if ($templates): ?>
        <div class="field"><label for="tp">Partir de um modelo</label><select id="tp" name="template_id"><option value="">Começar do zero</option><?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?> · <?= e($t['modality_name']) ?></option><?php endforeach; ?></select></div>
      <?php endif; ?>
      <button class="btn mt" type="submit"><?= icon('plus', 'icon-sm') ?> Criar e editar</button>
      <p class="hint mt">Dias de descanso e atividades complementares são adicionados no calendário.</p>
    </form>
  </div>

<?php endif; ?>
<?php if (in_array($tab, ['avaliacoes', 'progresso'], true)): ?>
  <div class="card mb"><p class="small">Próxima avaliação: <b><?= $profile['next_assessment_on'] ? e(fmt_date($profile['next_assessment_on'], true)) : 'não definida' ?></b> · intervalo de <?= (int) $profile['assessment_every_days'] ?> dias. <a href="?aba=dados#avancado">Alterar</a></p></div>
  <?php if (!$assessments): ?><div class="card empty"><?= icon('camera', 'icon-lg') ?><h3>Nenhuma avaliação enviada</h3></div><?php else: ?>
    <div class="card"><div class="list">
      <?php foreach ($assessments as $a): ?>
        <a class="list-item" href="/painel/avaliacoes/<?= (int) $a['id'] ?>"><?= icon('camera') ?><div class="grow"><div class="list-title"><?= e(fmt_datetime($a['submitted_at'])) ?></div><div class="list-sub">Ciclo <?= (int) $a['cycle'] ?><?= $a['due_on'] ? ' · prevista para ' . e(fmt_date_short($a['due_on'])) : '' ?></div></div><span class="badge <?= $a['status'] === 'reviewed' ? 'ok' : 'warn' ?>"><?= $a['status'] === 'reviewed' ? 'Revisada' : 'Para revisar' ?></span></a>
      <?php endforeach; ?>
    </div></div>
  <?php endif; ?>

<?php endif; ?>
<?php if ($tab === 'progresso'): ?><h2 class="section" style="margin-bottom:12px">Autoavaliações e alimentação</h2><?php endif; ?>
<?php if (in_array($tab, ['autoavaliacoes', 'progresso'], true)):
  $cats = $profile['categories'];
  $pendingDays = $dueDate < today() ? days_between($dueDate, today()) : 0;
?>
  <div class="row mb">
    <span class="badge outline">Frequência: a cada <?= plural((int) $profile['self_eval_every_days'], 'dia', 'dias') ?></span>
    <?php if ($pendingDays > 0): ?><span class="badge warn"><?= plural($pendingDays, 'dia pendente', 'dias pendentes') ?></span><?php elseif ($dueDate === today()): ?><span class="badge warn">Pendente hoje</span><?php else: ?><span class="badge ok">Em dia</span><?php endif; ?>
  </div>
  <div class="card">
    <?php if (!$selfs): ?><p class="muted small">Nenhuma autoavaliação registrada.</p><?php else: ?>
    <div class="table-wrap"><table class="table stack-mobile">
      <thead><tr><th>Data</th><?php foreach ($cats as $c): ?><th><?= e(Students::SELF_EVAL_CATEGORIES[$c] ?? $c) ?></th><?php endforeach; ?><th>Atenção</th><th>Observações</th></tr></thead>
      <tbody>
        <?php foreach ($selfs as $i => $row): $sc = json_decode_array($row['scores']); $prev = isset($selfs[$i + 1]) ? json_decode_array($selfs[$i + 1]['scores']) : null; $flags = SelfEval::attention($sc, $prev); ?>
          <tr>
            <td data-label="Data" class="strong"><?= e(fmt_date_short($row['ref_date'])) ?></td>
            <?php foreach ($cats as $c): $v = $sc[$c] ?? null; ?><td data-label="<?= e(Students::SELF_EVAL_CATEGORIES[$c] ?? $c) ?>"><?= $v ? '<span title="' . e(SelfEval::SCALE[$v]['label']) . '">' . SelfEval::SCALE[$v]['emoji'] . ' ' . (int) $v . '</span>' : '—' ?></td><?php endforeach; ?>
            <td data-label="Atenção"><?php foreach ($flags as $k => $why): ?><span class="badge <?= $why === 'nota baixa' ? 'danger' : 'warn' ?>"><?= e(Students::SELF_EVAL_CATEGORIES[$k] ?? $k) ?>: <?= e($why) ?></span> <?php endforeach; ?><?= $flags ? '' : '—' ?></td>
            <td data-label="Observações" class="small pre"><?= e($row['notes'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
    <p class="hint mt">Indicadores de atenção: nota ≤ 2 ou queda de 2 pontos em relação ao registro anterior. São percepções do aluno, não diagnósticos.</p>
  </div>
  <div class="card mt">
    <h3>Alimentação e hidratação</h3>
    <?php if (!$nutrition): ?><p class="small muted mt">Sem registros.</p><?php endif; ?>
    <div class="list mt"><?php foreach ($nutrition as $n): ?><div class="list-item small"><div style="width:52px"><b><?= e((new DateTimeImmutable($n['ref_date']))->format('d/m')) ?></b></div><div class="grow"><b><?= e(NutritionController::MEALS[$n['meal']] ?? '') ?></b><?= $n['water_ml'] ? ' · ' . (int) $n['water_ml'] . ' ml' : '' ?><?php if ($n['description']): ?><div class="pre"><?= e($n['description']) ?></div><?php endif; ?><?php if ($n['notes']): ?><div class="muted"><?= e($n['notes']) ?></div><?php endif; ?></div></div><?php endforeach; ?></div>
  </div>

<?php endif; ?>
<?php if ($tab === 'calendario'): ?>
  <?= View::partial('calendar', get_defined_vars() + ['base' => "/painel/alunos/{$id}", 'q' => ['aba' => 'calendario'], 'canEdit' => true, 'studentId' => $id, 'returnTo' => 'perfil']) ?>

<?php endif; ?>
<?php if ($tab === 'mensagens'): ?>
  <?= View::partial('chat', ['studentId' => $id, 'peerName' => $s['name']]) ?>

<?php endif; ?>
<?php if ($tab === 'dados'): ?>
  <div class="grid grid-main">
    <div class="stack">
      <?php if (!$anamnesis || $anamnesis['status'] !== 'submitted'): ?>
        <div class="card empty"><?= icon('clipboard', 'icon-lg') ?><h3>Avaliação inicial ainda não enviada</h3><p><?= $anamnesis ? 'O aluno salvou um rascunho em ' . e(fmt_datetime($anamnesis['updated_at'])) . '.' : 'O aluno ainda não começou.' ?></p></div>
      <?php else: ?>
        <div class="card">
          <div class="card-head"><h3>Anamnese · ciclo <?= (int) $anamnesis['cycle'] ?></h3><span class="small muted">Enviada em <?= e(fmt_datetime($anamnesis['submitted_at'])) ?></span></div>
          <?php foreach ($sections as $sec): ?>
            <p class="section-title mt-lg"><?= e($sec['title']) ?></p>
            <dl class="kv"><?php foreach ($sec['fields'] as $f): ?><dt><?= e($f['label']) ?></dt><dd><?= e(Anamnesis::display($f, $anamnesis['answers'][$f['key']] ?? '')) ?></dd><?php endforeach; ?></dl>
          <?php endforeach; ?>
          <?php if ($anamnesis['personal_notes']): ?><p class="section-title mt-lg">Observações pessoais</p><p class="pre"><?= e($anamnesis['personal_notes']) ?></p><?php endif; ?>
        </div>
        <div class="card">
          <h3>Fotografias da avaliação inicial</h3>
          <div class="gallery mt">
            <?php foreach ($photos as $p): ?><figure><a href="<?= e(Files::url((int) $p['id'])) ?>" target="_blank"><img src="<?= e(Files::url((int) $p['id'])) ?>" alt="Foto <?= e($p['kind']) ?>" loading="lazy"></a><figcaption><?= e(['front' => 'Frente', 'side' => 'Lado', 'back' => 'Costas'][$p['kind']] ?? 'Adicional') ?></figcaption></figure><?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div class="stack">
      <div class="card">
        <h3>Dados pessoais</h3>
        <dl class="kv mt">
          <dt>E-mail</dt><dd><?= e($s['email']) ?></dd>
          <dt>Telefone</dt><dd><?= e($profile['phone'] ?: '—') ?></dd>
          <dt>Nascimento</dt><dd><?= e(fmt_date_short($profile['birth_date'])) ?></dd>
          <dt>Cadastro</dt><dd><?= e(fmt_datetime($s['created_at'])) ?></dd>
          <dt>Consentimento</dt><dd><?= $s['consent_at'] ? e(fmt_datetime($s['consent_at'])) . ' (v' . e($s['consent_version']) . ')' : 'Pendente' ?></dd>
        </dl>
      </div>
      <?php if (count($history) > 1): ?>
        <div class="card"><h3>Anamneses anteriores</h3><div class="list mt"><?php foreach ($history as $h): ?><a class="list-item small" href="?aba=dados&anamnese=<?= (int) $h['id'] ?>">Ciclo <?= (int) $h['cycle'] ?> · <?= e(fmt_datetime($h['submitted_at'])) ?></a><?php endforeach; ?></div></div>
      <?php endif; ?>
      <div class="card">
        <h3>Assinaturas</h3>
        <?php if (!$subs): ?><p class="small muted mt">Nenhuma.</p><?php endif; ?>
        <div class="list mt"><?php foreach ($subs as $sb): ?><div class="list-item small"><div class="grow"><?= e($sb['plan_name']) ?> · <?= money((int) $sb['amount_cents']) ?><div class="muted"><?= $sb['mode'] === 'recurring' ? 'Recorrente' : 'Período' ?> · até <?= e(fmt_date_short($sb['current_period_end'])) ?></div></div><span class="badge"><?= e(Billing::STATUS_LABELS[$sb['status']] ?? $sb['status']) ?></span></div><?php endforeach; ?></div>
      </div>
      <?php if ($s['role'] === 'student'): ?>
      <details class="card" style="border-color:#f0c8c4">
        <summary class="strong" style="color:var(--danger)">Excluir dados definitivamente (LGPD)</summary>
        <form method="post" action="/painel/alunos/<?= $id ?>/excluir-dados" class="mt" data-confirm="Esta ação é irreversível. Excluir todos os dados e fotografias deste aluno?">
          <?= csrf_field() ?>
          <p class="small muted">Remove a conta, anamneses, fotografias, avaliações, registros, mensagens e pagamentos deste aluno. Use apenas para atender a um pedido de exclusão.</p>
          <div class="field mt"><label for="ce">Digite o e-mail do aluno para confirmar</label><input id="ce" name="confirm_email" autocomplete="off"></div>
          <button class="btn danger mt" type="submit">Excluir definitivamente</button>
        </form>
      </details>
      <?php endif; ?>
    </div>
  </div>
  <details class="card more section" id="avancado">
    <summary><?= icon('settings', 'icon-sm') ?> Avançado: configurações individuais, situação e resumo</summary>
    <div class="grid grid-2 mt">
      <?php $renderAdvanced(); ?>
      <div class="card" style="align-self:start">
        <h3>Resumo</h3>
        <dl class="kv mt">
          <dt>Ciclo atual</dt><dd><?= (int) $profile['cycle'] ?> · desde <?= e(fmt_date_short($profile['cycle_started_on'])) ?></dd>
          <dt>Avaliação inicial</dt><dd><?= $profile['onboarding_completed_at'] ? e(fmt_datetime($profile['onboarding_completed_at'])) : 'Pendente' ?></dd>
          <dt>Próxima avaliação física</dt><dd><?= $profile['next_assessment_on'] ? e(fmt_date($profile['next_assessment_on'], true)) : '—' ?></dd>
          <dt>Último acesso</dt><dd><?= e(fmt_ago($s['last_activity_at'])) ?></dd>
        </dl>
      </div>
    </div>
  </details>
<?php endif; ?>
