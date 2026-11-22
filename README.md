# Wruch Personal

Plataforma de consultoria esportiva online do treinador **Renan Wruch**: site institucional, área do aluno, interface do Renan, chat, calendário, avaliações por fotografias, autoavaliação, pagamentos pelo Mercado Pago e PWA instalável.

Foi feita em **PHP 8.1+ e MySQL, sem dependências externas** (sem Composer, sem Node no servidor). Assim, roda em **qualquer plano da HostGator, inclusive na hospedagem compartilhada**.

## O que está pronto

| Área | Recursos |
|---|---|
| Acesso | Cadastro com e-mail e senha, login com Google e Apple, confirmação de e-mail, recuperação de senha, aprovação pelo Renan, consentimento LGPD |
| Aluno | Treino do dia, registro série a série com salvamento automático, retomada, histórico, pedido de mudança de dia, calendário semanal/mensal, chat (texto, imagem, vídeo, documento e áudio gravado), autoavaliação 1–5 com emojis, alimentação e hidratação, avaliações físicas por fotos, assinatura, perfil, exportação de dados |
| Renan | Painel com dados reais, Meus alunos (busca, filtros, ordenação e paginação), perfil do aluno com 7 abas, aprovação, pausa, encerramento e reativação com novo ciclo, biblioteca de exercícios, editor de treinos (arrastar e soltar, versões, rascunho, publicação agendada), modelos, revisão de avaliações com comparação de fotos, calendário, financeiro, configurações e registros de auditoria |
| Desenvolvedores | Contas definidas no servidor que alternam entre a interface do aluno e a do Renan, com auditoria da identidade real. Não existe painel administrativo separado |
| Plataforma | PWA (manifesto, ícones, service worker que não guarda dados privados), e-mails com fila, novas tentativas e deduplicação, cron, webhooks assinados e idempotentes |

## Estrutura

```
app/            código da aplicação (fica FORA da pasta pública)
  controllers/  um controlador por área
  lib/          núcleo: banco, rotas, autenticação, arquivos, e-mail, Mercado Pago…
  migrations/   esquema do banco (MySQL e SQLite)
  views/        telas (PHP + HTML)
public/         única pasta pública (vai para o public_html)
  assets/       CSS, JS e ícones
  index.php     ponto de entrada
  sw.js         service worker
storage/        fotos, vídeos, sessões, logs (privado)
cron/           tarefas agendadas e utilitários de linha de comando
database/       schema.mysql.sql para importar no phpMyAdmin
tests/          testes automatizados de ponta a ponta
docs/           instalação, deploy, serviços, backups, fluxos e checklist
```

## Rodar localmente

```bash
cp .env.example .env          # ajuste: DB_DRIVER=sqlite, MAIL_TRANSPORT=log, FORCE_HTTPS=false, APP_URL=http://127.0.0.1:8080
php cron/generate-key.php     # copie o APP_KEY para o .env
php cron/migrate.php
php -S 127.0.0.1:8080 -t public public/router.php
```

Com `MAIL_TRANSPORT=log`, os e-mails ficam salvos em `storage/mail/`. Para confirmar sua conta local, abra o link que está no arquivo `.eml`.

## Testes

```bash
php tests/run.php
```

O comando sobe o servidor embutido do PHP, um banco SQLite temporário e um **Mercado Pago falso**. Em seguida, executa os fluxos A–F do escopo e as verificações de segurança: **252 verificações** na última execução.

## Documentação

- [docs/DEPLOY-HOSTGATOR.md](docs/DEPLOY-HOSTGATOR.md): publicar na HostGator (compartilhada ou VPS)
- [docs/SERVICOS-EXTERNOS.md](docs/SERVICOS-EXTERNOS.md): e-mail, Google, Apple e Mercado Pago
- [docs/BACKUP-E-RECUPERACAO.md](docs/BACKUP-E-RECUPERACAO.md)
- [docs/FLUXOS.md](docs/FLUXOS.md): como cada fluxo funciona
- [docs/ARQUITETURA.md](docs/ARQUITETURA.md): decisões técnicas, segurança e LGPD
- [docs/CHECKLIST-PUBLICACAO.md](docs/CHECKLIST-PUBLICACAO.md)
