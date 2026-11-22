<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Paginação">
  <?php if ($page > 1): ?><a class="btn secondary sm" href="<?= e($base . '?' . http_build_query($query + ['p' => $page - 1])) ?>"><?= icon('chevron-left', 'icon-sm') ?> Anterior</a><?php endif; ?>
  <span class="btn ghost sm" aria-current="page"><?= (int) $page ?> de <?= (int) $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn secondary sm" href="<?= e($base . '?' . http_build_query($query + ['p' => $page + 1])) ?>">Próxima <?= icon('chevron-right', 'icon-sm') ?></a><?php endif; ?>
</nav>
<?php endif; ?>
