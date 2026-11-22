<div class="page-head"><div><h1>Avaliações físicas</h1><p>Envios dos alunos com fotos de frente, lado e costas.</p></div>
  <div class="seg"><a class="<?= $status === 'submitted' ? 'active' : '' ?>" href="/painel/avaliacoes">Para revisar</a><a class="<?= $status === 'reviewed' ? 'active' : '' ?>" href="/painel/avaliacoes?situacao=revisadas">Revisadas</a></div>
</div>
<div class="grid grid-main">
  <div>
    <?php if (!$rows): ?><div class="card empty"><?= icon('camera', 'icon-lg') ?><h3><?= $status === 'submitted' ? 'Nenhuma avaliação aguardando revisão' : 'Nenhuma avaliação revisada' ?></h3></div><?php else: ?>
      <div class="card"><div class="list">
        <?php foreach ($rows as $a): ?>
          <a class="list-item" href="/painel/avaliacoes/<?= (int) $a['id'] ?>"><?= avatar($a, 'sm') ?><div class="grow"><div class="list-title"><?= e($a['name']) ?></div><div class="list-sub">Enviada <?= e(fmt_ago($a['submitted_at'])) ?><?= $a['due_on'] ? ' · prevista para ' . e(fmt_date_short($a['due_on'])) : '' ?></div></div><?= icon('chevron-right', 'icon-sm') ?></a>
        <?php endforeach; ?>
      </div></div>
    <?php endif; ?>
  </div>
  <div class="card" style="align-self:start">
    <h3>Atrasadas</h3>
    <?php if (!$overdue): ?><p class="small muted mt">Nenhum aluno com avaliação atrasada.</p><?php endif; ?>
    <div class="list mt"><?php foreach ($overdue as $o): ?><a class="list-item small" href="/painel/alunos/<?= (int) $o['id'] ?>?aba=avaliacoes"><div class="grow"><b><?= e($o['name']) ?></b><div class="muted">prevista para <?= e(fmt_date_short($o['next_assessment_on'])) ?></div></div><span class="badge danger"><?= days_between($o['next_assessment_on'], today()) ?> d</span></a><?php endforeach; ?></div>
  </div>
</div>
