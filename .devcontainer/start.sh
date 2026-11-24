#!/usr/bin/env bash
# Liga (ou religa) o site na porta 8080 e as tarefas agendadas. Roda sempre que o Codespace abre.
cd "$(dirname "$0")/.."
source .devcontainer/php.sh
mkdir -p storage/logs
pkill -f "php -S 0.0.0.0:8080" 2>/dev/null || true
pkill -f "wruch-cron-loop" 2>/dev/null || true
sleep 0.5
# Garante que o banco está atualizado (rápido quando não há nada novo).
$PHP cron/migrate.php >/dev/null 2>&1 || $PHP cron/migrate.php
nohup $PHP -S 0.0.0.0:8080 -t public public/router.php > storage/logs/servidor.log 2>&1 &
nohup bash -c "exec -a wruch-cron-loop bash -c 'sleep 20; while true; do $PHP cron/run.php >> storage/logs/cron.log 2>&1; sleep 60; done'" > /dev/null 2>&1 &
sleep 1.5
code=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/ 2>/dev/null)
if [ "$code" = "200" ]; then
  echo "✔ Site rodando na porta 8080 (aba PORTAS → 8080 → abrir no navegador)."
else
  echo "✖ O site respondeu com erro ($code). Última mensagem registrada:"
  tail -n 40 storage/logs/app-*.log 2>/dev/null | grep -o '"message":"[^"]*"\|ERROR[^{]*' | tail -n 3
  echo "  Mande esta mensagem para o Claude."
fi
