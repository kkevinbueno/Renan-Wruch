<?php
$q = array_filter(['q' => $f['q'], 'situacao' => $f['status'], 'modalidade' => $f['modality'], 'ordem' => $f['sort'] !== 'name' ? $f['sort'] : null, 'testes' => $f['include_dev'] ? 1 : null]);
$chips = ['active' => 'Ativos', 'pending' => 'Aguardando', 'late' => 'Atrasados', 'paused' => 'Pausados', 'ended' => 'Encerrados', '' => 'Todos'];
$link = fn(array $over) => '/painel/alunos?' . http_build_query(array_filter(array_merge($q, $over), fn($v) => $v !== null && $v !== ''));
?>
<div class="page-head"><div><h1>Alunos</h1><p><?= plural($r['total'], 'aluno', 'alunos') ?></p></div></div>

<form method="get" action="/painel/alunos" class="mb">
  <?php if ($f['status']): ?><input type="hidden" name="situacao" value="<?= e($f['status']) ?>"><?php endif; ?>
  <div class="row nowrap"><div class="grow" style="position:relative"><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por nome ou e-mail" aria-label="Buscar aluno"></div><button class="btn icon-only" type="submit" aria-label="Buscar"><?= icon('search') ?></button></div>
  <details class="more mt"<?= $f['modality'] || $f['sort'] !== 'name' || $f['include_dev'] ? ' open' : '' ?>>
    <summary><?= icon('settings', 'icon-sm') ?> Mais filtros</summary>
    <div class="form-grid cols-3 mt">
      <div class="field"><label for="md">Modalidade</label><select id="md" name="modalidade"><option value="">Todas</option><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $f['modality']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="or">Ordenar por</label><select id="or" name="ordem"><option value="name"<?= selected('name', $f['sort']) ?>>Nome</option><option value="recent"<?= selected('recent', $f['sort']) ?>>Cadastro mais recente</option><option value="activity"<?= selected('activity', $f['sort']) ?>>Última atividade</option><option value="assessment"<?= selected('assessment', $f['sort']) ?>>Próxima avaliação</option></select></div>
      <?php if (Auth::isDeveloper() || DB::val("SELECT COUNT(*) FROM users WHERE role = 'developer'")): ?><label class="check small" style="align-self:end"><input type="checkbox" name="testes" value="1"<?= checked($f['include_dev']) ?>> <span>Incluir contas de teste</span></label><?php endif; ?>
    </div>
    <button class="btn sm mt" type="submit">Aplicar</button>
  </details>
</form>

<nav class="filters mb" aria-label="Situação">
  <?php foreach ($chips as $k => $l): ?><a href="<?= e($link(['situacao' => $k, 'p' => null])) ?>" class="<?= $f['status'] === $k ? 'active' : '' ?>"><?= e($l) ?></a><?php endforeach; ?>
</nav>

<?php if (!$r['rows']): ?>
  <div class="card empty"><?= icon('users', 'icon-lg') ?><h3>Nenhum aluno aqui</h3><p>Novos cadastros aparecem em “Aguardando” depois que o aluno confirma o e-mail.</p></div>
<?php else: ?>
  <div class="card flush"><div class="list enter" style="padding:4px 18px">
    <?php foreach ($r['rows'] as $s):
      $mods = Students::modalitiesOf((int) $s['id']);
      if (!$mods && $s['modality_id'] && isset($modalities[$s['modality_id']])) $mods = [$modalities[$s['modality_id']]['name']];
      $last = max((string) $s['last_activity_at'], (string) $s['last_session_at']) ?: null;
      $lateAssess = $s['next_assessment_on'] && $s['next_assessment_on'] < today();
      $dot = $s['status'] === 'pending' ? 'warn' : ($s['status'] !== 'active' ? '' : ($lateAssess ? 'danger' : 'ok'));
    ?>
      <a class="list-item" href="/painel/alunos/<?= (int) $s['id'] ?>">
        <span class="av-wrap <?= e(mod_tone($s['modality_id'] ?: null)) ?>"><?= avatar($s) ?><i class="status <?= $dot ?>"></i></span>
        <div class="grow">
          <div class="list-title"><?= e($s['name']) ?><?= $s['role'] === 'developer' ? ' <span class="badge outline">teste</span>' : '' ?></div>
          <div class="list-sub truncate"><?= $mods ? e(implode(' · ', $mods)) : e($s['email']) ?></div>
        </div>
        <div class="right" style="flex:none">
          <?php if ($s['status'] !== 'active'): ?><?= badge_status($s['status']) ?>
          <?php elseif (!$s['onboarding_completed_at']): ?><span class="badge outline">avaliação inicial</span>
          <?php elseif ($lateAssess): ?><span class="badge danger">avaliação atrasada</span>
          <?php else: ?><span class="xs muted"><?= e(fmt_ago($last)) ?></span><?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div></div>
  <?= View::partial('pagination', ['page' => $r['page'], 'pages' => $r['pages'], 'base' => '/painel/alunos', 'query' => $q]) ?>
<?php endif; ?>
