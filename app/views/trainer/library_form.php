<?php
$v = fn(string $k) => old($k, $e[$k] ?? '');
$embed = video_embed_url($e['video_url'] ?? null);
?>
<div class="page-head">
  <div><p class="muted small"><a href="/painel/biblioteca">Biblioteca</a></p><h1><?= $e ? e($e['name']) : 'Novo exercício' ?></h1><?php if ($e && $usage): ?><p>Usado em <?= plural($usage, 'treino', 'treinos') ?>. Editar aqui não muda treinos já montados.</p><?php endif; ?></div>
</div>
<form class="grid grid-main" method="post" action="<?= $e ? '/painel/biblioteca/' . (int) $e['id'] : '/painel/biblioteca' ?>" enctype="multipart/form-data" data-loading>
  <?= csrf_field() ?>
  <div class="card">
    <div class="form-grid cols-2">
      <div class="field span-2"><label for="n" class="req">Nome</label><input id="n" name="name" value="<?= e($v('name')) ?>" required></div>
      <div class="field"><label for="m" class="req">Modalidade</label><select id="m" name="modality_id" data-modality-select><?php foreach ($modalities as $md): ?><option value="<?= (int) $md['id'] ?>" data-fields="<?= e(implode(',', $md['fields'])) ?>"<?= selected($md['id'], $v('modality_id')) ?>><?= e($md['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="c">Categoria</label><input id="c" name="category" value="<?= e($v('category')) ?>" list="cats" placeholder="Ex.: Membros inferiores"><datalist id="cats"><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></div>
    </div>
    <div class="field"><label for="d">Descrição</label><textarea id="d" name="description" rows="2"><?= e($v('description')) ?></textarea></div>
    <div class="field"><label for="i">Instruções de execução</label><textarea id="i" name="instructions" rows="5"><?= e($v('instructions')) ?></textarea></div>
    <div class="field"><label for="o">Observações gerais</label><textarea id="o" name="notes" rows="2"><?= e($v('notes')) ?></textarea></div>
  </div>
  <div class="stack">
    <div class="card">
      <h3>Vídeo demonstrativo</h3>
      <div class="field mt"><label for="vu">Link externo (YouTube, Vimeo…)</label><input id="vu" type="url" name="video_url" value="<?= e($v('video_url')) ?>" placeholder="https://"></div>
      <div class="field"><label for="vf">Ou envie um vídeo</label><input id="vf" type="file" name="video" accept="video/*"><span class="hint">MP4, WebM ou MOV · até <?= (int) Settings::get('upload_video_mb') ?> MB</span></div>
      <?php if (!empty($e['video_file_id'])): ?>
        <div class="video-embed"><video controls preload="metadata" src="<?= e(Files::url((int) $e['video_file_id'])) ?>"></video></div>
        <label class="check mt"><input type="checkbox" name="remove_video" value="1"> <span>Remover vídeo enviado</span></label>
      <?php elseif ($embed): ?>
        <div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo" allowfullscreen loading="lazy"></iframe></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3>Campos de prescrição</h3>
      <p class="hint mt">Deixe tudo desmarcado para usar o padrão da modalidade.</p>
      <div class="pills mt"><?php foreach ($fieldsCatalog as $k => $meta): ?><label class="pill-check"><input type="checkbox" name="fields[]" value="<?= e($k) ?>"<?= checked(in_array($k, $fields, true) && !empty($e['fields']) && json_decode_array($e['fields'])) ?>><span><?= e($meta['label']) ?></span></label><?php endforeach; ?></div>
    </div>
    <button class="btn" type="submit">Salvar exercício</button>
  </div>
</form>
