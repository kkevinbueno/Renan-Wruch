<?php
$u = Auth::user();
$hour = (int) local_now()->format('H');
$greet = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
$main = $todays[0] ?? null;
?>
<div class="page-head">
  <div>
    <h1><?= e($greet) ?>, <?= e(first_name($u['name'])) ?></h1>
    <p><?= e(fmt_date(today(), true)) ?></p>
  </div>
</div>

<?php if (!empty($_GET['enviado'])): ?>
  <div class="callout ok mb"><?= icon('check') ?><div><b>Avaliação inicial enviada.</b><p class="small muted">Registramos suas respostas e fotografias em <?= e(fmt_datetime(Students::profile((int) $u['id'])['onboarding_completed_at'])) ?>. O Renan foi notificado.</p></div></div>
<?php endif; ?>

<?php if ($inProgress): ?>
  <div class="callout accent mb"><?= icon('clock') ?><div class="grow"><b>Treino em andamento</b><p class="small muted"><?= e(Workouts::title($inProgress)) ?> de <?= e(fmt_date($inProgress['scheduled_date'])) ?></p></div><a class="btn sm" href="/app/sessao/<?= (int) $inProgress['id'] ?>">Continuar</a></div>
<?php endif; ?>

<?php if (!$hasAny): ?>
  <section class="hero-workout empty-state">
    <p class="eyebrow muted">Treino do dia</p>
    <h2>Seu treino está sendo preparado pelo Renan.</h2>
    <p class="muted mt">Você receberá um e-mail assim que ele for publicado. Enquanto isso, você pode usar as outras áreas normalmente.</p>
    <div class="btn-row mt-lg"><a class="btn secondary" href="/app/mensagens"><?= icon('message') ?> Falar com o Renan</a></div>
  </section>
<?php elseif ($main): ?>
  <?php
    $modality = Modalities::find((int) $main['modality_id']);
    $items = Workouts::decodeItems(Workouts::items((int) $main['version']['id']), $modality);
    $st = $main['session']['status'] ?? null;
  ?>
  <section class="hero-workout" aria-labelledby="hw-title">
    <p class="eyebrow">Treino do dia<?= $main['moved_from'] ? ' · remarcado de ' . e(fmt_date_short($main['moved_from'])) : '' ?></p>
    <h2 id="hw-title"><?= e(Workouts::title($main)) ?></h2>
    <p class="meta"><?= e($main['modality_name']) ?> · <?= plural(count($items), 'exercício', 'exercícios') ?> · <?= e(Schedule::sessionStatusLabel($st)) ?></p>
    <ol class="ex-list">
      <?php foreach (array_slice($items, 0, 6) as $i => $it): ?>
        <li><span><?= $i + 1 ?></span><span><?= e($it['name']) ?></span></li>
      <?php endforeach; ?>
      <?php if (count($items) > 6): ?><li><span></span><span>+ <?= count($items) - 6 ?> exercícios</span></li><?php endif; ?>
    </ol>
    <div class="btn-row">
      <?php if ($st === 'completed'): ?>
        <a class="btn" href="/app/sessao/<?= (int) $main['session']['id'] ?>"><?= icon('check') ?> Ver registro</a>
      <?php else: ?>
        <form method="post" action="/app/treinos/<?= (int) $main['id'] ?>/iniciar">
          <?= csrf_field() ?><input type="hidden" name="date" value="<?= e(today()) ?>">
          <button class="btn" type="submit"><?= icon('play') ?> <?= $st === 'in_progress' ? 'Continuar treino' : 'Iniciar treino' ?></button>
        </form>
      <?php endif; ?>
      <a class="btn ghost" href="/app/treinos/<?= (int) $main['id'] ?>?data=<?= e(today()) ?>">Detalhes</a>
    </div>
  </section>
  <?php foreach (array_slice($todays, 1) as $w): ?>
    <a class="card row mt" href="/app/treinos/<?= (int) $w['id'] ?>?data=<?= e(today()) ?>" style="text-decoration:none">
      <?= icon('dumbbell') ?><div class="grow"><div class="strong"><?= e(Workouts::title($w)) ?></div><div class="small muted"><?= e($w['modality_name']) ?> · <?= e(Schedule::sessionStatusLabel($w['session']['status'] ?? null)) ?></div></div><?= icon('chevron-right') ?>
    </a>
  <?php endforeach; ?>
<?php else: ?>
  <section class="hero-workout empty-state">
    <p class="eyebrow muted">Treino do dia</p>
    <h2>Hoje é dia de descanso</h2>
    <?php if ($next): ?>
      <p class="muted mt">Próximo treino: <b><?= e(Workouts::title($next['workout'])) ?></b> · <?= e(fmt_date($next['date'], true)) ?></p>
      <div class="btn-row mt-lg"><a class="btn secondary" href="/app/treinos/<?= (int) $next['workout']['id'] ?>?data=<?= e($next['date']) ?>">Ver próximo treino</a></div>
    <?php else: ?>
      <p class="muted mt">Não há treinos previstos para os próximos dias.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>

<div class="section">
  <div class="quick">
    <a href="/app/calendario">
      <?= icon('calendar') ?>
      <span class="q-label">Próxima atividade</span>
      <span class="q-value"><?= $nextEvent ? e($nextEvent['title']) . ' · ' . e(fmt_date_short($nextEvent['event_date'])) : 'Nada agendado' ?></span>
    </a>
    <a href="/app/avaliacoes">
      <?= icon('camera') ?>
      <span class="q-label">Próxima avaliação física</span>
      <span class="q-value"><?= $pendingAssessment ? 'Enviada, aguardando revisão' : ($profile['next_assessment_on'] ? e(fmt_date($profile['next_assessment_on'])) : 'A definir') ?></span>
    </a>
    <a href="/app/autoavaliacao">
      <?= icon('smile') ?>
      <span class="q-label">Autoavaliação</span>
      <span class="q-value"><?= $selfDoneToday ? 'Preenchida hoje' : ($selfDue <= today() ? 'Preencher agora' : 'Próxima em ' . e(fmt_date_short($selfDue))) ?></span>
    </a>
    <a href="/app/mensagens">
      <?= icon('message') ?>
      <span class="q-label">Mensagens</span>
      <span class="q-value"><?= $unread ? plural($unread, 'não lida', 'não lidas') : 'Nenhuma nova' ?></span>
    </a>
  </div>
</div>
