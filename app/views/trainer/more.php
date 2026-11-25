<div class="page-head"><div><h1>Mais</h1><p>Planejamento, gestão e conta.</p></div></div>
<div class="hub enter">
  <a href="/painel/alimentacao"><span class="ico"><?= icon('utensils') ?></span><div class="grow"><b>Alimentação</b><span class="small muted">Refeições e hidratação dos alunos</span></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a href="/painel/biblioteca"><span class="ico blue"><?= icon('book') ?></span><div class="grow"><b>Biblioteca</b><span class="small muted">Exercícios e modelos de treino</span></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a href="/painel/financeiro"><span class="ico ok"><?= icon('card') ?></span><div class="grow"><b>Financeiro</b><span class="small muted">Assinaturas, pagamentos e planos<?= $c['failed_payments'] ? ' · ' . plural($c['failed_payments'], 'pagamento recusado', 'pagamentos recusados') : '' ?></span></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a href="/painel/avaliacoes"><span class="ico"><?= icon('camera') ?></span><div class="grow"><b>Avaliações físicas</b><span class="small muted"><?= $c['assessments'] ? plural($c['assessments'], 'para revisar', 'para revisar') : 'Todas as avaliações' ?></span></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a href="/painel/solicitacoes"><span class="ico"><?= icon('repeat') ?></span><div class="grow"><b>Pedidos de troca de dia</b><span class="small muted"><?= $c['requests'] ? plural($c['requests'], 'pendente', 'pendentes') : 'Histórico de pedidos' ?></span></div><?= icon('chevron-right', 'icon-sm') ?></a>
  <a href="/painel/configuracoes"><span class="ico"><?= icon('settings') ?></span><div class="grow"><b>Configurações</b><span class="small muted">Perfil, página pública e regras</span></div><?= icon('chevron-right', 'icon-sm') ?></a>
</div>
<div class="stack mt-lg">
  <?php if (Auth::isDeveloper()): ?>
    <form method="post" action="/alternar-interface"><?= csrf_field() ?><input type="hidden" name="to" value="student"><button class="btn secondary block" type="submit"><?= icon('repeat', 'icon-sm') ?> Alternar para aluno</button></form>
  <?php endif; ?>
  <form method="post" action="/sair"><?= csrf_field() ?><button class="btn ghost block" type="submit"><?= icon('logout', 'icon-sm') ?> Sair da conta</button></form>
</div>
