<a class="btn ghost sm" href="/painel/mais"><?= icon('chevron-left', 'icon-sm') ?> Mais</a>
<div class="page-head mt"><div><h1>Biblioteca</h1><p>Modelos reutilizáveis para criar treinos de qualquer aluno.</p></div></div>
<nav class="tabs"><a href="/painel/biblioteca">Exercícios</a><a href="/painel/modelos" class="active">Modelos de treino</a></nav>
<div class="grid grid-main">
  <div>
    <?php if (!$templates): ?>
      <div class="card empty"><?= icon('layers', 'icon-lg') ?><h3>Nenhum modelo ainda</h3><p>Crie um modelo ao lado ou salve qualquer treino como modelo pelo editor.</p></div>
    <?php else: ?>
      <div class="card"><div class="list">
        <?php foreach ($templates as $t): ?>
          <a class="list-item" href="/painel/treinos/<?= (int) $t['id'] ?>"><?= icon('layers') ?><div class="grow"><div class="list-title"><?= e($t['name']) ?></div><div class="list-sub"><?= e($t['modality_name']) ?> · <?= plural((int) $t['items_count'], 'exercício', 'exercícios') ?></div></div><?= icon('chevron-right', 'icon-sm') ?></a>
        <?php endforeach; ?>
      </div></div>
    <?php endif; ?>
  </div>
  <form class="card" method="post" action="/painel/modelos" style="align-self:start">
    <?= csrf_field() ?>
    <h3>Novo modelo</h3>
    <div class="field mt"><label for="tn" class="req">Nome</label><input id="tn" name="name" required placeholder="Ex.: Base — força geral"></div>
    <div class="field"><label for="tm" class="req">Modalidade</label><select id="tm" name="modality_id"><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
    <button class="btn mt" type="submit"><?= icon('plus', 'icon-sm') ?> Criar e editar</button>
  </form>
</div>
