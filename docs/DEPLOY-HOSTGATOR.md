# Publicação na HostGator

A aplicação usa apenas **PHP 8.1+ (recomendado 8.2/8.3), MySQL/MariaDB, Apache com `.htaccess` e cron**, que existem em todos os planos HostGator com cPanel. Não precisa de Node.js, processos persistentes nem WebSockets: o chat atualiza por consultas curtas a cada 4 segundos.

> Se a HostGator recusar algum item abaixo (por exemplo, versão do PHP), a mesma estrutura funciona em uma VPS HostGator. Veja o final deste documento.

## 1. Preparar no cPanel

1. **Domínio e SSL**: em *SSL/TLS Status*, ative o AutoSSL para `wruchpersonal.com` e `www`.
2. **PHP**: em *MultiPHP Manager*, selecione **PHP 8.2 ou 8.3** para o domínio. Em *Select PHP Version → Extensions*, confirme `pdo_mysql`, `gd`, `fileinfo`, `curl`, `mbstring`, `openssl`, `exif` e `intl`.
3. **Limites de upload**: em *MultiPHP INI Editor*, defina `upload_max_filesize = 256M`, `post_max_size = 260M`, `max_execution_time = 120` e `memory_limit = 256M`. O arquivo `public/.user.ini` já pede isso, mas alguns planos exigem o ajuste pelo painel.
4. **Banco**: em *MySQL Databases*, crie o banco (ex.: `usuario_wruch`) e um usuário com **todas as permissões** nele.
5. **E-mail**: em *Email Accounts*, crie `nao-responda@wruchpersonal.com`. Em *Email Deliverability*, confirme que **SPF e DKIM** estão válidos; sem isso, os e-mails caem no spam.

## 2. Enviar os arquivos

Estrutura recomendada (o código fica fora da pasta pública):

```
/home/USUARIO/
├── wruch/               ← tudo, exceto o conteúdo de public/
│   ├── app/  cron/  database/  docs/  storage/  tests/
│   └── .env
└── public_html/         ← conteúdo da pasta public/ (index.php, assets/, sw.js, .htaccess, .user.ini, manifest…)
    └── wruch-root.php   ← crie com: <?php return '/home/USUARIO/wruch';
```

Pelo *Gerenciador de Arquivos* (envie o `.zip` e use *Extract*) ou por FTP/SFTP:

1. Envie o projeto para `/home/USUARIO/wruch/`.
2. Mova **o conteúdo** de `wruch/public/` para `public_html/` (inclua os arquivos ocultos `.htaccess` e `.user.ini`).
3. Crie `public_html/wruch-root.php` com a linha acima. O `.htaccess` bloqueia o acesso direto a ele.
4. Dê permissão de escrita em `wruch/storage` e subpastas (755 nas pastas; o PHP roda com o seu usuário).

## 3. Configurar o `.env`

```bash
cp /home/USUARIO/wruch/.env.example /home/USUARIO/wruch/.env
```

Preencha conforme o próprio `.env.example`: `APP_URL`, `APP_KEY` (gere em *Terminal* com `php ~/wruch/cron/generate-key.php`), `STORAGE_PATH`, banco, e-mail e `TRAINER_EMAILS`. Deixe os serviços externos para o [SERVICOS-EXTERNOS.md](SERVICOS-EXTERNOS.md).

## 4. Criar as tabelas

- **Com Terminal/SSH** (recomendado): `php ~/wruch/cron/migrate.php`
- **Sem terminal**: no phpMyAdmin, importe `database/schema.mysql.sql`

## 5. Cron (obrigatório)

Em *Cron Jobs*, adicione uma tarefa a cada 10 minutos (`*/10 * * * *`):

```
/usr/local/bin/php /home/USUARIO/wruch/cron/run.php >/dev/null 2>&1
```

O cron publica treinos agendados, envia lembretes e a fila de e-mails, agrupa avisos do chat, reconcilia pagamentos com o Mercado Pago e faz a manutenção.

Se o plano não oferecer cron por linha de comando, defina `CRON_HTTP_TOKEN` no `.env` e use um serviço externo de agendamento chamando `https://wruchpersonal.com/cron?token=SEU_TOKEN`.

## 6. Primeiro acesso

1. Abra `https://wruchpersonal.com/cadastro` e crie a conta com **o e-mail que está em `TRAINER_EMAILS`**.
2. Confirme pelo link recebido. A conta vira automaticamente a do Renan.
3. Em **Configurações → Operação**, clique em *Enviar e-mail de teste* e confira se o cron aparece como executado.
4. Desenvolvedores: inclua os e-mails em `DEVELOPER_EMAILS`, cadastre e confirme cada conta.
5. Opcional: `php ~/wruch/cron/seed-library.php` cria uma biblioteca inicial apenas com nomes e categorias comuns, sem instruções técnicas.

## 7. Atualizações futuras

1. Faça backup (veja [BACKUP-E-RECUPERACAO.md](BACKUP-E-RECUPERACAO.md)).
2. Substitua `app/`, `cron/`, `database/` e o conteúdo de `public_html/`, preservando `.env`, `storage/` e `wruch-root.php`.
3. Rode `php cron/migrate.php`.
4. Ao mudar CSS ou JS, altere `VERSION` em `public/sw.js`: o aplicativo instalado mostra “Nova versão disponível”.

## Deploy automatizado (opcional)

Com o *Git™ Version Control* do cPanel, crie o repositório em `/home/USUARIO/wruch` e adicione um `.cpanel.yml`:

```yaml
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/USUARIO/public_html/
    - /bin/cp -R public/. $DEPLOYPATH
    - /usr/local/bin/php cron/migrate.php
```

## Alternativa: VPS HostGator

A aplicação é a mesma. Instale Apache (ou Nginx + PHP-FPM), PHP 8.3 com as extensões listadas, MariaDB e Certbot. Aponte o *document root* para `public/` (nesse caso, `wruch-root.php` não é necessário) e configure o cron como acima. Com Nginx, use `try_files $uri /index.php?$query_string;` e bloqueie arquivos iniciados por ponto.
