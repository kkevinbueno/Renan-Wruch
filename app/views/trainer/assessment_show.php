<?php
$labels = ['front' => 'Frente', 'side' => 'Lado', 'back' => 'Costas', 'extra' => 'Adicional'];
$bySlot = fn(array $photos) => array_reduce($photos, function ($c, $p) { $c[$p['kind']][] = $p; return $c; }, []);
$cur = $bySlot($photos);
$cmp = $bySlot($comparePhotos);
$prevMeasures = $compare ? json_decode_array($compare['measurements']) : [];
?>
<div class="page-head">
  <div>
    <p class="muted small"><a href="/painel/avaliacoes">Avaliações físicas</a> / <a href="/painel/alunos/<?= (int) $a['uid'] ?>?aba=avaliacoes"><?= e($a['name']) ?></a></p>
    <h1>Avaliação de <?= e(fmt_date(to_local($a['submitted_at'])->format('Y-m-d'))) ?></h1>
    <p><?= $a['status'] === 'reviewed' ? 'Revisada em ' . e(fmt_datetime($a['reviewed_at'])) : 'Aguardando sua revisão' ?></p>
  </div>
  <span class="badge <?= $a['status'] === 'reviewed' ? 'ok' : 'warn' ?>"><?= $a['status'] === 'reviewed' ? 'Revisada' : 'Para revisar' ?></span>
</div>

<div class="grid grid-main">
  <div class="stack">
    <div class="card">
      <div class="card-head">
        <h3>Fotografias</h3>
        <?php if ($previous): ?>
          <form method="get" class="row"><label for="cmp" class="small muted">Comparar com</label><select id="cmp" name="comparar" data-autosubmit style="width:auto;min-height:36px"><?php foreach ($previous as $p): ?><option value="<?= (int) $p['id'] ?>"<?= selected($p['id'], $compare['id'] ?? '') ?>><?= e(fmt_date_short(to_local($p['submitted_at'])->format('Y-m-d'))) ?></option><?php endforeach; ?><option value="0"<?= $compare ? '' : ' selected' ?>>Inicial</option></select></form>
        <?php endif; ?>
      </div>
      <?php foreach (['front', 'side', 'back'] as $slot): ?>
        <p class="section-title mt"><?= $labels[$slot] ?></p>
        <div class="compare">
          <figure style="margin:0"><?php foreach ($cur[$slot] ?? [] as $p): ?><a href="<?= e(Files::url((int) $p['id'])) ?>" target="_blank"><img src="<?= e(Files::url((int) $p['id'])) ?>" alt="<?= $labels[$slot] ?> atual" style="width:100%;border-radius:10px;border:1px solid var(--border)"></a><?php endforeach; ?><figcaption class="xs muted">Atual</figcaption></figure>
          <figure style="margin:0">
            <?php $older = $compare ? ($cmp[$slot] ?? []) : array_values(array_filter($initialPhotos, fn($p) => $p['kind'] === $slot)); ?>
            <?php foreach ($older as $p): ?><a href="<?= e(Files::url((int) $p['id'])) ?>" target="_blank"><img src="<?= e(Files::url((int) $p['id'])) ?>" alt="<?= $labels[$slot] ?> anterior" style="width:100%;border-radius:10px;border:1px solid var(--border)"></a><?php endforeach; ?>
            <?php if (!$older): ?><div class="empty small">Sem foto para comparar</div><?php endif; ?>
            <figcaption class="xs muted"><?= $compare ? e(fmt_date_short(to_local($compare['submitted_at'])->format('Y-m-d'))) : 'Avaliação inicial' ?></figcaption>
          </figure>
        </div>
      <?php endforeach; ?>
      <?php if (!empty($cur['extra'])): ?>
        <p class="section-title mt-lg">Adicionais</p>
        <div class="gallery"><?php foreach ($cur['extra'] as $p): ?><figure><a href="<?= e(Files::url((int) $p['id'])) ?>" target="_blank"><img src="<?= e(Files::url((int) $p['id'])) ?>" alt="Foto adicional" loading="lazy"></a></figure><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stack">
    <?php if ($a['student_notes']): ?><div class="card"><h3>Observações do aluno</h3><p class="pre mt small"><?= e($a['student_notes']) ?></p></div><?php endif; ?>
    <?php if ($measures): ?>
      <div class="card"><h3>Medidas</h3>
        <table class="table mt"><thead><tr><th></th><th>Atual</th><?php if ($prevMeasures): ?><th>Anterior</th><?php endif; ?></tr></thead><tbody>
          <?php foreach ($measureLabels as $k => $l): if (!isset($measures[$k]) && !isset($prevMeasures[$k])) continue; ?>
            <tr><td><?= e($l) ?></td><td class="strong"><?= isset($measures[$k]) ? e(str_replace('.', ',', (string) $measures[$k])) : '—' ?></td><?php if ($prevMeasures): ?><td class="muted"><?= isset($prevMeasures[$k]) ? e(str_replace('.', ',', (string) $prevMeasures[$k])) : '—' ?></td><?php endif; ?></tr>
          <?php endforeach; ?>
        </tbody></table>
      </div>
    <?php endif; ?>
    <form class="card" method="post" action="/painel/avaliacoes/<?= (int) $a['id'] ?>">
      <?= csrf_field() ?>
      <h3>Sua revisão</h3>
      <div class="field mt"><label for="tc">Comentários</label><textarea id="tc" name="trainer_comments" rows="4"><?= e($a['trainer_comments']) ?></textarea></div>
      <div class="field"><label for="tg">Orientações para o aluno</label><textarea id="tg" name="trainer_guidance" rows="4"><?= e($a['trainer_guidance']) ?></textarea></div>
      <div class="field"><label for="nx">Próxima avaliação</label><input id="nx" type="date" name="next_assessment_on" value="" placeholder="Automática"><span class="hint">Em branco: calculada automaticamente (envio + <?= (int) $profile['assessment_every_days'] ?> dias). Atual: <?= e(fmt_date_short($profile['next_assessment_on'])) ?>.</span></div>
      <?php if ($a['status'] === 'submitted'): ?><label class="check mt"><input type="checkbox" name="mark_reviewed" value="1" checked> <span>Marcar como revisada e avisar o aluno</span></label><?php endif; ?>
      <button class="btn mt" type="submit">Salvar</button>
    </form>
  </div>
</div>
