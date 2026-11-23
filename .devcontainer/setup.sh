#!/usr/bin/env bash
# Roda uma vez quando o Codespace é criado: instala o PHP, cria o .env de teste e prepara o banco.
set -e
cd "$(dirname "$0")/.."

if ! /usr/bin/php -m 2>/dev/null | grep -qi '^gd$'; then
  echo "▶ Instalando PHP e extensões..."
  sudo apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq php-cli php-gd php-sqlite3 php-mbstring php-curl php-xml unzip >/dev/null
fi
source .devcontainer/php.sh

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

$PHP cron/migrate.php
$PHP .devcontainer/contas-teste.php
echo "✔ Ambiente pronto."
