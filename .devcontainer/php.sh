# Escolhe o PHP para o projeto: precisa ser 8.1 ou mais novo; de preferência com a extensão gd (fotos).
_wp_ok() { "$1" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' 2>/dev/null; }
_wp_gd() { "$1" -m 2>/dev/null | grep -qi '^gd$'; }
PHP=""
for c in /usr/bin/php8.3 /usr/bin/php8.2 /usr/bin/php8.1 /usr/bin/php "$(command -v php)"; do
  [ -n "$c" ] && [ -x "$c" ] && _wp_ok "$c" && _wp_gd "$c" && { PHP=$c; break; }
done
if [ -z "$PHP" ]; then
  for c in "$(command -v php)" /usr/local/php/current/bin/php /usr/bin/php8.3 /usr/bin/php; do
    [ -n "$c" ] && [ -x "$c" ] && _wp_ok "$c" && { PHP=$c; break; }
  done
fi
[ -z "$PHP" ] && PHP=php
