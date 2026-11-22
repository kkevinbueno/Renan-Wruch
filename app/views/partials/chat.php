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
    <button class="btn ghost icon-only" type="button" data-chat-record aria-label="Gravar áudio" title="Gravar áudio"><?= icon('mic') ?></button>
    <button class="btn icon-only" type="submit" aria-label="Enviar"><?= icon('send') ?></button>
  </form>
</section>
