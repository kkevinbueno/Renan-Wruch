<?php
/** @var array $rows @var array $f @var array $query @var array $favorites @var array $stats */
$activeFilters = array_filter([$f['musculo'], $f['equipamento'], $f['nivel'], $f['video'], $f['mod'], $f['favoritos'], $f['personalizados'], $f['arquivados']]);
$levelTone = ['iniciante' => 'ok', 'intermediario' => 'warn', 'avancado' => 'danger'];
?>
<a class="btn ghost sm" href="/painel/mais"><?= icon('chevron-left', 'icon-sm') ?> Mais</a>
<div class="page-head mt">
  <div><h1>Biblioteca</h1><p><?= plural($stats['all'], 'exercício', 'exercícios') ?> · <?= plural($stats['ok'], 'vídeo revisado', 'vídeos revisados') ?> · <?= plural($stats['pending'], 'vídeo pendente', 'vídeos pendentes') ?></p></div>
  <a class="btn" href="/painel/biblioteca/novo"><?= icon('plus', 'icon-sm') ?> Novo exercício</a>
</div>
<nav class="tabs"><a href="/painel/biblioteca" class="active">Exercícios</a><a href="/painel/modelos">Modelos de treino</a></nav>

<form class="lib-filters" method="get" role="search">
  <div class="lib-search">
    <?= icon('search', 'icon-sm') ?>
    <label for="lq" class="sr-only">Buscar exercício</label>
    <input id="lq" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por nome (português ou inglês)">
    <button class="btn sm" type="submit">Buscar</button>
  </div>
  <details class="lib-more"<?= $activeFilters ? ' open' : '' ?>>
    <summary><?= icon('filter', 'icon-sm') ?> Filtros<?= $activeFilters ? ' <span class="count">' . count($activeFilters) . '</span>' : '' ?></summary>
    <div class="form-grid lib-filter-grid mt">
      <div class="field"><label for="fm">Grupo muscular</label><select id="fm" name="musculo"><option value="">Todos</option><?php foreach (ExerciseLibrary::MUSCLES as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $f['musculo']) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="fe">Equipamento</label><select id="fe" name="equipamento"><option value="">Todos</option><?php foreach (ExerciseLibrary::EQUIPMENT as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $f['equipamento']) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="fmod">Modalidade</label><select id="fmod" name="modalidade"><option value="">Todas</option><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $f['mod']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="fn">Nível</label><select id="fn" name="nivel"><option value="">Todos</option><?php foreach (ExerciseLibrary::DIFFICULTY as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $f['nivel']) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="fv">Vídeo</label><select id="fv" name="video"><option value="">Todos</option><?php foreach (ExerciseLibrary::VIDEO_STATUS as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $f['video']) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="row mt" style="gap:16px">
      <label class="check small"><input type="checkbox" name="favoritos" value="1"<?= checked($f['favoritos']) ?>> <span>Só favoritos</span></label>
      <label class="check small"><input type="checkbox" name="personalizados" value="1"<?= checked($f['personalizados']) ?>> <span>Só personalizados</span></label>
      <label class="check small"><input type="checkbox" name="arquivados" value="1"<?= checked($f['arquivados']) ?>> <span>Arquivados</span></label>
      <span class="grow"></span>
      <?php if ($activeFilters || $f['q'] !== ''): ?><a class="btn ghost sm" href="/painel/biblioteca">Limpar</a><?php endif; ?>
      <button class="btn sm" type="submit">Aplicar</button>
    </div>
  </details>
</form>

<p class="small muted mb"><?= plural($total, 'resultado', 'resultados') ?></p>
<?php if (!$rows): ?>
  <div class="card empty"><?= icon('book', 'icon-lg') ?><h3>Nenhum exercício encontrado</h3><p>Ajuste a busca ou os filtros, ou cadastre um exercício personalizado.</p></div>
<?php endif; ?>
<div class="lib-grid">
  <?php foreach ($rows as $ex): $fav = in_array((int) $ex['id'], $favorites, true); ?>
    <article class="lib-card">
      <a class="lib-card-link" href="/painel/biblioteca/<?= (int) $ex['id'] ?>">
        <?= ExerciseLibrary::thumb($ex) ?>
        <span class="grow" style="min-width:0">
          <span class="lib-name"><?= e($ex['name']) ?></span>
          <span class="lib-meta"><?= e(ExerciseLibrary::MUSCLES[$ex['primary_muscle'] ?? ''] ?? ($ex['category'] ?: $ex['modality_name'])) ?><?= $ex['equipment'] ? ' · ' . e(ExerciseLibrary::EQUIPMENT[$ex['equipment']] ?? '') : '' ?></span>
          <span class="lib-tags">
            <?php if ($ex['difficulty']): ?><span class="badge <?= e($levelTone[$ex['difficulty']] ?? '') ?>"><?= e(ExerciseLibrary::DIFFICULTY[$ex['difficulty']] ?? '') ?></span><?php endif; ?>
            <?php if ($ex['video_status'] === 'ok'): ?><span class="badge outline"><?= icon('video', 'icon-sm') ?> Vídeo</span><?php elseif ($ex['video_status'] === 'pending'): ?><span class="badge outline muted-badge"><?= icon('clock', 'icon-sm') ?> Vídeo pendente</span><?php endif; ?>
            <?php if ((int) $ex['is_custom']): ?><span class="badge outline">Personalizado</span><?php endif; ?>
          </span>
        </span>
      </a>
      <form method="post" action="/painel/biblioteca/<?= (int) $ex['id'] ?>/favorito" data-fav-form>
        <?= csrf_field() ?>
        <button class="fav-btn<?= $fav ? ' on' : '' ?>" type="submit" aria-pressed="<?= $fav ? 'true' : 'false' ?>" aria-label="<?= $fav ? 'Remover dos favoritos' : 'Favoritar' ?>: <?= e($ex['name']) ?>" title="Favorito"><?= icon('star', 'icon-sm') ?></button>
      </form>
    </article>
  <?php endforeach; ?>
</div>
<?= View::partial('pagination', ['page' => $page, 'pages' => $pages, 'base' => '/painel/biblioteca', 'query' => $query]) ?>
