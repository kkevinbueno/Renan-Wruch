<div class="page-head">
  <div><h1>Calendário</h1><p>Eventos oficiais criados aqui aparecem automaticamente para o aluno.</p></div>
  <?php if ($students): ?>
  <form method="get" class="row">
    <input type="hidden" name="ver" value="<?= $view === 'month' ? 'mes' : 'semana' ?>">
    <label for="al" class="small muted">Aluno</label>
    <select id="al" name="aluno" data-autosubmit style="width:auto;min-width:200px"><?php foreach ($students as $st): ?><option value="<?= (int) $st['id'] ?>"<?= selected($st['id'], $student['id'] ?? '') ?>><?= e($st['name']) ?></option><?php endforeach; ?></select>
  </form>
  <?php endif; ?>
</div>
<?php if (!$student): ?>
  <div class="card empty"><?= icon('calendar', 'icon-lg') ?><h3>Nenhum aluno ativo</h3><p>O calendário fica disponível quando houver alunos aprovados.</p></div>
<?php else: ?>
  <div class="grid grid-main">
    <div><?= View::partial('calendar', get_defined_vars() + ['q' => ['aluno' => $student['id']]]) ?></div>
    <div class="card" style="align-self:start">
      <h3>Agenda de todos os alunos</h3>
      <p class="small muted">De <?= e(fmt_date_short($start)) ?> a <?= e(fmt_date_short($end)) ?></p>
      <?php if (!$week): ?><p class="small muted mt">Nenhum evento no período.</p><?php endif; ?>
      <div class="list mt"><?php foreach ($week as $ev): ?><a class="list-item small" href="/painel/calendario?<?= e(http_build_query(['aluno' => $ev['student_id'], 'data' => $ev['event_date'], 'dia' => $ev['event_date']])) ?>"><div style="width:46px"><b><?= e((new DateTimeImmutable($ev['event_date']))->format('d/m')) ?></b></div><div class="grow"><?= e($ev['title']) ?><div class="muted"><?= e($ev['name']) ?><?= $ev['start_time'] ? ' · ' . e($ev['start_time']) : '' ?></div></div></a><?php endforeach; ?></div>
    </div>
  </div>
<?php endif; ?>
