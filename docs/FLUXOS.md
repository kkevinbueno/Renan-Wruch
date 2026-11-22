# Fluxos principais

Cada fluxo é verificado por `php tests/run.php`.

## A — Novo aluno

1. **Cadastro** (`/cadastro`, Google ou Apple) com aceite dos termos e consentimento específico para dados de saúde e fotos. O papel é sempre *aluno*: permissões especiais vêm só do `.env`.
2. **Confirmação de e-mail** por link de uso único (48 h). Sem confirmação, nenhuma área abre.
3. **Status pendente**: o Renan recebe um e-mail. O aluno vê “Cadastro em análise” e não acessa informações da consultoria.
4. **Aprovação ou recusa** no perfil do aluno. O aluno recebe o resultado por e-mail.
5. **Avaliação inicial obrigatória** em 7 etapas: dados, objetivos e modalidades, rotina e disponibilidade, limitações e lesões, fotos (frente, lado e costas obrigatórias, mais as opcionais, com pré-visualização e substituição), observações pessoais, revisão e envio. O rascunho é salvo automaticamente.
6. **Envio**: grava data e hora, vincula as fotos, notifica o Renan e mostra a confirmação. Sem treino publicado, a área de treinos mostra **“Seu treino está sendo preparado pelo Renan.”**
7. O Renan **publica o primeiro treino** → o aluno recebe um e-mail → o treino do dia aparece em destaque → o aluno executa e registra.

## B — Atualização de treino

Renan abre o aluno → **Treinos** → *Editar* → o sistema cria o rascunho v(n+1) a partir da versão publicada → ele edita (arrastar e soltar, biblioteca, campos por modalidade, vídeo por link ou upload) → salvamento automático → **Publicar agora** ou **Agendar** (data e hora). A versão anterior continua visível ao aluno até a publicação. Cada registro de execução aponta para a versão usada, e versões antigas nunca são alteradas.

## C — Avaliação física

O Renan define, por aluno, o intervalo, a próxima data, a antecedência e a quantidade de lembretes e os alertas de atraso → o cron envia os lembretes (sem duplicar) → o aluno envia 3 fotos obrigatórias, fotos extras, medidas opcionais e observações → o Renan é notificado → compara com a avaliação anterior ou inicial → registra comentários e orientações → marca como revisada → a próxima data é calculada (envio + intervalo) ou definida manualmente.

## D — Chat

O aluno envia texto, imagem, vídeo, documento ou áudio gravado no navegador → os arquivos são validados (tipo real e tamanho), as imagens reprocessadas e tudo fica guardado fora da pasta pública → o Renan vê a conversa (atualização a cada 4 s) → responde → as mensagens são marcadas como lidas (✓✓) → o histórico fica disponível. O aviso por e-mail é agrupado (padrão de 15 min), não traz conteúdo e é cancelado se a mensagem for lida antes.

## E — Assinatura

O Renan cadastra o plano → o aluno escolhe *recorrente no cartão* ou *pagar o período (Pix, boleto, cartão)* → é redirecionado ao Mercado Pago → o webhook assinado chega → o sistema **consulta a API** e atualiza a assinatura e o pagamento → a política de acesso é aplicada → as próximas cobranças são acompanhadas por webhook e pela reconciliação horária. Recusas avisam o aluno e o Renan, e o aluno pode cancelar a renovação.

## F — Desenvolvedores

O e-mail fica em `DEVELOPER_EMAILS` → o desenvolvedor cadastra e confirma → começa na interface de aluno → usa **Alternar para Renan** (menu lateral) → opera as funcionalidades do Renan sem a senha dele → cada ação é registrada com a identidade real e a interface usada (**Configurações → Registros**) → volta para a interface de aluno. Alunos comuns recebem 403 ao tentar alternar. Contas de desenvolvedor não aparecem em “Meus alunos” nem nos números do painel, exceto com o filtro “Incluir contas de teste”.

## Outros fluxos

- **Mudança de dia**: o aluno pede (data original, nova data, observação) → o Renan aprova ou recusa em *Pedidos de mudança* → só depois da aprovação o calendário oficial muda.
- **Treino não realizado**: o aluno marca Realizado, Adiado ou Não realizado, com observação. O Renan vê no histórico.
- **Reativação**: pausado ou encerrado → *Reativar*, opcionalmente com novo ciclo e nova avaliação inicial. O histórico anterior é preservado.
- **LGPD**: o aluno baixa os próprios dados em JSON e registra pedidos de acesso, correção ou exclusão. O Renan vê os prazos (15 dias) e pode excluir definitivamente os dados e arquivos do aluno.
