<?php
/** @var array $e @var bool $favorite @var int $usage */
$embed = video_embed_url($e['video_url'] ?? null);
$secondary = ExerciseLibrary::secondaryNames($e['secondary_muscles'] ?? null);
$steps = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $e['instructions']) ?: [])));
$mistakes = array_values(array_filter(array_map(fn($l) => ltrim(trim($l), '•- '), preg_split('/\R/', (string) ($e['mistakes'] ?? '')) ?: [])));
?>
<a class="btn ghost sm" href="/painel/biblioteca"><?= icon('chevron-left', 'icon-sm') ?> Biblioteca</a>
<div class="ex-hero mt">
  <?= ExerciseLibrary::thumb($e, 'lg') ?>
  <div class="grow">
    <p class="kicker"><?= e($e['modality_name']) ?><?= $e['category'] ? ' · ' . e($e['category']) : '' ?></p>
    <h1><?= e($e['name']) ?></h1>
    <?php if ($e['name_en']): ?><p class="muted small" lang="en"><?= e($e['name_en']) ?></p><?php endif; ?>
    <div class="row mt" style="gap:8px">
      <form method="post" action="/painel/biblioteca/<?= (int) $e['id'] ?>/favorito" data-fav-form><?= csrf_field() ?><button class="btn secondary sm fav-toggle<?= $favorite ? ' on' : '' ?>" type="submit" aria-pressed="<?= $favorite ? 'true' : 'false' ?>"><?= icon('star', 'icon-sm') ?> <span><?= $favorite ? 'Favorito' : 'Favoritar' ?></span></button></form>
      <a class="btn secondary sm" href="/painel/biblioteca/<?= (int) $e['id'] ?>/editar"><?= icon('edit', 'icon-sm') ?> Editar</a>
      <details class="menu-pop">
        <summary class="btn ghost sm icon-only" aria-label="Mais ações"><?= icon('dots', 'icon-sm') ?></summary>
        <div class="menu-list">
          <form method="post" action="/painel/biblioteca/<?= (int) $e['id'] ?>/duplicar"><?= csrf_field() ?><button type="submit"><?= icon('copy', 'icon-sm') ?> Duplicar como personalizado</button></form>
          <form method="post" action="/painel/biblioteca/<?= (int) $e['id'] ?>/arquivar"<?= $e['archived_at'] ? '' : ' data-confirm="Arquivar este exercício? Treinos que já o usam não mudam."' ?>><?= csrf_field() ?><?php if ($e['archived_at']): ?><input type="hidden" name="restore" value="1"><button type="submit"><?= icon('refresh', 'icon-sm') ?> Restaurar</button><?php else: ?><button type="submit" class="danger"><?= icon('archive', 'icon-sm') ?> Arquivar</button><?php endif; ?></form>
        </div>
      </details>
    </div>
  </div>
</div>

<div class="grid grid-main section">
  <div class="stack">
    <div class="card">
      <h3>Vídeo demonstrativo</h3>
      <?php if (!empty($e['video_file_id'])): ?>
        <div class="video-embed mt"><video controls preload="metadata" playsinline src="<?= e(Files::url((int) $e['video_file_id'])) ?>"></video></div>
      <?php elseif ($embed): ?>
        <div class="video-embed mt"><iframe src="<?= e($embed) ?>" title="Vídeo: <?= e($e['name']) ?>" allow="encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
      <?php endif; ?>
      <?php if (($e['video_status'] ?? 'none') === 'pending'): ?>
        <div class="callout mt"><?= icon('clock') ?><div class="small"><b>Vídeo pendente de revisão.</b> <?= ($e['video_url'] || $e['video_file_id']) ? 'Confirme na edição que o vídeo mostra exatamente este exercício e que você tem autorização para usá-lo.' : 'Adicione um vídeo próprio ou um link autorizado do YouTube/Vimeo.' ?> <a class="link" href="/painel/biblioteca/<?= (int) $e['id'] ?>/editar">Adicionar vídeo</a></div></div>
      <?php elseif (($e['video_status'] ?? 'none') === 'none'): ?>
        <p class="small muted mt">Sem vídeo. <a class="link" href="/painel/biblioteca/<?= (int) $e['id'] ?>/editar">Adicionar</a></p>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3>Como executar</h3>
      <?php if ($e['description']): ?><p class="small muted mt"><?= e($e['description']) ?></p><?php endif; ?>
      <?php if ($steps): ?><ol class="ex-steps mt"><?php foreach ($steps as $s): ?><li><?= e(preg_replace('/^\d+\.\s*/', '', $s)) ?></li><?php endforeach; ?></ol><?php else: ?><p class="small muted mt">Sem orientações cadastradas.</p><?php endif; ?>
    </div>
    <?php if ($mistakes): ?>
      <div class="card">
        <h3>Cuidados e erros comuns</h3>
        <ul class="ex-mistakes mt"><?php foreach ($mistakes as $m): ?><li><?= icon('alert', 'icon-sm') ?><span><?= e($m) ?></span></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <?php if ($e['notes']): ?><div class="card"><h3>Observações</h3><p class="small pre mt"><?= e($e['notes']) ?></p></div><?php endif; ?>
  </div>
  <div class="stack">
    <div class="card">
      <dl class="ex-facts">
        <div><dt>Músculo principal</dt><dd><?= e(ExerciseLibrary::MUSCLES[$e['primary_muscle'] ?? ''] ?? '—') ?></dd></div>
        <div><dt>Músculos secundários</dt><dd><?= $secondary ? e(implode(', ', $secondary)) : '—' ?></dd></div>
        <div><dt>Equipamento</dt><dd><?= e(ExerciseLibrary::EQUIPMENT[$e['equipment'] ?? ''] ?? '—') ?></dd></div>
        <div><dt>Nível</dt><dd><?= e(ExerciseLibrary::DIFFICULTY[$e['difficulty'] ?? ''] ?? '—') ?></dd></div>
        <div><dt>Uso em treinos</dt><dd><?= $usage ? plural($usage, 'treino', 'treinos') : 'Ainda não usado' ?></dd></div>
        <div><dt>Tipo</dt><dd><?= (int) $e['is_custom'] ? 'Personalizado' : 'Catálogo do Wruch Personal' ?></dd></div>
      </dl>
    </div>
    <div class="card">
      <h3 class="small">Origem e licença</h3>
      <p class="xs muted mt"><?= e($e['source'] ?: 'Não informada.') ?></p>
      <p class="xs muted mt"><?= e($e['license'] ?: 'Não informada.') ?></p>
      <p class="xs muted mt">Criado em <?= e(fmt_datetime($e['created_at'])) ?> · atualizado em <?= e(fmt_datetime($e['updated_at'])) ?></p>
    </div>
  </div>
</div>
