<?php $s = Settings::all(); ?>
<article class="prose">
  <h1>Termos de Uso</h1>
  <p class="muted small">Versão <?= e(config('app.consent_version')) ?></p>
  <h2>O serviço</h2>
  <p>A plataforma <?= e($s['brand_name']) ?> é o ambiente da consultoria esportiva online de <?= e($s['trainer_name']) ?>. O acesso de alunos depende da aprovação do cadastro e, quando configurado, de uma assinatura ativa.</p>
  <h2>Responsabilidades do aluno</h2>
  <p>Fornecer informações verdadeiras na anamnese e nas avaliações, informar dores, lesões e restrições e buscar avaliação de profissional de saúde sempre que necessário. As orientações de treino não substituem acompanhamento médico, fisioterapêutico ou nutricional.</p>
  <h2>Conta e segurança</h2>
  <p>A conta é pessoal e intransferível. Mantenha sua senha em sigilo e avise o treinador em caso de uso indevido.</p>
  <h2>Conteúdo</h2>
  <p>Treinos, vídeos e materiais disponibilizados são de uso pessoal do aluno e não podem ser redistribuídos. Arquivos enviados pelo aluno são usados apenas no acompanhamento.</p>
  <h2>Pagamentos</h2>
  <p>Os pagamentos são processados pelo Mercado Pago. Assinaturas recorrentes podem ter a renovação cancelada a qualquer momento na área Assinatura; o acesso permanece até o fim do período pago. Em caso de inadimplência, o acesso pode ser limitado conforme a política da consultoria, sem exclusão do histórico.</p>
  <h2>Encerramento</h2>
  <p>O acompanhamento pode ser pausado ou encerrado por qualquer das partes. O histórico é preservado e pode ser exportado ou excluído a pedido do aluno, conforme a Política de Privacidade.</p>
  <h2>Contato</h2>
  <p><a href="mailto:<?= e($s['contact_email']) ?>"><?= e($s['contact_email']) ?></a> · WhatsApp <?= e($s['contact_whatsapp_label']) ?></p>
</article>
