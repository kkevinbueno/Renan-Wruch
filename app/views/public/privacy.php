<?php $s = Settings::all(); ?>
<article class="prose">
  <h1>Política de Privacidade</h1>
  <p class="muted small">Versão <?= e(config('app.consent_version')) ?></p>

  <h2>Quem trata os seus dados</h2>
  <p>A plataforma <?= e($s['brand_name']) ?> é operada por <?= e($s['trainer_name']) ?>, responsável (controlador) pelo tratamento dos dados pessoais dos alunos da consultoria. Contato: <a href="mailto:<?= e($s['contact_email']) ?>"><?= e($s['contact_email']) ?></a>.</p>

  <h2>Quais dados coletamos</h2>
  <p>Dados de cadastro (nome, e-mail, telefone, data de nascimento); respostas da anamnese, incluindo objetivos, rotina, limitações físicas e histórico de lesões informados por você; fotografias corporais e medidas enviadas nas avaliações; registros de treinos, autoavaliações, alimentação e hidratação; mensagens e arquivos trocados no chat; e dados de assinatura e pagamento (status e valores — os dados do cartão são processados apenas pelo Mercado Pago).</p>

  <h2>Para que usamos</h2>
  <p>Exclusivamente para planejar, prescrever e acompanhar os seus treinos, comunicar-se com você sobre a consultoria, enviar lembretes e avisos da plataforma e gerenciar a sua assinatura. Não utilizamos análise automática de aparência corporal nem geramos diagnósticos.</p>

  <h2>Bases legais (LGPD)</h2>
  <p>Execução do contrato de consultoria (art. 7º, V), consentimento específico e destacado para dados pessoais sensíveis de saúde e fotografias (art. 11, I), cumprimento de obrigações legais (art. 7º, II) e legítimo interesse para segurança da plataforma (art. 7º, IX).</p>

  <h2>Com quem compartilhamos</h2>
  <p>Somente com fornecedores necessários ao funcionamento: hospedagem do site e do banco de dados, serviço de envio de e-mails e o Mercado Pago (pagamentos). Nenhum dado é vendido ou usado para publicidade.</p>

  <h2>Segurança</h2>
  <p>As fotografias e arquivos ficam em armazenamento privado, fora de pastas públicas, e só são exibidos a você e ao treinador por meio de links temporários vinculados à sessão. Usamos conexão criptografada (HTTPS), senhas protegidas por hash, controle de acesso no servidor e registro de ações críticas.</p>

  <h2>Por quanto tempo guardamos</h2>
  <p>Enquanto houver acompanhamento ativo e pelo período necessário para cumprir obrigações legais. Após o encerramento, você pode solicitar a exclusão a qualquer momento; fotografias podem ser excluídas automaticamente após o prazo de retenção configurado pela consultoria.</p>

  <h2>Seus direitos</h2>
  <p>Você pode confirmar a existência de tratamento, acessar, corrigir, exportar e solicitar a exclusão dos seus dados, além de revogar o consentimento. Na área <b>Meu perfil</b> é possível baixar seus dados e registrar solicitações, que são respondidas em até 15 dias. A revogação do consentimento para dados de saúde pode impedir a continuidade do acompanhamento.</p>

  <h2>Cookies</h2>
  <p>Usamos apenas cookies essenciais de sessão e segurança. Não há cookies de publicidade ou rastreamento de terceiros.</p>
</article>
