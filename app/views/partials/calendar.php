<?php
/**
 * Calendário compartilhado (semanal padrão / mensal).
 * @var string $view @var string $date @var string $selected @var array $days @var array $selectedItems
 * @var string $base @var array $q (parâmetros extras) @var bool $canEdit
 */
$q = $q ?? [];
$dotClass = ['workout' => 'dark', 'assessment' => 'accent', 'activity' => 'ok', 'appointment' => 'warn', 'rest' => ''];
$link = fn(array $extra) => $base . '?' . http_build_query($q + ['ver' => $view === 'month' ? 'mes' : 'semana', 'data' => $date] + $extra);
$d = new DateTimeImmutable($date);
$period = $view === 'month'
    ? ucfirst(PT_MONTHS[(int) $d->format('n') - 1]) . ' de ' . $d->format('Y')
    : (new DateTimeImmutable($start))->format('d/m') . ' – ' . (new DateTimeImmutable($end))->format('d/m');
$month = $d->format('m');
?>
<div class="card">
  <div class="cal-toolbar">
    <a class="btn secondary sm icon-only" href="<?= e($base . '?' . http_build_query($q + ['ver' => $view === 'month' ? 'mes' : 'semana', 'data' => $prev, 'dia' => $prev])) ?>" aria-label="Período anterior"><?= icon('chevron-left', 'icon-sm') ?></a>
    <span class="period"><?= e($period) ?></span>
    <a class="btn secondary sm icon-only" href="<?= e($base . '?' . http_build_query($q + ['ver' => $view === 'month' ? 'mes' : 'semana', 'data' => $next, 'dia' => $next])) ?>" aria-label="Próximo período"><?= icon('chevron-right', 'icon-sm') ?></a>
    <a class="btn ghost sm" href="<?= e($base . '?' . http_build_query($q + ['ver' => $view === 'month' ? 'mes' : 'semana'])) ?>">Hoje</a>
    <span class="grow"></span>
    <div class="seg" role="tablist">
      <a class="<?= $view === 'week' ? 'active' : '' ?>" href="<?= e($base . '?' . http_build_query($q + ['ver' => 'semana', 'data' => $selected, 'dia' => $selected])) ?>">Semana</a>
      <a class="<?= $view === 'month' ? 'active' : '' ?>" href="<?= e($base . '?' . http_build_query($q + ['ver' => 'mes', 'data' => $selected, 'dia' => $selected])) ?>">Mês</a>
    </div>
  </div>

  <?php if ($view === 'month'): ?>
    <div class="cal-month-head"><?php foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $wd): ?><span><?= $wd ?></span><?php endforeach; ?></div>
  <?php endif; ?>
  <div class="<?= $view === 'month' ? 'cal-month' : 'cal-week' ?>">
    <?php foreach ($days as $day => $items):
      $dt = new DateTimeImmutable($day);
      $cls = ['cal-day'];
      if ($day === today()) $cls[] = 'today';
      if ($day === $selected) $cls[] = 'selected';
      if ($view === 'month' && $dt->format('m') !== $month) $cls[] = 'outside';
    ?>
      <a class="<?= implode(' ', $cls) ?>" href="<?= e($link(['dia' => $day])) ?>" aria-label="<?= e(fmt_date($day, true)) ?>: <?= count($items) ?> itens"<?= $day === $selected ? ' aria-current="date"' : '' ?>>
        <?php if ($view === 'week'): ?><span class="wd"><?= e(PT_WEEKDAYS_SHORT[(int) $dt->format('w')]) ?></span><?php endif; ?>
        <span class="dn"><?= (int) $dt->format('j') ?></span>
        <span class="cal-dots"><?php foreach (array_slice($items, 0, 4) as $it): ?><span class="dot <?= $dotClass[$it['type']] ?? '' ?>"></span><?php endforeach; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="legend"><span><span class="dot dark"></span>Treino</span><span><span class="dot accent"></span>Avaliação</span><span><span class="dot ok"></span>Atividade</span><span><span class="dot warn"></span>Compromisso</span><span><span class="dot"></span>Descanso</span></div>
</div>

<div class="card mt day-list">
  <div class="card-head"><h3><?= e(fmt_date($selected, true)) ?></h3><?php if ($canEdit): ?><button class="btn secondary sm" type="button" data-open-dialog="event-dialog"><?= icon('plus', 'icon-sm') ?> Evento</button><?php endif; ?></div>
  <?php if (!$selectedItems): ?><p class="muted small">Nada programado para este dia.</p><?php endif; ?>
  <div class="list">
    <?php foreach ($selectedItems as $it): ?>
      <div class="list-item">
        <span class="dot <?= $dotClass[$it['type']] ?? '' ?>" style="margin-top:8px"></span>
        <div class="grow">
          <div class="list-title"><?= e($it['title']) ?></div>
          <div class="list-sub"><?= e(Schedule::typeLabel($it['type'])) ?><?= !empty($it['subtitle']) ? ' · ' . e($it['subtitle']) : '' ?><?= !empty($it['moved_from']) ? ' · remarcado de ' . e(fmt_date_short($it['moved_from'])) : '' ?><?= !empty($it['status']) ? ' · ' . e(Schedule::sessionStatusLabel($it['status'])) : '' ?></div>
          <?php if (!empty($it['description'])): ?><p class="small mt pre"><?= e($it['description']) ?></p><?php endif; ?>
        </div>
        <?php if ($it['type'] === 'workout' && !$canEdit): ?>
          <a class="btn secondary sm" href="/app/treinos/<?= (int) $it['workout_id'] ?>?data=<?= e($selected) ?>">Abrir</a>
        <?php elseif ($canEdit && !empty($it['event_id'])): ?>
          <div class="btn-row">
            <a class="btn ghost sm icon-only" href="<?= e($link(['dia' => $selected, 'evento' => $it['event_id']])) ?>" aria-label="Editar"><?= icon('edit', 'icon-sm') ?></a>
            <form method="post" action="/painel/eventos/<?= (int) $it['event_id'] ?>/excluir" data-confirm="Remover este evento? O aluno será avisado."><?= csrf_field() ?><button class="btn ghost sm icon-only" aria-label="Remover"><?= icon('trash', 'icon-sm') ?></button></form>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$canEdit): ?><p class="hint mt">Precisa trocar um treino de dia? Abra o treino e use “Pedir mudança de dia”.</p><?php endif; ?>
</div>

<?php if ($canEdit && $studentId): $ev = $editEvent ?? null; ?>
<dialog id="event-dialog" <?= $ev ? 'data-open-on-load' : '' ?>>
  <form method="post" action="/painel/eventos">
    <?= csrf_field() ?>
    <input type="hidden" name="student_id" value="<?= (int) $studentId ?>">
    <?php if ($ev): ?><input type="hidden" name="id" value="<?= (int) $ev['id'] ?>"><?php endif; ?>
    <?php if (!empty($returnTo)): ?><input type="hidden" name="return" value="<?= e($returnTo) ?>"><?php endif; ?>
    <div class="dialog-head"><h3><?= $ev ? 'Editar evento' : 'Novo evento' ?></h3><button class="btn ghost sm icon-only" type="button" data-close-dialog aria-label="Fechar"><?= icon('x', 'icon-sm') ?></button></div>
    <div class="dialog-body">
      <div class="form-grid cols-2">
        <div class="field"><label for="ev_type">Tipo</label><select id="ev_type" name="type"><?php foreach ($types as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $ev['type'] ?? 'activity') ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="ev_date">Data</label><input id="ev_date" type="date" name="event_date" value="<?= e($ev['event_date'] ?? $selected) ?>" required></div>
      </div>
      <div class="field"><label for="ev_title">Título</label><input id="ev_title" name="title" value="<?= e($ev['title'] ?? '') ?>" placeholder="Ex.: Jogo de beach tennis, Consulta de retorno"></div>
      <div class="form-grid cols-2 mt">
        <div class="field"><label for="ev_s">Início</label><input id="ev_s" type="time" name="start_time" value="<?= e($ev['start_time'] ?? '') ?>"></div>
        <div class="field"><label for="ev_e">Fim</label><input id="ev_e" type="time" name="end_time" value="<?= e($ev['end_time'] ?? '') ?>"></div>
      </div>
      <div class="field"><label for="ev_d">Descrição</label><textarea id="ev_d" name="description" rows="3"><?= e($ev['description'] ?? '') ?></textarea></div>
      <label class="check mt"><input type="checkbox" name="notify" value="1" checked> <span>Avisar o aluno por e-mail</span></label>
    </div>
    <div class="dialog-foot"><button class="btn secondary" type="button" data-close-dialog>Cancelar</button><button class="btn" type="submit">Salvar</button></div>
  </form>
</dialog>
<?php endif; ?>
