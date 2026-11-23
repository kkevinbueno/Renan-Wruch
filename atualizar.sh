#!/usr/bin/env bash
# Atualiza o projeto a partir de um .zip novo, testa, envia para o GitHub e religa o site.
#
#   1. Arraste o wruch-personal.zip para a raiz do projeto no Codespace
#   2. No terminal:  bash atualizar.sh
#
# Opções:  bash atualizar.sh outro-arquivo.zip   |   bash atualizar.sh --sem-testes
set -euo pipefail
cd "$(dirname "$0")"
if [ -x /usr/bin/php ] && /usr/bin/php -m 2>/dev/null | grep -qi '^gd$'; then PHP=/usr/bin/php; else PHP=php; fi

TESTES=1; ZIP=""
for a in "$@"; do
  case "$a" in
    --sem-testes) TESTES=0 ;;
    *) ZIP="$a" ;;
  esac
done
[ -z "$ZIP" ] && ZIP=$(ls -t ./*.zip 2>/dev/null | head -1 || true)
if [ -z "$ZIP" ] || [ ! -f "$ZIP" ]; then
  echo "✖ Nenhum .zip encontrado. Arraste o wruch-personal.zip para a pasta do projeto e rode de novo."
  exit 1
fi
echo "▶ Usando $ZIP"

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
unzip -q "$ZIP" -d "$TMP"
SRC="$TMP"
[ -d "$TMP/wruch-personal" ] && SRC="$TMP/wruch-personal"
[ -f "$SRC/public/index.php" ] || { echo "✖ O zip não parece ser do projeto Wruch Personal."; exit 1; }

echo "▶ Copiando arquivos (o .env, os dados e o histórico do git ficam intactos)..."
shopt -s dotglob
for item in "$SRC"/*; do
  nome=$(basename "$item")
  case "$nome" in
    .git|.env|.github|.vscode|*.zip) continue ;;
    storage) cp -an "$item/." storage/ 2>/dev/null || cp -a "$item" . ;;  # só cria o que falta
    *) rm -rf "./$nome"; cp -a "$item" "./$nome" ;;
  esac
done
shopt -u dotglob

echo "▶ Atualizando o banco..."
$PHP cron/migrate.php

if [ "$TESTES" = 1 ]; then
  echo "▶ Rodando os testes (leva cerca de 1 minuto)..."
  if ! $PHP tests/run.php > storage/logs/testes.log 2>&1; then
    tail -20 storage/logs/testes.log
    echo "✖ Testes falharam — nada foi enviado ao GitHub. Detalhes em storage/logs/testes.log"
    exit 1
  fi
  tail -1 storage/logs/testes.log
fi

# arquivo de contas de teste antigo não vai para o GitHub
git rm -q --cached --ignore-unmatch cron/contas-teste.php
rm -f cron/contas-teste.php

git add -A
if git diff --cached --quiet; then
  echo "▶ Nada mudou desde a última atualização."
else
  git commit -q -m "Atualização do site — $(date '+%d/%m/%Y %H:%M')"
  echo "▶ Enviando para o GitHub..."
  git push -q
  echo "✔ Enviado."
fi

rm -f "$ZIP"
bash .devcontainer/start.sh
echo "✔ Tudo pronto."
