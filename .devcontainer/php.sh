# Escolhe o PHP com as extensões do projeto (o do sistema, instalado pelo setup.sh).
if [ -x /usr/bin/php ] && /usr/bin/php -m 2>/dev/null | grep -qi '^gd$'; then PHP=/usr/bin/php; else PHP=php; fi
