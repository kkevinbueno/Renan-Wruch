<?php $q = array_filter(['q' => $f['q'], 'situacao' => $f['status'], 'modalidade' => $f['modality'], 'ordem' => $f['sort'], 'testes' => $f['include_dev'] ? 1 : null]); ?>
<div class="page-head"><div><h1>Meus alunos</h1><p><?= plural($r['total'], 'aluno encontrado', 'alunos encontrados') ?></p></div></div>

<form class="card mb" method="get" action="/painel/alunos">
  <div class="form-grid cols-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
    <div class="field"><label for="q">Buscar</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nome ou e-mail"></div>
    <div class="field"><label for="st">Situação</label><select id="st" name="situacao"><option value="">Todas</option><?php foreach (Students::STATUSES + ['rejected' => 'Recusado'] as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $f['status']) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="md">Modalidade</label><select id="md" name="modalidade"><option value="">Todas</option><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $f['modality']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="or">Ordenar por</label><select id="or" name="ordem"><option value="name"<?= selected('name', $f['sort']) ?>>Nome</option><option value="recent"<?= selected('recent', $f['sort']) ?>>Cadastro mais recente</option><option value="activity"<?= selected('activity', $f['sort']) ?>>Última atividade</option><option value="assessment"<?= selected('assessment', $f['sort']) ?>>Próxima avaliação</option></select></div>
  </div>
  <div class="row between mt">
    <?php if (Auth::isDeveloper() || DB::val("SELECT COUNT(*) FROM users WHERE role = 'developer'")): ?><label class="check small"><input type="checkbox" name="testes" value="1"<?= checked($f['include_dev']) ?>> <span>Incluir contas de teste (desenvolvedores)</span></label><?php else: ?><span></span><?php endif; ?>
    <div class="btn-row"><a class="btn ghost sm" href="/painel/alunos">Limpar</a><button class="btn sm" type="submit">Filtrar</button></div>
  </div>
</form>

<?php if (!$r['rows']): ?>
  <div class="card empty"><?= icon('users', 'icon-lg') ?><h3>Nenhum aluno encontrado</h3><p>Novos cadastros aparecem aqui como “Pendente” depois que o aluno confirma o e-mail.</p></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap"><table class="table stack-mobile">
      <thead><tr><th>Aluno</th><th>Modalidade</th><th>Situação</th><th>Última atividade</th><th>Próxima avaliação</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($r['rows'] as $s):
        $mods = Students::modalitiesOf((int) $s['id']);
        if (!$mods && $s['modality_id'] && isset($modalities[$s['modality_id']])) $mods = [$modalities[$s['modality_id']]['name']];
        $last = max((string) $s['last_activity_at'], (string) $s['last_session_at']) ?: null;
      ?>
        <tr>
          <td data-label="Aluno"><a class="row nowrap" href="/painel/alunos/<?= (int) $s['id'] ?>" style="text-decoration:none"><?= avatar($s, 'sm') ?><span><span class="strong"><?= e($s['name']) ?></span><?= $s['role'] === 'developer' ? ' <span class="badge outline">teste</span>' : '' ?><br><span class="xs muted"><?= e($s['email']) ?></span></span></a></td>
          <td data-label="Modalidade"><?= $mods ? e(implode(', ', $mods)) : '<span class="muted">—</span>' ?></td>
          <td data-label="Situação"><?= badge_status($s['status']) ?><?php if ($s['status'] === 'active' && !$s['onboarding_completed_at']): ?> <span class="badge outline">avaliação inicial pendente</span><?php endif; ?></td>
          <td data-label="Última atividade" class="small"><?= e(fmt_ago($last)) ?></td>
          <td data-label="Próxima avaliação" class="small"><?php if ($s['next_assessment_on']): ?><span class="<?= $s['next_assessment_on'] < today() ? 'badge danger' : '' ?>"><?= e(fmt_date_short($s['next_assessment_on'])) ?></span><?php else: ?>—<?php endif; ?></td>
          <td class="right"><a class="btn secondary sm" href="/painel/alunos/<?= (int) $s['id'] ?>">Abrir</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <?= View::partial('pagination', ['page' => $r['page'], 'pages' => $r['pages'], 'base' => '/painel/alunos', 'query' => $q]) ?>
<?php endif; ?>
