<?php
/**
 * Fotos obrigatórias (frente/lado/costas) + adicionais.
 * @var array $slots @var array $photos @var string $uploadUrl @var string $deleteBase @var bool $editable
 */
$bySlot = [];
$extras = [];
foreach ($photos as $p) {
    if (isset($slots[$p['kind']])) $bySlot[$p['kind']] = $p; else $extras[] = $p;
}
?>
<div data-photo-uploader data-upload-url="<?= e($uploadUrl) ?>" data-delete-base="<?= e($deleteBase) ?>">
  <div class="photo-grid">
    <?php foreach ($slots as $key => $label): $p = $bySlot[$key] ?? null; ?>
      <div class="photo-slot<?= $p ? ' filled' : '' ?>" data-slot="<?= e($key) ?>" <?= $p ? 'data-file-id="' . (int) $p['id'] . '"' : '' ?>>
        <span class="slot-label"><?= e($label) ?></span>
        <?php if ($p): ?><img src="<?= e(Files::url((int) $p['id'])) ?>" alt="Foto de <?= e(mb_strtolower($label)) ?>"><?php endif; ?>
        <?php if ($editable): ?>
          <span class="slot-empty" <?= $p ? 'hidden' : '' ?>><?= icon('camera') ?><br>Adicionar foto</span>
          <input type="file" accept="image/*" aria-label="Foto de <?= e(mb_strtolower($label)) ?>">
          <div class="slot-actions" <?= $p ? '' : 'hidden' ?>>
            <button type="button" class="btn sm" data-replace>Substituir</button>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="section-title mt-lg">Fotos adicionais (opcional)</p>
  <div class="photo-grid" data-extras>
    <?php foreach ($extras as $p): ?>
      <div class="photo-slot filled" data-slot="extra" data-file-id="<?= (int) $p['id'] ?>">
        <img src="<?= e(Files::url((int) $p['id'])) ?>" alt="Foto adicional">
        <?php if ($editable): ?><div class="slot-actions"><button type="button" class="btn sm" data-remove>Remover</button></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if ($editable): ?>
      <div class="photo-slot" data-slot="extra" data-add-extra>
        <span class="slot-empty"><?= icon('plus') ?><br>Adicionar</span>
        <input type="file" accept="image/*" aria-label="Adicionar foto extra">
      </div>
    <?php endif; ?>
  </div>
  <?php if ($editable): ?><p class="hint mt">Fotos são reduzidas e têm os metadados (como localização) removidos antes de serem salvas. Somente você e o Renan têm acesso.</p><?php endif; ?>
</div>
