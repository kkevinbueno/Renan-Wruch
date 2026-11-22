<?php
/** @var array $f @var mixed $value */
$id = 'a_' . $f['key'];
$name = 'a[' . $f['key'] . ']';
$req = !empty($f['required']);
?>
<div class="field" data-field="<?= e($f['key']) ?>">
  <?php if (in_array($f['type'], ['checkboxes', 'radio'], true)): ?>
    <span class="label<?= $req ? ' req' : '' ?>" id="<?= e($id) ?>_l"><?= e($f['label']) ?></span>
    <div class="pills" role="group" aria-labelledby="<?= e($id) ?>_l">
      <?php foreach ($f['options'] as $k => $label): ?>
        <?php $isChecked = is_array($value) ? in_array((string) $k, array_map('strval', $value), true) : (string) $value === (string) $k; ?>
        <label class="pill-check"><input type="<?= $f['type'] === 'radio' ? 'radio' : 'checkbox' ?>" name="<?= e($name) ?><?= $f['type'] === 'checkboxes' ? '[]' : '' ?>" value="<?= e($k) ?>"<?= checked($isChecked) ?>><span><?= e($label) ?></span></label>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <label for="<?= e($id) ?>"<?= $req ? ' class="req"' : '' ?>><?= e($f['label']) ?></label>
    <?php if ($f['type'] === 'textarea'): ?>
      <textarea id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $req ? ' data-required' : '' ?>><?= e($value) ?></textarea>
    <?php elseif ($f['type'] === 'select'): ?>
      <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $req ? ' data-required' : '' ?>>
        <option value="">Selecione…</option>
        <?php foreach ($f['options'] as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($k, $value) ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    <?php else: ?>
      <input id="<?= e($id) ?>" type="<?= e($f['type']) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"<?= isset($f['step']) ? ' step="' . e($f['step']) . '"' : '' ?><?= isset($f['min']) ? ' min="' . e($f['min']) . '"' : '' ?><?= isset($f['max']) ? ' max="' . e($f['max']) . '"' : '' ?><?= $req ? ' data-required' : '' ?><?= $f['type'] === 'number' ? ' inputmode="decimal"' : '' ?>>
    <?php endif; ?>
  <?php endif; ?>
  <?php if (!empty($f['hint'])): ?><span class="hint"><?= e($f['hint']) ?></span><?php endif; ?>
</div>
