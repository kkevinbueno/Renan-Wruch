# Checklist de validação para publicação

## Servidor
- [ ] PHP 8.2+ com `pdo_mysql`, `gd`, `fileinfo`, `curl`, `mbstring`, `openssl`, `exif`
- [ ] SSL ativo; `http://` redireciona para `https://`
- [ ] Código em `~/wruch`, só `public/` dentro de `public_html`, e `wruch-root.php` criado
- [ ] `https://wruchpersonal.com/.env`, `/app/config.php` e `/storage/` retornam 403/404
- [ ] `storage/` gravável; `APP_DEBUG=false`; `APP_KEY` gerado
- [ ] Migrações aplicadas (`php cron/migrate.php`)
- [ ] Cron a cada 10 min; “Última execução do cron” recente em Configurações → Operação
- [ ] Limites de upload ajustados (fotos de celular de 5–15 MB, vídeos)

## E-mail
- [ ] E-mail de teste recebido (fora do spam)
- [ ] SPF e DKIM válidos
- [ ] Cadastro → link de confirmação chega e funciona

## Contas e permissões
- [ ] Renan cadastrado com o e-mail de `TRAINER_EMAILS` e já dentro do Painel
- [ ] Contas de desenvolvedor confirmadas e alternando as interfaces
- [ ] Aluno de teste: pendente sem acesso; aprovado → avaliação inicial → mensagem “Seu treino está sendo preparado pelo Renan.”
- [ ] Aluno não abre `/painel`, outra conversa nem a foto de outro aluno

## Integrações
- [ ] Google: login, criação de conta e tela de consentimento
- [ ] Apple: login (testar no iPhone/Safari)
- [ ] Mercado Pago com credenciais de teste: plano criado, assinatura recorrente e Pix aprovados pelo webhook; recusa tratada; cancelamento de renovação
- [ ] Trocar para credenciais de produção e atualizar a URL e a assinatura secreta do webhook
- [ ] Página de retorno do pagamento **não** ativa nada sem confirmação

## Funcionalidades
- [ ] Biblioteca: criar exercício com link de vídeo e com upload
- [ ] Editor: adicionar, reordenar, salvar, publicar agora e agendar (confirmar a publicação automática)
- [ ] Aluno registra séries, sai e volta (retomada) e conclui
- [ ] Pedido de mudança de dia aprovado aparece no calendário
- [ ] Avaliação física: lembrete, envio, revisão e próxima data calculada
- [ ] Chat: texto, foto, PDF, vídeo e áudio gravado no celular; aviso por e-mail agrupado
- [ ] Autoavaliação com o horário do lembrete; alimentação e hidratação

## PWA e dispositivos
- [ ] Android/Chrome: “Instalar app”; iPhone/Safari: “Adicionar à Tela de Início”
- [ ] Modo avião mostra a página offline; ao publicar nova versão (mudar `VERSION` no `sw.js`) aparece “Nova versão disponível”
- [ ] Teste em celular, tablet e computador sem rolagem horizontal

## Conteúdo e LGPD
- [ ] Textos da página pública revisados pelo Renan (Configurações → Página pública), sem depoimentos, certificações ou resultados não verificados
- [ ] Foto do Renan enviada
- [ ] Política de Privacidade e Termos revisados juridicamente, com a identificação do controlador
- [ ] Prazo de retenção decidido (Configurações → Privacidade)
- [ ] Backup diário configurado e restauração testada

## Testes automatizados
- [ ] `php tests/run.php` sem falhas
