<?php
$isTpl = (bool) $w['is_template'];
$editorData = [
    'items' => array_map(fn($it) => [
        'exercise_id' => $it['exercise_id'] ? (int) $it['exercise_id'] : null, 'name' => $it['name'], 'instructions' => $it['instructions'],
        'notes' => $it['notes'], 'video_url' => $it['video_url'], 'video_file_id' => $it['video_file_id'] ? (int) $it['video_file_id'] : null,
        'video_file_url' => $it['video_file_url'], 'params' => (object) $it['params'], 'fields' => $it['fields'],
    ], $items),
    'modality_id' => (int) $w['modality_id'],
    'modality_fields' => $modality['fields'],
    'catalog' => Modalities::FIELDS,
    'save_url' => '/painel/treinos/' . $w['id'] . '/salvar',
];
$back = $isTpl ? '/painel/modelos' : '/painel/alunos/' . $w['student_id'] . '?aba=treinos';
?>
<?php $who = $isTpl ? '' : first_name($student['name'] ?? 'aluno'); ?>
<div class="<?= e(mod_tone($modality)) ?>">
<a class="btn ghost sm" href="<?= e($back) ?>"><?= icon('chevron-left', 'icon-sm') ?> <?= $isTpl ? 'Modelos' : e($student['name'] ?? 'Aluno') ?></a>

<form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/dados" class="mt" data-meta-form>
  <?= csrf_field() ?>
  <label for="mn" class="sr-only">Nome do treino</label>
  <input id="mn" name="name" value="<?= e($w['name']) ?>" required class="title-input" placeholder="Nome do treino" style="width:100%">
  <div class="row mt" style="gap:8px">
    <label class="chip mod" style="padding:0 4px 0 12px;min-height:38px"><?= icon('dumbbell', 'icon-sm') ?>
      <select name="modality_id" aria-label="Modalidade" style="border:0;background-color:transparent;min-height:34px;padding:0 30px 0 4px;font-weight:650;color:var(--ink);width:auto"><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $w['modality_id']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select>
    </label>
    <?php if (!$isTpl): ?>
      <span style="width:1px;height:26px;background:var(--line)"></span>
      <div class="pills"><?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><label class="pill-check"><input type="checkbox" name="weekdays[]" value="<?= $d ?>"<?= checked(in_array($d, Workouts::weekdays($w), true)) ?>><span><?= PT_WEEKDAYS_SHORT[$d] ?></span></label><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
  <?php if (!$isTpl): ?>
    <details class="more mt"<?= $w['starts_on'] || $w['ends_on'] ? ' open' : '' ?>>
      <summary><?= icon('calendar', 'icon-sm') ?> Período e identificação (opcional)</summary>
      <div class="form-grid cols-3 mt">
        <div class="field"><label for="ml">Identificação</label><input id="ml" name="label" value="<?= e($w['label']) ?>" placeholder="Treino A"></div>
        <div class="field"><label for="ms">Início</label><input id="ms" type="date" name="starts_on" value="<?= e($w['starts_on']) ?>"></div>
        <div class="field"><label for="me">Fim</label><input id="me" type="date" name="ends_on" value="<?= e($w['ends_on']) ?>"></div>
      </div>
    </details>
  <?php endif; ?>
  <button class="btn secondary sm mt" type="submit" data-meta-save hidden><?= icon('check', 'icon-sm') ?> Salvar nome, modalidade e dias</button>
</form>

<div class="row between section" style="margin-bottom:10px"><h2>Exercícios</h2><span class="xs muted">Arraste para reordenar</span></div>
<div data-editor>
  <script type="application/json" data-editor-data><?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <div data-editor-list></div>
  <div class="empty card" data-editor-empty hidden><?= icon('dumbbell', 'icon-lg') ?><h3>Nenhum exercício ainda</h3><p>Toque em “Adicionar exercício” para buscar na biblioteca.</p></div>
  <div class="row mt">
    <button class="btn" type="button" data-open-dialog="library-dialog"><?= icon('plus', 'icon-sm') ?> Adicionar exercício</button>
    <button class="btn ghost sm" type="button" data-add-custom>ou criar um personalizado</button>
  </div>
  <div class="card mt-lg">
    <div class="field"><label for="gn">Orientações gerais</label><textarea id="gn" data-general-notes rows="2" placeholder="Aquecimento, objetivo da sessão, cuidados…"><?= e($wc['general_notes']) ?></textarea></div>
  </div>
</div>

<div class="sticky-actions">
  <span class="dot accent"></span>
  <div class="grow small">
    <b data-save-state>Rascunho salvo</b>
    <?php if (!$isTpl): ?><span class="muted"> · <?= $wc['status'] === 'scheduled' ? 'publicação agendada para ' . e(fmt_datetime($wc['publish_at'])) : ($published ? e($who) . ' ainda vê a versão anterior até você publicar' : 'ainda não publicado') ?></span><?php endif; ?>
  </div>
  <details class="menu-pop">
    <summary class="btn sm icon-only" aria-label="Mais ações"><?= icon('dots', 'icon-sm') ?></summary>
    <div class="menu-list">
      <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/modelo"><?= csrf_field() ?><input type="hidden" name="template_name" value="<?= e($w['name']) ?>"><button type="submit"><?= icon('layers', 'icon-sm') ?> Salvar como modelo</button></form>
      <button type="button" data-open-dialog="duplicate-dialog"><?= icon('copy', 'icon-sm') ?> Duplicar…</button>
      <?php if (!$isTpl): ?><button type="button" data-open-dialog="versions-dialog"><?= icon('clock', 'icon-sm') ?> Histórico de versões</button><?php endif; ?>
      <?php if (!$isTpl && $wc['status'] === 'scheduled'): ?><form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/desagendar"><?= csrf_field() ?><button type="submit"><?= icon('x', 'icon-sm') ?> Cancelar agendamento</button></form><?php endif; ?>
      <?php if (!$isTpl && $published): ?><form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/descartar-rascunho" data-confirm="Descartar as alterações do rascunho?"><?= csrf_field() ?><button type="submit"><?= icon('refresh', 'icon-sm') ?> Descartar rascunho</button></form><?php endif; ?>
      <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/arquivar" data-confirm="Arquivar? Ele sai da programação do aluno, mas o histórico é preservado."><?= csrf_field() ?><button type="submit" class="danger"><?= icon('archive', 'icon-sm') ?> Arquivar</button></form>
    </div>
  </details>
  <?php if (!$isTpl): ?>
    <button type="button" class="btn sm" data-open-dialog="schedule-dialog"><?= icon('clock', 'icon-sm') ?> Agendar</button>
    <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/publicar" data-publish-form>
      <?= csrf_field() ?><input type="hidden" name="when" value="now">
      <button class="btn pw" type="submit"><?= icon('send', 'icon-sm') ?> Publicar para <?= e($who) ?></button>
    </form>
  <?php endif; ?>
</div>
</div>

<?php if (!$isTpl): ?>
<dialog id="schedule-dialog">
  <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/publicar" data-publish-form>
    <?= csrf_field() ?><input type="hidden" name="when" value="schedule">
    <div class="dialog-head"><h3>Agendar publicação</h3><button class="btn ghost sm icon-only" type="button" data-close-dialog aria-label="Fechar"><?= icon('x', 'icon-sm') ?></button></div>
    <div class="dialog-body">
      <div class="field"><label for="pa">Publicar em</label><input id="pa" type="datetime-local" name="publish_at" required value="<?= $wc['publish_at'] ? e(to_local($wc['publish_at'])->format('Y-m-d\TH:i')) : '' ?>" min="<?= e(local_now()->format('Y-m-d\TH:i')) ?>"></div>
      <p class="hint mt"><?= e($who) ?> recebe um e-mail quando a nova versão for publicada.</p>
    </div>
    <div class="dialog-foot"><button class="btn ghost" type="button" data-close-dialog>Cancelar</button><button class="btn pw" type="submit">Agendar</button></div>
  </form>
</dialog>
<dialog id="versions-dialog">
  <div class="dialog-head"><h3>Histórico de versões</h3><button class="btn ghost sm icon-only" type="button" data-close-dialog aria-label="Fechar"><?= icon('x', 'icon-sm') ?></button></div>
  <div class="dialog-body"><div class="list">
    <?php foreach ($versions as $v): ?>
      <a class="list-item small" href="/painel/treinos/<?= (int) $w['id'] ?>/versoes/<?= (int) $v['id'] ?>">
        <span class="strong">v<?= (int) $v['version_no'] ?></span>
        <span class="grow muted"><?= $v['published_at'] ? 'publicada ' . e(fmt_datetime($v['published_at'])) : ($v['status'] === 'scheduled' ? 'agendada ' . e(fmt_datetime($v['publish_at'])) : 'rascunho') ?></span>
        <span class="badge <?= ['published' => 'ok', 'scheduled' => 'warn', 'draft' => '', 'superseded' => 'outline'][$v['status']] ?>"><?= ['published' => 'Atual', 'scheduled' => 'Agendada', 'draft' => 'Rascunho', 'superseded' => 'Anterior'][$v['status']] ?></span>
      </a>
    <?php endforeach; ?>
  </div></div>
</dialog>
<?php endif; ?>
<dialog id="duplicate-dialog">
  <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/duplicar">
    <?= csrf_field() ?>
    <div class="dialog-head"><h3>Duplicar treino</h3><button class="btn ghost sm icon-only" type="button" data-close-dialog aria-label="Fechar"><?= icon('x', 'icon-sm') ?></button></div>
    <div class="dialog-body"><div class="field"><label for="ds">Para quem?</label><select id="ds" name="student_id"><?php if ($isTpl): ?><option value="">Novo modelo</option><?php endif; ?><?php foreach ($students as $st): ?><option value="<?= (int) $st['id'] ?>"<?= selected($st['id'], $w['student_id']) ?>><?= e($st['name']) ?><?= (int) $st['id'] === (int) $w['student_id'] ? ' (este aluno)' : '' ?></option><?php endforeach; ?></select></div></div>
    <div class="dialog-foot"><button class="btn ghost" type="button" data-close-dialog>Cancelar</button><button class="btn" type="submit"><?= icon('copy', 'icon-sm') ?> Duplicar</button></div>
  </form>
</dialog>

<dialog id="library-dialog">
  <div class="dialog-head"><h3>Adicionar exercício</h3><button class="btn ghost sm icon-only" type="button" data-close-dialog aria-label="Fechar"><?= icon('x', 'icon-sm') ?></button></div>
  <div class="dialog-body">
    <div class="row nowrap">
      <input type="search" placeholder="Buscar exercício" data-lib-q aria-label="Buscar exercício" class="grow">
      <label class="check small" style="white-space:nowrap"><input type="checkbox" data-lib-all> <span>Todas as modalidades</span></label>
    </div>
    <div class="lib-results" data-lib-results><div class="loading-block">Carregando</div></div>
    <button type="button" class="btn ghost block mt" data-lib-create><?= icon('plus', 'icon-sm') ?> <span>Criar exercício personalizado</span></button>
    <p class="hint mt">Mudanças no treino não alteram o exercício original da biblioteca.</p>
  </div>
</dialog>

<template data-item-template>
  <div class="editor-item" draggable="true">
    <div class="editor-item-head">
      <span class="drag-handle" aria-hidden="true" title="Arrastar para reordenar"><?= icon('grip', 'icon-sm') ?></span>
      <span class="exercise-num" data-num style="width:30px;height:30px"></span>
      <input class="item-name grow" data-f="name" placeholder="Nome do exercício" aria-label="Nome do exercício">
      <button type="button" class="btn ghost sm icon-only" data-move="-1" aria-label="Mover para cima"><?= icon('arrow-up', 'icon-sm') ?></button>
      <button type="button" class="btn ghost sm icon-only" data-move="1" aria-label="Mover para baixo"><?= icon('arrow-down', 'icon-sm') ?></button>
      <button type="button" class="btn ghost sm icon-only" data-remove-item aria-label="Remover exercício"><?= icon('trash', 'icon-sm') ?></button>
    </div>
    <div class="editor-item-body">
      <div class="param-grid" data-params></div>
      <details>
        <summary class="details-toggle"><?= icon('chevron-down', 'icon-sm') ?> Mais detalhes (instruções, vídeo, campos)</summary>
        <div class="stack mt">
          <div class="field"><label>Instruções específicas para este aluno</label><textarea rows="2" data-f="instructions"></textarea></div>
          <div class="field"><label>Observações individuais</label><textarea rows="2" data-f="notes"></textarea></div>
          <div class="form-grid cols-2">
            <div class="field"><label>Link de vídeo (YouTube/Vimeo)</label><input type="url" data-f="video_url" placeholder="https://"></div>
            <div class="field"><label>Ou enviar vídeo</label><input type="file" accept="video/*" data-video-upload><span class="hint" data-video-status></span></div>
          </div>
          <div class="field"><span class="label">Campos exibidos</span><div class="pills" data-fields></div></div>
        </div>
      </details>
    </div>
  </div>
</template>
