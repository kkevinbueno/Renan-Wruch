<?php
$u = Auth::user();
$hour = (int) local_now()->format('H');
$greet = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
$main = $todays[0] ?? null;
$todayIso = today();
$waterPct = min(1, $water / Progress::WATER_GOAL_ML);
$assessDue = $profile['next_assessment_on'] && !$pendingAssessment && $profile['next_assessment_on'] <= date_add_days($todayIso, 3);
$selfDueNow = !$selfDoneToday && $selfDue <= $todayIso;
$payIssue = in_array($standing['tone'], ['warn', 'danger'], true) || ($standing['label'] === 'Sem assinatura' && Billing::plans());
$todo = 0; $todoDone = 0;
?>
<div class="hello">
  <div><div class="muted"><?= e($greet) ?>,</div><h1><?= e(first_name($u['name'])) ?></h1></div>
  <div class="row nowrap">
    <?php if ($streak > 0): ?><span class="streak" title="Treinos seguidos">🔥 <?= (int) $streak ?></span><?php endif; ?>
    <a href="/app/eu" class="av-wrap <?= e(mod_tone($main['modality_id'] ?? null)) ?>" aria-label="Meu perfil"><?= avatar($u) ?></a>
  </div>
</div>

<div class="week enter" aria-label="Sua semana">
  <?php foreach ($week as $d): ?>
    <a href="/app/calendario?data=<?= e($d['date']) ?>" class="<?= $d['date'] === $todayIso ? 'today' : '' ?><?= $d['done'] ? ' done-day' : '' ?>" aria-label="<?= e(fmt_date($d['date'], true)) ?>">
      <?= e($d['wd']) ?><b><?= (int) $d['n'] ?></b>
      <span class="dots"><?php foreach (array_slice($d['tones'], 0, 3) as $t): ?><i class="<?= e($t) ?>"></i><?php endforeach; ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!empty($_GET['enviado'])): ?>
  <div class="callout ok mb"><?= icon('check') ?><div><b>Avaliação inicial enviada!</b><p class="small">O Renan já foi avisado e vai preparar o seu treino.</p></div></div>
<?php endif; ?>

<?php if ($inProgress): ?>
  <a class="todo mb" href="/app/sessao/<?= (int) $inProgress['id'] ?>"><span class="ico blue"><?= icon('clock') ?></span><div class="grow"><div class="t">Treino em andamento</div><div class="xs muted"><?= e(Workouts::title($inProgress)) ?> · <?= e(fmt_date_short($inProgress['scheduled_date'])) ?></div></div><span class="btn sm">Continuar</span></a>
<?php endif; ?>

<?php if (!$hasAny): ?>
  <section class="hero-workout empty-state">
    <p class="eyebrow">Treino do dia</p>
    <h2>Seu treino está sendo preparado pelo Renan.</h2>
    <p class="muted">Você recebe um e-mail assim que ele for publicado.</p>
    <div class="btn-row mt-lg"><a class="btn" href="/app/mensagens"><?= icon('message') ?> Falar com o Renan</a></div>
  </section>
<?php elseif ($main): ?>
  <?php
    $modality = Modalities::find((int) $main['modality_id']);
    $items = Workouts::decodeItems(Workouts::items((int) $main['version']['id']), $modality);
    $st = $main['session']['status'] ?? null;
  ?>
  <section class="hero-workout <?= e(mod_tone($modality)) ?>" aria-labelledby="hw-title">
    <div class="top">
      <span class="chip mod"><?= icon('dumbbell', 'icon-sm') ?> <?= e($main['modality_name']) ?></span>
      <span class="eyebrow">Treino do dia<?= $main['moved_from'] ? ' · remarcado' : '' ?></span>
    </div>
    <h2 id="hw-title"><?= e(Workouts::title($main)) ?></h2>
    <p class="meta"><?= plural(count($items), 'exercício', 'exercícios') ?><?= $st ? ' · ' . e(Schedule::sessionStatusLabel($st)) : '' ?></p>
    <ol class="ex-list">
      <?php foreach (array_slice($items, 0, 5) as $i => $it): ?>
        <li><span><?= $i + 1 ?></span><span><?= e($it['name']) ?></span></li>
      <?php endforeach; ?>
      <?php if (count($items) > 5): ?><li><span></span><span>+ <?= count($items) - 5 ?> exercícios</span></li><?php endif; ?>
    </ol>
    <div class="btn-row">
      <?php if ($st === 'completed'): ?>
        <a class="btn lg" href="/app/sessao/<?= (int) $main['session']['id'] ?>"><?= icon('check') ?> Ver registro</a>
      <?php else: ?>
        <form method="post" action="/app/treinos/<?= (int) $main['id'] ?>/iniciar">
          <?= csrf_field() ?><input type="hidden" name="date" value="<?= e($todayIso) ?>">
          <button class="btn" type="submit"><?= icon('play') ?> <?= $st === 'in_progress' ? 'Continuar treino' : 'Começar treino' ?></button>
        </form>
      <?php endif; ?>
      <a class="btn ghost icon-only" href="/app/treinos/<?= (int) $main['id'] ?>?data=<?= e($todayIso) ?>" aria-label="Detalhes do treino"><?= icon('eye') ?></a>
    </div>
  </section>
  <?php foreach (array_slice($todays, 1) as $w): ?>
    <a class="todo mt <?= e(mod_tone((int) $w['modality_id'])) ?>" href="/app/treinos/<?= (int) $w['id'] ?>?data=<?= e($todayIso) ?>">
      <span class="ico mod"><?= icon('dumbbell') ?></span><div class="grow"><div class="t"><?= e(Workouts::title($w)) ?></div><div class="xs muted"><?= e($w['modality_name']) ?> · <?= e(Schedule::sessionStatusLabel($w['session']['status'] ?? null)) ?></div></div><?= icon('chevron-right', 'icon-sm') ?>
    </a>
  <?php endforeach; ?>
<?php else: ?>
  <section class="hero-workout empty-state">
    <p class="eyebrow">Hoje</p>
    <h2>Dia de descanso</h2>
    <?php if ($next): ?>
      <p class="muted">Próximo treino: <b><?= e(Workouts::title($next['workout'])) ?></b> · <?= e(fmt_date($next['date'], true)) ?></p>
      <div class="btn-row mt-lg"><a class="btn secondary" href="/app/treinos/<?= (int) $next['workout']['id'] ?>?data=<?= e($next['date']) ?>">Ver próximo treino</a></div>
    <?php else: ?>
      <p class="muted">Não há treinos previstos para os próximos dias.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>

<div class="section">
  <div class="row between" style="margin-bottom:10px"><h2>Para fazer hoje</h2><span class="xs muted" data-todo-count></span></div>
  <div class="enter">
    <a class="todo<?= $selfDoneToday ? ' done' : '' ?>" href="/app/autoavaliacao">
      <span class="ico" style="background:color-mix(in srgb, var(--m-beach) 35%, transparent)">😊</span>
      <div class="grow"><div class="t">Autoavaliação</div><div class="xs muted"><?= $selfDoneToday ? 'Feita hoje' : ($selfDueNow ? '7 perguntas · 30 segundos' : 'Próxima em ' . e(fmt_date_short($selfDue))) ?></div></div>
      <?= $selfDoneToday ? '<span class="badge ok">' . icon('check', 'icon-sm') . '</span>' : icon('chevron-right', 'icon-sm') ?>
    </a>
    <div class="todo">
      <span class="ico blue"><?= icon('droplet') ?></span>
      <a class="grow" href="/app/alimentacao" style="text-decoration:none"><div class="t">Água · <?= number_format($water / 1000, 1, ',', '') ?> de <?= number_format(Progress::WATER_GOAL_ML / 1000, 1, ',', '') ?> L</div><div class="bar"><span style="width:<?= round($waterPct * 100) ?>%"></span></div></a>
      <form method="post" action="/app/alimentacao"><?= csrf_field() ?><input type="hidden" name="meal" value="agua"><input type="hidden" name="water_ml" value="250"><input type="hidden" name="back" value="/app"><button class="btn pw sm" type="submit" aria-label="Adicionar 250 ml de água">+250</button></form>
    </div>
    <a class="todo" href="/app/alimentacao">
      <span class="ico" style="background:color-mix(in srgb, var(--m-musc) 30%, transparent)">🍽️</span>
      <div class="grow"><div class="t">Refeições</div><div class="xs muted">Anote o que você comeu</div></div><?= icon('chevron-right', 'icon-sm') ?>
    </a>
    <?php if ($assessDue): ?>
      <a class="todo" href="/app/avaliacoes/nova">
        <span class="ico warn"><?= icon('camera') ?></span>
        <div class="grow"><div class="t">Avaliação física</div><div class="xs muted"><?= $profile['next_assessment_on'] < $todayIso ? 'Atrasada desde ' : 'Prevista para ' ?><?= e(fmt_date_short($profile['next_assessment_on'])) ?> · 3 fotos</div></div><?= icon('chevron-right', 'icon-sm') ?>
      </a>
    <?php endif; ?>
    <?php if ($payIssue): ?>
      <a class="todo" href="/app/assinatura">
        <span class="ico danger"><?= icon('card') ?></span>
        <div class="grow"><div class="t">Assinatura</div><div class="xs muted"><?= e($standing['label']) ?></div></div><?= icon('chevron-right', 'icon-sm') ?>
      </a>
    <?php endif; ?>
    <?php if ($unread): ?>
      <a class="todo" href="/app/mensagens">
        <span class="ico blue"><?= icon('message') ?></span>
        <div class="grow"><div class="t">Mensagens</div><div class="xs muted"><?= plural($unread, 'nova mensagem', 'novas mensagens') ?> do Renan</div></div><?= icon('chevron-right', 'icon-sm') ?>
      </a>
    <?php endif; ?>
    <?php if ($nextEvent): ?>
      <a class="todo" href="/app/calendario?data=<?= e($nextEvent['event_date']) ?>">
        <span class="ico"><?= icon('calendar') ?></span>
        <div class="grow"><div class="t"><?= e($nextEvent['title']) ?></div><div class="xs muted"><?= e(fmt_date($nextEvent['event_date'], true)) ?><?= $nextEvent['start_time'] ? ' · ' . e(substr($nextEvent['start_time'], 0, 5)) : '' ?></div></div><?= icon('chevron-right', 'icon-sm') ?>
      </a>
    <?php endif; ?>
  </div>
</div>
