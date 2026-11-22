<div class="page-head">
  <div><h1>Biblioteca de exercícios</h1><p><?= plural($count, 'exercício', 'exercícios') ?><?= $archived ? ' arquivados' : '' ?></p></div>
  <a class="btn" href="/painel/biblioteca/novo"><?= icon('plus', 'icon-sm') ?> Novo exercício</a>
</div>
<form class="card mb" method="get">
  <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
    <div class="field"><label for="lq">Buscar</label><input id="lq" type="search" name="q" value="<?= e($q) ?>" placeholder="Nome ou descrição"></div>
    <div class="field"><label for="lm">Modalidade</label><select id="lm" name="modalidade"><option value="">Todas</option><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $mod) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="lc">Categoria</label><select id="lc" name="categoria"><option value="">Todas</option><?php foreach ($categories as $c): ?><option<?= selected($c, $cat) ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="row between mt"><label class="check small"><input type="checkbox" name="arquivados" value="1"<?= checked($archived) ?>> <span>Mostrar arquivados</span></label><button class="btn sm" type="submit">Filtrar</button></div>
</form>
<?php if (!$grouped): ?>
  <div class="card empty"><?= icon('book', 'icon-lg') ?><h3>Nenhum exercício encontrado</h3><p>Cadastre exercícios com instruções e vídeos para usá-los nos treinos.</p></div>
<?php endif; ?>
<?php foreach ($grouped as $modName => $cats): ?>
  <div class="section">
    <p class="section-title"><?= e($modName) ?></p>
    <div class="card">
      <?php foreach ($cats as $catName => $list): ?>
        <p class="small strong mt"><?= e($catName) ?></p>
        <div class="list">
          <?php foreach ($list as $ex): ?>
            <div class="list-item">
              <a class="grow" href="/painel/biblioteca/<?= (int) $ex['id'] ?>" style="text-decoration:none">
                <div class="list-title"><?= e($ex['name']) ?></div>
                <div class="list-sub truncate"><?= e($ex['description'] ?: ($ex['instructions'] ? mb_strimwidth($ex['instructions'], 0, 90, '…') : 'Sem descrição')) ?></div>
              </a>
              <?php if ($ex['video_url'] || $ex['video_file_id']): ?><span class="badge outline"><?= icon('video', 'icon-sm') ?></span><?php endif; ?>
              <form method="post" action="/painel/biblioteca/<?= (int) $ex['id'] ?>/duplicar"><?= csrf_field() ?><button class="btn ghost sm icon-only" aria-label="Duplicar" title="Duplicar"><?= icon('copy', 'icon-sm') ?></button></form>
              <form method="post" action="/painel/biblioteca/<?= (int) $ex['id'] ?>/arquivar"><?= csrf_field() ?><?php if ($archived): ?><input type="hidden" name="restore" value="1"><button class="btn ghost sm">Restaurar</button><?php else: ?><button class="btn ghost sm icon-only" aria-label="Arquivar" title="Arquivar"><?= icon('archive', 'icon-sm') ?></button><?php endif; ?></form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
