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
<div class="page-head">
  <div>
    <p class="muted small"><a href="<?= e($back) ?>"><?= $isTpl ? 'Modelos de treino' : e($student['name'] ?? 'Aluno') ?></a> / <?= $isTpl ? 'Modelo' : 'Treino' ?></p>
    <h1><?= e(Workouts::title($w)) ?></h1>
    <p class="row small" style="gap:6px">
      <span><?= e($modality['name']) ?></span>
      <?php if (!$isTpl): ?><span>· <?= e(weekday_names(Workouts::weekdays($w))) ?></span><?php endif; ?>
      <?php if ($published): ?><span class="badge ok">Publicado: v<?= (int) $published['version_no'] ?></span><?php elseif (!$isTpl): ?><span class="badge outline">Ainda não publicado</span><?php endif; ?>
      <?php if ($wc['status'] === 'scheduled'): ?><span class="badge warn">v<?= (int) $wc['version_no'] ?> agendada: <?= e(fmt_datetime($wc['publish_at'])) ?></span><?php else: ?><span class="badge">Editando rascunho v<?= (int) $wc['version_no'] ?></span><?php endif; ?>
    </p>
  </div>
  <span class="save-state" data-save-state>Alterações salvas automaticamente</span>
</div>

<div class="grid grid-main">
  <div>
    <?php if ($published && !$isTpl): ?>
      <div class="callout mb small"><?= icon('eye') ?><p>O aluno continua vendo a versão publicada (v<?= (int) $published['version_no'] ?>) até você publicar este rascunho. Registros anteriores do aluno não são alterados.</p></div>
    <?php endif; ?>

    <div data-editor>
      <script type="application/json" data-editor-data><?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
      <div data-editor-list></div>
      <div class="empty card" data-editor-empty hidden><?= icon('dumbbell', 'icon-lg') ?><h3>Nenhum exercício ainda</h3><p>Adicione exercícios da biblioteca ou crie um personalizado.</p></div>
      <div class="btn-row mt">
        <button class="btn secondary" type="button" data-open-dialog="library-dialog"><?= icon('book', 'icon-sm') ?> Adicionar da biblioteca</button>
        <button class="btn ghost" type="button" data-add-custom><?= icon('plus', 'icon-sm') ?> Exercício personalizado</button>
      </div>
      <div class="card mt-lg">
        <div class="field"><label for="gn">Orientações gerais do treino</label><textarea id="gn" data-general-notes rows="3" placeholder="Aquecimento, objetivo da sessão, cuidados…"><?= e($wc['general_notes']) ?></textarea></div>
      </div>
    </div>

    <?php if (!$isTpl): ?>
    <form class="card mt-lg" method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/publicar" data-publish-form>
      <?= csrf_field() ?>
      <h3>Publicação</h3>
      <div class="pills mt">
        <label class="pill-check"><input type="radio" name="when" value="now" checked data-when><span>Publicar agora</span></label>
        <label class="pill-check"><input type="radio" name="when" value="schedule" data-when><span>Agendar publicação</span></label>
      </div>
      <div class="field" data-schedule-field hidden><label for="pa">Data e horário</label><input id="pa" type="datetime-local" name="publish_at" value="<?= $wc['publish_at'] ? e(to_local($wc['publish_at'])->format('Y-m-d\TH:i')) : '' ?>" min="<?= e(local_now()->format('Y-m-d\TH:i')) ?>"></div>
      <p class="hint mt">O aluno recebe um e-mail quando a nova versão é publicada. A versão anterior continua disponível até lá.</p>
      <div class="btn-row mt"><button class="btn accent" type="submit"><?= icon('send', 'icon-sm') ?> Confirmar</button></div>
    </form>
    <div class="btn-row mt">
      <?php if ($wc['status'] === 'scheduled'): ?><form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/desagendar"><?= csrf_field() ?><button class="btn ghost sm">Cancelar agendamento</button></form><?php endif; ?>
      <?php if ($published): ?><form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/descartar-rascunho" data-confirm="Descartar as alterações do rascunho?"><?= csrf_field() ?><button class="btn ghost sm">Descartar rascunho</button></form><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <form class="card" method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/dados">
      <?= csrf_field() ?>
      <h3>Dados do <?= $isTpl ? 'modelo' : 'treino' ?></h3>
      <?php if (!$isTpl): ?><div class="field mt"><label for="ml">Identificação</label><input id="ml" name="label" value="<?= e($w['label']) ?>" placeholder="Treino A"></div><?php endif; ?>
      <div class="field<?= $isTpl ? ' mt' : '' ?>"><label for="mn">Nome</label><input id="mn" name="name" value="<?= e($w['name']) ?>" required></div>
      <div class="field"><label for="mm">Modalidade</label><select id="mm" name="modality_id"><?php foreach ($modalities as $m): ?><option value="<?= (int) $m['id'] ?>"<?= selected($m['id'], $w['modality_id']) ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
      <?php if (!$isTpl): ?>
        <div class="field"><span class="label">Dias da semana</span><div class="pills"><?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><label class="pill-check"><input type="checkbox" name="weekdays[]" value="<?= $d ?>"<?= checked(in_array($d, Workouts::weekdays($w), true)) ?>><span><?= PT_WEEKDAYS_SHORT[$d] ?></span></label><?php endforeach; ?></div></div>
        <div class="form-grid cols-2 mt">
          <div class="field"><label for="ms">Início</label><input id="ms" type="date" name="starts_on" value="<?= e($w['starts_on']) ?>"></div>
          <div class="field"><label for="me">Fim</label><input id="me" type="date" name="ends_on" value="<?= e($w['ends_on']) ?>"></div>
        </div>
      <?php endif; ?>
      <button class="btn secondary sm mt" type="submit">Salvar dados</button>
    </form>

    <div class="card">
      <h3>Ações</h3>
      <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/modelo" class="mt">
        <?= csrf_field() ?>
        <div class="field"><label for="tn">Salvar como modelo reutilizável</label><input id="tn" name="template_name" value="<?= e($w['name']) ?>"></div>
        <button class="btn secondary sm mt" type="submit"><?= icon('layers', 'icon-sm') ?> Salvar modelo</button>
      </form>
      <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/duplicar" class="mt-lg">
        <?= csrf_field() ?>
        <div class="field"><label for="ds">Duplicar para</label><select id="ds" name="student_id"><?php if ($isTpl): ?><option value="">Novo modelo</option><?php endif; ?><?php foreach ($students as $st): ?><option value="<?= (int) $st['id'] ?>"<?= selected($st['id'], $w['student_id']) ?>><?= e($st['name']) ?><?= (int) $st['id'] === (int) $w['student_id'] ? ' (este aluno)' : '' ?></option><?php endforeach; ?></select></div>
        <button class="btn secondary sm mt" type="submit"><?= icon('copy', 'icon-sm') ?> Duplicar</button>
      </form>
      <form method="post" action="/painel/treinos/<?= (int) $w['id'] ?>/arquivar" class="mt-lg" data-confirm="Arquivar? Ele sai da programação do aluno, mas o histórico é preservado.">
        <?= csrf_field() ?><button class="btn danger sm" type="submit"><?= icon('archive', 'icon-sm') ?> Arquivar</button>
      </form>
    </div>

    <?php if (!$isTpl): ?>
    <div class="card">
      <h3>Histórico de versões</h3>
      <div class="list mt">
        <?php foreach ($versions as $v): ?>
          <a class="list-item small" href="/painel/treinos/<?= (int) $w['id'] ?>/versoes/<?= (int) $v['id'] ?>">
            <span class="strong">v<?= (int) $v['version_no'] ?></span>
            <span class="grow muted"><?= $v['published_at'] ? 'publicada ' . e(fmt_datetime($v['published_at'])) : ($v['status'] === 'scheduled' ? 'agendada ' . e(fmt_datetime($v['publish_at'])) : 'rascunho') ?></span>
            <span class="badge <?= ['published' => 'ok', 'scheduled' => 'warn', 'draft' => '', 'superseded' => 'outline'][$v['status']] ?>"><?= ['published' => 'Atual', 'scheduled' => 'Agendada', 'draft' => 'Rascunho', 'superseded' => 'Anterior'][$v['status']] ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<dialog id="library-dialog">
  <div class="dialog-head"><h3>Biblioteca de exercícios</h3><button class="btn ghost sm icon-only" type="button" data-close-dialog aria-label="Fechar"><?= icon('x', 'icon-sm') ?></button></div>
  <div class="dialog-body">
    <div class="row nowrap">
      <input type="search" placeholder="Buscar exercício" data-lib-q aria-label="Buscar exercício" class="grow">
      <label class="check small" style="white-space:nowrap"><input type="checkbox" data-lib-all> <span>Todas as modalidades</span></label>
    </div>
    <div class="lib-results" data-lib-results><div class="loading-block">Carregando</div></div>
    <p class="hint mt">Mudanças feitas no treino do aluno não alteram o exercício original da biblioteca. <a href="/painel/biblioteca/novo" target="_blank">Cadastrar novo exercício</a></p>
  </div>
</dialog>

<template data-item-template>
  <div class="editor-item" draggable="true">
    <div class="editor-item-head">
      <span class="drag-handle" aria-hidden="true" title="Arrastar para reordenar"><?= icon('grip', 'icon-sm') ?></span>
      <span class="exercise-num" data-num></span>
      <input class="item-name grow" data-f="name" placeholder="Nome do exercício" aria-label="Nome do exercício">
      <button type="button" class="btn ghost sm icon-only" data-move="-1" aria-label="Mover para cima"><?= icon('arrow-up', 'icon-sm') ?></button>
      <button type="button" class="btn ghost sm icon-only" data-move="1" aria-label="Mover para baixo"><?= icon('arrow-down', 'icon-sm') ?></button>
      <button type="button" class="btn ghost sm icon-only" data-remove-item aria-label="Remover exercício"><?= icon('trash', 'icon-sm') ?></button>
    </div>
    <div class="editor-item-body">
      <div class="param-grid" data-params></div>
      <details>
        <summary class="details-toggle"><?= icon('chevron-down', 'icon-sm') ?> Instruções, vídeo, observações e campos</summary>
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
