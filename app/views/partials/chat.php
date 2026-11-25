<?php /** @var int $studentId @var string $peerName @var ?string $backHref */ ?>
<section class="chat" data-chat data-student="<?= (int) $studentId ?>" data-endpoint="/api/mensagens/<?= (int) $studentId ?>">
  <header class="chat-head">
    <?php if (!empty($backHref)): ?><a class="btn ghost sm icon-only" href="<?= e($backHref) ?>" aria-label="Voltar"><?= icon('chevron-left', 'icon-sm') ?></a><?php endif; ?>
    <span class="avatar sm"><?= e(initials($peerName)) ?></span>
    <div class="grow"><div class="strong"><?= e($peerName) ?></div><div class="xs muted" data-chat-status>Conversa privada</div></div>
  </header>
  <div class="chat-log" data-chat-log role="log" aria-live="polite" aria-label="Mensagens">
    <div class="loading-block">Carregando mensagens</div>
  </div>
  <div class="chat-attach-preview" data-attach-preview><?= icon('paperclip', 'icon-sm') ?><span class="grow truncate" data-attach-name></span><button class="btn ghost sm" type="button" data-attach-clear>Remover</button></div>

  <form class="chat-compose" data-chat-form>
    <label class="btn ghost icon-only" aria-label="Anexar arquivo" title="Anexar imagem, vídeo ou documento">
      <?= icon('paperclip') ?>
      <input type="file" class="sr-only" data-chat-file accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
    </label>
    <textarea rows="1" placeholder="Escreva uma mensagem" aria-label="Mensagem" data-chat-input maxlength="4000"></textarea>
    <button class="btn ghost icon-only chat-mic" type="button" data-chat-record aria-label="Gravar áudio" title="Gravar áudio"><?= icon('mic') ?></button>
    <button class="btn icon-only chat-send" type="submit" aria-label="Enviar"><?= icon('send') ?></button>
  </form>

  <!-- Gravação de áudio: aparece no lugar da caixa de texto -->
  <div class="rec-bar" data-rec-bar hidden>
    <button class="btn ghost icon-only rec-cancel" type="button" data-rec-cancel aria-label="Cancelar gravação" title="Cancelar"><?= icon('trash') ?></button>
    <div class="rec-live" data-rec-live>
      <span class="rec-dot" aria-hidden="true"></span>
      <span class="rec-time" data-rec-time role="timer" aria-live="off">0:00</span>
      <span class="rec-wave" data-rec-wave aria-hidden="true"></span>
    </div>
    <div class="rec-preview" data-rec-preview hidden></div>
    <button class="btn secondary icon-only" type="button" data-rec-stop aria-label="Parar e ouvir antes de enviar" title="Parar e ouvir"><?= icon('stop') ?></button>
    <button class="btn icon-only" type="button" data-rec-send aria-label="Enviar áudio" title="Enviar"><?= icon('send') ?></button>
  </div>
</section>

<dialog class="sheet" data-msg-sheet aria-labelledby="msg-sheet-title">
  <div class="sheet-body" data-sheet-step="choose">
    <p class="sheet-title" id="msg-sheet-title">Apagar mensagem?</p>
    <button type="button" class="sheet-btn" data-del="me"><?= icon('eye-off', 'icon-sm') ?> Apagar para mim</button>
    <button type="button" class="sheet-btn danger" data-del="all"><?= icon('trash', 'icon-sm') ?> Apagar para todos</button>
    <button type="button" class="sheet-btn muted" data-sheet-close>Cancelar</button>
  </div>
  <div class="sheet-body" data-sheet-step="confirm" hidden>
    <p class="sheet-title">Apagar para todos?</p>
    <p class="small muted" style="margin:0 4px 12px">A mensagem e o anexo deixam de aparecer para os dois lados. Não é possível desfazer.</p>
    <button type="button" class="sheet-btn danger" data-del-confirm><?= icon('trash', 'icon-sm') ?> Sim, apagar para todos</button>
    <button type="button" class="sheet-btn muted" data-sheet-close>Cancelar</button>
  </div>
</dialog>
