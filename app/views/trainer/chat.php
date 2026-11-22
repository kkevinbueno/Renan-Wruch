<div class="chat-layout<?= $studentId ? ' has-thread' : '' ?>">
  <aside class="card convs-panel" style="padding:10px">
    <p class="section-title" style="padding:6px 6px 0">Conversas</p>
    <?php if (!$conversations): ?><p class="small muted" style="padding:6px">Nenhum aluno ativo.</p><?php endif; ?>
    <div class="convs">
      <?php foreach ($conversations as $c): ?>
        <a class="conv<?= (int) $c['id'] === (int) $studentId ? ' active' : '' ?>" href="/painel/mensagens/<?= (int) $c['id'] ?>">
          <?= avatar($c, 'sm') ?>
          <div class="grow" style="min-width:0"><div class="row between nowrap"><span class="strong truncate"><?= e($c['name']) ?></span><span class="xs muted"><?= $c['last_at'] ? e(fmt_ago($c['last_at'])) : '' ?></span></div><div class="xs muted truncate"><?= e($c['last_body'] ?? 'Sem mensagens') ?></div></div>
          <?php if ((int) $c['unread']): ?><span class="badge dark"><?= (int) $c['unread'] ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>
  <div>
    <?php if ($student): ?>
      <?= View::partial('chat', ['studentId' => $studentId, 'peerName' => $student['name'], 'backHref' => '/painel/mensagens']) ?>
    <?php else: ?>
      <div class="card empty" style="height:100%"><?= icon('message', 'icon-lg') ?><h3>Selecione uma conversa</h3><p>As mensagens são privadas entre você e cada aluno.</p></div>
    <?php endif; ?>
  </div>
</div>
