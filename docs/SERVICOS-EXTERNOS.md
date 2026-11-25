# Configuração dos serviços externos

Sem as credenciais abaixo, cada integração fica **desativada de forma visível**: o botão some ou aparece um aviso. Nada é simulado. Nenhuma chave foi inventada; todas devem vir das contas do Renan.

## E-mail (obrigatório)

| Opção | Configuração |
|---|---|
| Conta do domínio na HostGator | `MAIL_HOST=mail.wruchpersonal.com`, `MAIL_PORT=465`, `MAIL_ENCRYPTION=ssl`, usuário e senha da conta `nao-responda@…` |
| Provedor transacional (melhor entrega) | Brevo, Amazon SES, Mailgun ou Resend por SMTP: use host, porta (587 + `tls`) e credenciais do provedor e valide o domínio (SPF/DKIM) |

Depois, use **Configurações → Operação → Enviar e-mail de teste**. Falhas ficam em **Configurações → Registros** com o erro, e a fila tenta de novo até 5 vezes com espera crescente.

## Login com Google

1. Em [console.cloud.google.com](https://console.cloud.google.com), crie um projeto e vá em *APIs e serviços → Tela de consentimento OAuth* (tipo Externo, com nome, logo e e-mail de suporte).
2. Em *Credenciais → Criar credenciais → ID do cliente OAuth → Aplicativo da Web*:
   - Origem JavaScript: `https://wruchpersonal.com`
   - URI de redirecionamento: `https://wruchpersonal.com/auth/google/callback`
3. Escopos usados: `openid`, `email` e `profile` (não precisam de verificação especial do Google).
4. Copie para `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` no `.env` do servidor (nunca no código nem no navegador).
5. Enquanto a tela de consentimento estiver em modo *Teste*, só os e-mails cadastrados como "usuários de teste" conseguem entrar. Para liberar a todos, publique o app (*Publicar aplicativo*).

Como funciona no Wruch Personal:
- O botão "Continuar com Google" só aparece no login e no cadastro quando as duas chaves estão preenchidas.
- Conta nova pelo Google vira **aluno aguardando aprovação** e passa pela tela de consentimento LGPD. Ninguém vira treinador pelo Google: o papel de treinador/desenvolvedor continua vindo apenas de `TRAINER_EMAILS` / `DEVELOPER_EMAILS` com e-mail confirmado.
- Conta que já existe com o mesmo e-mail **verificado pelo Google** é vinculada automaticamente (sem duplicar). E-mail não verificado nunca vincula.
- Logado, o aluno (Meu perfil) e o Renan (Configurações → Meu perfil) podem conectar ou desconectar o Google. Desconectar só é permitido para quem tem senha, para ninguém perder o acesso.

## Login com Apple

Exige uma conta Apple Developer (paga).

1. *Certificates, IDs & Profiles → Identifiers*: crie um **App ID** com “Sign in with Apple” habilitado.
2. Crie um **Services ID** (ex.: `com.wruchpersonal.web`). Esse é o `APPLE_CLIENT_ID`. Configure o domínio `wruchpersonal.com` e a Return URL `https://wruchpersonal.com/auth/apple/callback`.
3. *Keys*: crie uma chave com “Sign in with Apple” e baixe o `.p8` (o download só é possível uma vez). Salve **fora do public_html**, ex.: `/home/USUARIO/wruch/keys/`. Anote o **Key ID** e o **Team ID**.
4. Preencha `APPLE_CLIENT_ID`, `APPLE_TEAM_ID`, `APPLE_KEY_ID` e `APPLE_PRIVATE_KEY_PATH`.

Contas novas criadas por Google ou Apple passam pela tela de consentimento LGPD no primeiro acesso.

## Mercado Pago

1. Em [mercadopago.com.br/developers](https://www.mercadopago.com.br/developers) → *Suas integrações*, crie uma aplicação (produto: pagamentos online / assinaturas).
2. Copie as **credenciais de produção**: `MP_ACCESS_TOKEN` (`APP_USR-…`) e `MP_PUBLIC_KEY`. Para homologar, use antes as credenciais de teste e usuários de teste.
3. Em *Webhooks → Configurar notificações*:
   - URL de produção: `https://wruchpersonal.com/webhooks/mercadopago`
   - Eventos: **Pagamentos**, **Planos e assinaturas** (vinculação e pagamento recorrente)
   - Copie a **assinatura secreta** para `MP_WEBHOOK_SECRET`
4. Em **Financeiro**, o Renan cadastra os planos (preço, periodicidade e formas aceitas).

### Formas de cobrança

- **Recorrente no cartão**: assinatura do Mercado Pago (`/preapproval`). O aluno informa o cartão no próprio Mercado Pago, e a renovação e as novas tentativas de cobrança são automáticas. O aluno pode cancelar a renovação na plataforma.
- **Pagamento do período (Pix, boleto ou cartão)**: Checkout Pro. Cada pagamento aprovado libera o período do plano, e o aluno recebe um lembrete 5 dias antes do vencimento.

### Garantias

- Um pagamento só muda de status com dados **consultados na API do Mercado Pago**: nem o corpo do webhook nem a volta à página de sucesso bastam.
- Webhooks com assinatura `x-signature` inválida são recusados com 401.
- Notificações repetidas são ignoradas (idempotência por evento e por ID de pagamento).
- O cron reconcilia assinaturas pendentes e ativas a cada hora, e o botão *Sincronizar* no Financeiro faz o mesmo na hora.
- Nenhum dado de cartão passa pelo servidor do Wruch Personal.

### Política de inadimplência

Em **Configurações → Operação**, o Renan escolhe entre *não restringir* (padrão) e *restringir os treinos sem assinatura em dia*, com carência em dias. O aluno restrito continua com acesso a mensagens, perfil e assinatura. Nenhum dado é apagado, e o Renan pode liberar alunos individualmente.
