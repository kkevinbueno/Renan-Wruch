#!/usr/bin/env bash
# Liga (ou religa) o site na porta 8080 e as tarefas agendadas. Roda sempre que o Codespace abre.
cd "$(dirname "$0")/.."
source .devcontainer/php.sh
mkdir -p storage/logs
pkill -f "php -S 0.0.0.0:8080" 2>/dev/null || true
pkill -f "wruch-cron-loop" 2>/dev/null || true
nohup $PHP -S 0.0.0.0:8080 -t public public/router.php > storage/logs/servidor.log 2>&1 &
nohup bash -c "exec -a wruch-cron-loop bash -c 'while true; do $PHP cron/run.php >> storage/logs/cron.log 2>&1; sleep 60; done'" > /dev/null 2>&1 &
sleep 1
echo "✔ Site rodando na porta 8080 (aba PORTAS → 8080 → abrir no navegador)."
