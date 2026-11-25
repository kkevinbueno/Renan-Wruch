<?php /** @var array $notes @var int $sid @var string $topic @var int $period */ ?>
<div class="card">
  <h3>Observações do <?= e(first_name((string) Settings::get('trainer_name'))) ?></h3>
  <p class="xs muted mt">Visíveis só na interface do treinador.</p>
  <form method="post" action="/painel/alimentacao/observacoes" class="mt">
    <?= csrf_field() ?>
    <input type="hidden" name="student_id" value="<?= (int) $sid ?>"><input type="hidden" name="topic" value="<?= e($topic) ?>"><input type="hidden" name="periodo" value="<?= (int) $period ?>">
    <div class="field"><label for="nb_<?= e($topic) ?>" class="sr-only">Nova observação</label><textarea id="nb_<?= e($topic) ?>" name="body" rows="2" maxlength="2000" placeholder="<?= $topic === 'hidratacao' ? 'Ex.: bebe pouca água nos dias de trabalho' : 'Ex.: costuma pular o café da manhã' ?>" required></textarea></div>
    <button class="btn sm mt" type="submit"><?= icon('plus', 'icon-sm') ?> Adicionar observação</button>
  </form>
  <?php if ($notes): ?>
    <div class="list mt">
      <?php foreach ($notes as $n): ?>
        <div class="list-item small">
          <div class="grow"><div class="pre"><?= e($n['body']) ?></div><div class="xs muted"><?= e(fmt_datetime($n['created_at'])) ?><?= $n['author'] ? ' · ' . e(first_name($n['author'])) : '' ?></div></div>
          <form method="post" action="/painel/alimentacao/observacoes/<?= (int) $n['id'] ?>/excluir" data-confirm="Remover esta observação?"><?= csrf_field() ?><button class="btn ghost sm icon-only" type="submit" aria-label="Remover observação"><?= icon('trash', 'icon-sm') ?></button></form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
