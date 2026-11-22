<?php $editable = $a['status'] === 'draft'; ?>
<div class="page-head">
  <div>
    <p class="muted small"><a href="/app/avaliacoes">Avaliações físicas</a></p>
    <h1><?= $editable ? 'Enviar avaliação física' : 'Avaliação de ' . e(fmt_date(to_local($a['submitted_at'])->format('Y-m-d'))) ?></h1>
    <?php if ($a['due_on']): ?><p>Prevista para <?= e(fmt_date($a['due_on'], true)) ?></p><?php endif; ?>
  </div>
  <?php if (!$editable): ?><span class="badge <?= $a['status'] === 'reviewed' ? 'ok' : 'warn' ?>"><?= $a['status'] === 'reviewed' ? 'Revisada' : 'Aguardando revisão' ?></span><?php endif; ?>
</div>

<?php if ($a['status'] === 'reviewed' && ($a['trainer_comments'] || $a['trainer_guidance'])): ?>
  <div class="card mb" style="border-color:var(--accent)">
    <h3>Retorno do Renan</h3>
    <?php if ($a['trainer_comments']): ?><p class="pre mt"><?= e($a['trainer_comments']) ?></p><?php endif; ?>
    <?php if ($a['trainer_guidance']): ?><p class="section-title mt-lg">Orientações</p><p class="pre"><?= e($a['trainer_guidance']) ?></p><?php endif; ?>
  </div>
<?php endif; ?>

<form method="post" action="/app/avaliacoes/<?= (int) $a['id'] ?>/enviar" class="stack">
  <?= csrf_field() ?>
  <div class="card">
    <h3>Fotografias</h3>
    <p class="small muted mt mb">Obrigatórias: frente, lado e costas — mesma roupa, local e iluminação das avaliações anteriores, se possível.</p>
    <?= View::partial('photo_slots', ['slots' => $slots, 'photos' => $photos, 'uploadUrl' => '/app/avaliacoes/' . $a['id'] . '/foto', 'deleteBase' => '/app/avaliacoes/' . $a['id'] . '/foto/{id}/excluir', 'editable' => $editable]) ?>
  </div>
  <div class="card">
    <h3>Medidas corporais <span class="muted small">(opcional)</span></h3>
    <div class="form-grid cols-3 mt">
      <?php foreach ($measureLabels as $k => $label): ?>
        <div class="field"><label for="m_<?= e($k) ?>"><?= e($label) ?></label><input id="m_<?= e($k) ?>" name="m[<?= e($k) ?>]" inputmode="decimal" value="<?= e(isset($measures[$k]) ? str_replace('.', ',', (string) $measures[$k]) : '') ?>"<?= $editable ? '' : ' readonly' ?>></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="field"><label for="sn">Como você está neste momento?</label><textarea id="sn" name="student_notes" rows="4" placeholder="Percepções, mudanças, dificuldades…"<?= $editable ? '' : ' readonly' ?>><?= e($a['student_notes']) ?></textarea></div>
  </div>
  <?php if ($editable): ?>
    <div class="sticky-actions row between">
      <button class="btn secondary" type="submit" name="action" value="save">Salvar rascunho</button>
      <button class="btn accent" type="submit" name="action" value="submit">Enviar avaliação</button>
    </div>
  <?php endif; ?>
</form>
