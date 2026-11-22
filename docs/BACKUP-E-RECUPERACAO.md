# Backups e recuperação

O que precisa de backup:

| Item | Onde | Contém |
|---|---|---|
| Banco de dados | MySQL | Contas, anamneses, treinos, registros, mensagens, pagamentos, auditoria |
| Arquivos privados | `wruch/storage/uploads/` | Fotografias, vídeos, áudios e documentos |
| Configuração | `wruch/.env`, chave `.p8` da Apple | Segredos (guarde em local seguro, separado) |

## Rotina recomendada

1. **Backup automático da HostGator**: confira se o plano inclui o *JetBackup* no cPanel e com qual retenção.
2. **Backup próprio diário** (cron às 3h):

   ```bash
   0 3 * * * mysqldump --single-transaction -u USUARIO_DB -p'SENHA' usuario_wruch | gzip > ~/backups/db-$(date +\%F).sql.gz && tar -czf ~/backups/uploads-$(date +\%F).tar.gz -C ~/wruch/storage uploads && find ~/backups -mtime +30 -delete
   ```

   A pasta `~/backups` fica fora do `public_html`.
3. **Cópia externa semanal**: baixe ou sincronize `~/backups` para um armazenamento fora da HostGator (Google Drive do Renan, S3, Backblaze). As fotos são dados sensíveis: use armazenamento privado e, se possível, criptografe o arquivo (`gpg -c`).
4. **Teste de restauração a cada trimestre**: restaure em um subdomínio de teste e abra algumas fotos e treinos.

## Restaurar

```bash
gunzip < db-AAAA-MM-DD.sql.gz | mysql -u USUARIO_DB -p usuario_wruch
tar -xzf uploads-AAAA-MM-DD.tar.gz -C ~/wruch/storage
php ~/wruch/cron/migrate.php    # aplica migrações mais novas, se houver
```

Depois, confira o login, uma foto de avaliação, um treino publicado e o **Financeiro → Sincronizar** para alinhar os pagamentos ocorridos depois do backup.

## Incidentes

- **Senha vazada ou acesso indevido**: troque a senha (as outras sessões são encerradas) e confira **Configurações → Registros**.
- **Vazamento do `.env`**: gere um novo `APP_KEY` (os links de fotos antigos deixam de valer) e renove as credenciais do banco, do e-mail, do Google, da Apple e do Mercado Pago, além da assinatura do webhook.
- **LGPD**: incidentes com dados pessoais podem exigir comunicação à ANPD e aos titulares. Registre data, escopo e medidas tomadas.
