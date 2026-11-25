<?php
$v = fn(string $k) => old($k, $e[$k] ?? '');
$embed = video_embed_url($e['video_url'] ?? null);
$sec = (array) old('secondary_muscles', array_filter(explode(',', (string) ($e['secondary_muscles'] ?? ''))));
$hasVideo = !empty($e['video_url']) || !empty($e['video_file_id']);
?>
<div class="page-head">
  <div><p class="muted small"><a href="/painel/biblioteca">Biblioteca</a><?php if ($e): ?> · <a href="/painel/biblioteca/<?= (int) $e['id'] ?>"><?= e($e['name']) ?></a><?php endif; ?></p><h1><?= $e ? 'Editar exercício' : 'Novo exercício' ?></h1><?php if ($e && $usage): ?><p>Usado em <?= plural($usage, 'treino', 'treinos') ?>. Editar aqui não muda treinos já montados.</p><?php endif; ?></div>
</div>
<form class="grid grid-main" method="post" action="<?= $e ? '/painel/biblioteca/' . (int) $e['id'] : '/painel/biblioteca' ?>" enctype="multipart/form-data" data-loading>
  <?= csrf_field() ?>
  <div class="stack">
    <div class="card">
      <div class="form-grid cols-2">
        <div class="field"><label for="n" class="req">Nome em português</label><input id="n" name="name" value="<?= e($v('name')) ?>" required maxlength="150"></div>
        <div class="field"><label for="ne">Nome em inglês</label><input id="ne" name="name_en" value="<?= e($v('name_en')) ?>" maxlength="150" lang="en"></div>
        <div class="field"><label for="m" class="req">Modalidade</label><select id="m" name="modality_id" data-modality-select><?php foreach ($modalities as $md): ?><option value="<?= (int) $md['id'] ?>" data-fields="<?= e(implode(',', $md['fields'])) ?>"<?= selected($md['id'], $v('modality_id')) ?>><?= e($md['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="c">Categoria</label><input id="c" name="category" value="<?= e($v('category')) ?>" list="cats" placeholder="Preenchida pelo grupo muscular, se vazia"><datalist id="cats"><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></div>
        <div class="field"><label for="pm">Grupo muscular principal</label><select id="pm" name="primary_muscle"><option value="">—</option><?php foreach (ExerciseLibrary::MUSCLES as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $v('primary_muscle')) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="eq">Equipamento</label><select id="eq" name="equipment"><option value="">—</option><?php foreach (ExerciseLibrary::EQUIPMENT as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $v('equipment')) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="df">Nível</label><select id="df" name="difficulty"><option value="">—</option><?php foreach (ExerciseLibrary::DIFFICULTY as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($k, $v('difficulty')) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><span class="label">Grupos musculares secundários</span><div class="pills mt"><?php foreach (ExerciseLibrary::MUSCLES as $k => $l): ?><label class="pill-check"><input type="checkbox" name="secondary_muscles[]" value="<?= e($k) ?>"<?= checked(in_array($k, $sec, true)) ?>><span><?= e($l) ?></span></label><?php endforeach; ?></div></div>
    </div>
    <div class="card">
      <div class="field"><label for="d">Descrição</label><textarea id="d" name="description" rows="3"><?= e($v('description')) ?></textarea></div>
      <div class="field"><label for="i">Orientações de execução</label><textarea id="i" name="instructions" rows="6" placeholder="Uma orientação por linha"><?= e($v('instructions')) ?></textarea></div>
      <div class="field"><label for="mi">Cuidados e erros comuns</label><textarea id="mi" name="mistakes" rows="4" placeholder="Um por linha"><?= e($v('mistakes')) ?></textarea></div>
      <div class="field"><label for="o">Observações gerais</label><textarea id="o" name="notes" rows="2"><?= e($v('notes')) ?></textarea></div>
    </div>
  </div>
  <div class="stack">
    <div class="card">
      <h3>Vídeo demonstrativo</h3>
      <div class="field mt"><label for="vu">Link do YouTube ou Vimeo</label><input id="vu" type="url" name="video_url" value="<?= e($v('video_url')) ?>" placeholder="https://"></div>
      <div class="field"><label for="vf">Ou envie um vídeo</label><input id="vf" type="file" name="video" accept="video/mp4,video/webm,video/quicktime"><span class="hint">MP4, WebM ou MOV · até <?= (int) Settings::get('upload_video_mb') ?> MB</span></div>
      <?php if (!empty($e['video_file_id'])): ?>
        <div class="video-embed"><video controls preload="metadata" playsinline src="<?= e(Files::url((int) $e['video_file_id'])) ?>"></video></div>
        <label class="check mt"><input type="checkbox" name="remove_video" value="1"> <span>Remover vídeo enviado</span></label>
      <?php elseif ($embed): ?>
        <div class="video-embed"><iframe src="<?= e($embed) ?>" title="Vídeo" allowfullscreen loading="lazy"></iframe></div>
      <?php endif; ?>
      <label class="check mt"><input type="checkbox" name="video_confirmed" value="1"<?= checked($hasVideo && ($e['video_status'] ?? '') === 'ok') ?>> <span class="small">Confirmo que o vídeo mostra exatamente este exercício, com execução correta, e que tenho autorização para usá-lo.</span></label>
      <p class="hint mt">Sem esta confirmação, o vídeo fica marcado como <b>pendente de revisão</b>.</p>
    </div>
    <div class="card">
      <h3>Imagem de capa</h3>
      <?php if (!empty($e['image_file_id'])): ?><div class="row mt"><?= ExerciseLibrary::thumb($e, 'lg') ?><label class="check"><input type="checkbox" name="remove_image" value="1"> <span>Remover imagem</span></label></div><?php endif; ?>
      <div class="field mt"><label for="im">Enviar imagem</label><input id="im" type="file" name="image" accept="image/jpeg,image/png,image/webp"><span class="hint">Sem imagem, a biblioteca mostra o ícone do equipamento.</span></div>
    </div>
    <div class="card">
      <h3>Origem e licença</h3>
      <div class="field mt"><label for="so">Origem do conteúdo</label><input id="so" name="source" value="<?= e($v('source')) ?>" maxlength="255" placeholder="Ex.: vídeo gravado pelo Renan"></div>
      <div class="field"><label for="li">Licença / autorização</label><input id="li" name="license" value="<?= e($v('license')) ?>" maxlength="255" placeholder="Ex.: uso autorizado pelo autor"></div>
    </div>
    <div class="card">
      <h3>Campos de prescrição</h3>
      <p class="hint mt">Deixe tudo desmarcado para usar o padrão da modalidade.</p>
      <div class="pills mt"><?php foreach ($fieldsCatalog as $k => $meta): ?><label class="pill-check"><input type="checkbox" name="fields[]" value="<?= e($k) ?>"<?= checked(in_array($k, $fields, true) && !empty($e['fields']) && json_decode_array($e['fields'])) ?>><span><?= e($meta['label']) ?></span></label><?php endforeach; ?></div>
    </div>
    <button class="btn" type="submit">Salvar exercício</button>
  </div>
</form>
