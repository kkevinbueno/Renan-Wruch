#!/usr/bin/env bash
# Prepara o Codespace: instala o PHP (se der), cria o .env de teste, prepara o banco e as contas de teste.
# Pode ser rodado de novo a qualquer momento sem estragar nada.
cd "$(dirname "$0")/.."

if ! /usr/bin/php -m 2>/dev/null | grep -qi '^gd$'; then
  echo "▶ Instalando PHP com as extensões do projeto (pode levar 1–2 minutos)..."
  if ! { sudo apt-get update -qq >/dev/null 2>&1 || true; sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq php-cli php-gd php-sqlite3 php-mbstring php-curl php-xml unzip >/dev/null 2>&1; }; then
    echo "  (não foi possível instalar pelo apt — seguindo com o PHP que já existe; só o redimensionamento de fotos pode não funcionar)"
  fi
fi
source .devcontainer/php.sh
echo "▶ Usando $($PHP -v | head -1)"

if [ ! -f .env ]; then
  echo "▶ Criando .env de teste..."
  URL="http://127.0.0.1:8080"
  if [ -n "${CODESPACE_NAME:-}" ]; then URL="https://${CODESPACE_NAME}-8080.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"; fi
  {
    echo "APP_ENV=local"
    echo "APP_DEBUG=true"
    echo "APP_URL=$URL"
    $PHP cron/generate-key.php | grep APP_KEY
    echo "FORCE_HTTPS=false"
    echo "DB_DRIVER=sqlite"
    echo "MAIL_TRANSPORT=log"
    echo "TRAINER_EMAILS=renanrdw@gmail.com"
    echo "DEVELOPER_EMAILS="
  } > .env
fi

mkdir -p storage/logs storage/uploads storage/cache storage/sessions
$PHP cron/migrate.php || { echo "✖ Falha ao preparar o banco (veja a mensagem acima)."; exit 1; }
$PHP .devcontainer/contas-teste.php || echo "  (contas de teste não criadas — confira APP_ENV=local e DB_DRIVER=sqlite no .env)"
echo "✔ Ambiente pronto."
