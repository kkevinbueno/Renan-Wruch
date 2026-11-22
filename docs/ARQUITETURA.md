# Arquitetura, segurança e LGPD

## Decisões

| Tema | Escolha | Motivo |
|---|---|---|
| Linguagem | PHP 8.1+ puro, sem dependências | Roda em qualquer plano HostGator (o plano atual não foi confirmado). Não exige Node, processo persistente nem build |
| Banco | MySQL/MariaDB (testes em SQLite) | Disponível em todos os planos. Migrações versionadas em `app/migrations` |
| Interface | HTML renderizado no servidor + CSS próprio + JavaScript sem frameworks | Rápida em celular, acessível, sem etapa de build |
| Tempo real | Consultas curtas (4 s) no chat e contadores a cada 60 s | WebSockets não funcionam em hospedagem compartilhada |
| Arquivos | Disco privado (`storage/uploads`) fora do `public_html` | Sem custo extra. A classe `Files` concentra o acesso e pode ser trocada por S3 depois |
| E-mail | SMTP próprio (SSL/STARTTLS) com fila no banco | Funciona com a conta da HostGator ou com um provedor transacional |
| Pagamentos | API REST do Mercado Pago (assinaturas + Checkout Pro) | Pix, boleto e cartão no Brasil, com assinaturas recorrentes |
| Tarefas | `cron/run.php` (cron do cPanel) + verificação a cada acesso | Publicações agendadas não dependem só do cron |

> O escopo sugeria Next.js e PostgreSQL. Sem a confirmação do plano HostGator, PHP + MySQL foi a forma de garantir que tudo funcione de verdade na hospedagem desejada. Os recursos aprovados são os mesmos.

## Entidades (tabelas)

`users`, `student_profiles`, `student_cycles`, `anamneses`, `files` (fotos, vídeos, anexos, avatares), `modalities`, `exercises`, `workouts` (treinos e modelos), `workout_versions`, `workout_items`, `schedule_overrides`, `reschedule_requests`, `workout_sessions`, `session_entries`, `self_assessments`, `nutrition_logs`, `physical_assessments`, `calendar_events`, `messages`, `email_queue`, `notification_prefs`, `plans`, `subscriptions`, `payments`, `webhook_events`, `audit_logs`, `settings`, `tokens`, `rate_limits`, `data_requests`.

As chaves estrangeiras têm `ON DELETE` adequado. Há índices nos filtros mais usados e restrições `UNIQUE` (e-mail, sessão por aluno, treino e data, pagamento por ID do provedor, deduplicação de e-mails e de eventos de webhook).

## Papéis e permissões (sempre verificados no servidor)

- `student`: só os próprios dados. Cada rota passa por middleware (`student`, `onboarding`, `app` e `chat`) e os controladores conferem a posse do registro (`student_id = usuário logado`).
- `trainer`: definido por `TRAINER_EMAILS` e aplicado **apenas a e-mails confirmados**.
- `developer`: definido por `DEVELOPER_EMAILS`. A interface fica na sessão (`mode`), só contas `developer` podem alterná-la e a auditoria grava `actor_id` real e `acting_as`.
- Estados independentes: cadastro aprovado (`users.status`), avaliação inicial (`onboarding_completed_at`), treino publicado (`workout_versions.status`) e assinatura ativa (`subscriptions`).

## Segurança

- HTTPS obrigatório (redirecionamento + HSTS), cookies de sessão `HttpOnly`, `Secure` e `SameSite=Lax`, sessões em pasta própria, ID regenerado no login e sessões antigas invalidadas na troca de senha.
- Token CSRF em todo POST (formulários e `fetch`). Webhooks e o retorno da Apple usam assinatura ou estado próprios.
- Consultas parametrizadas (PDO, sem emulação) e escape de saída com `e()`.
- CSP restritiva (sem scripts inline), `X-Frame-Options`, `nosniff`, `Referrer-Policy` e `Permissions-Policy`.
- Senhas com `password_hash`. Limites por IP e por e-mail para login, cadastro, recuperação, uploads, chat e checkout.
- Uploads: lista branca de tipos com detecção real (`finfo`), limite por tipo, nomes aleatórios, imagens recodificadas pelo GD (remove EXIF/GPS e neutraliza arquivos disfarçados), SVG e executáveis recusados e documentos entregues como download com `nosniff` e CSP `sandbox`.
- Mídia por `/media/{id}` com sessão + **URL temporária assinada (HMAC) vinculada ao usuário** + regra de acesso por contexto + `Cache-Control: no-store`. O service worker não armazena páginas, API ou mídia.
- Tokens de e-mail e senha guardados só como hash SHA-256, com expiração e uso único.
- Segredos apenas no `.env`, fora da pasta pública. As pastas `app/`, `storage/` etc. também têm `.htaccess` de bloqueio.

## LGPD

- Consentimento destacado e específico para dados sensíveis (saúde e fotos), com versão e data registradas. Contas criadas por login social passam pela mesma tela.
- Finalidade limitada ao acompanhamento, sem análise automática de imagem nem diagnósticos.
- Direitos do titular: exportação JSON pelo próprio aluno, pedidos de acesso, correção e exclusão com prazo e exclusão definitiva pelo Renan (registros + arquivos).
- Retenção configurável: exclusão automática de fotos de alunos encerrados após N meses (desativada por padrão; defina com orientação jurídica).
- E-mails sem conteúdo sensível. Auditoria de ações críticas.
- **Recomendação**: revisar a Política de Privacidade e os Termos (`app/views/public/`) com um advogado e incluir os dados do controlador (nome completo ou CNPJ).

## Limites conhecidos

- O “tempo real” do chat tem atraso de até ~4 s (consulta periódica).
- Não há antivírus no servidor compartilhado. A proteção vem de lista branca de tipos, recodificação de imagens e entrega segura. Em uma VPS, dá para integrar o ClamAV em `Files::store`.
- Vídeos grandes dependem dos limites de upload do plano (`upload_max_filesize`).
- A gravação de áudio usa `MediaRecorder`. Em navegadores sem suporte, o aluno envia um arquivo de áudio.
