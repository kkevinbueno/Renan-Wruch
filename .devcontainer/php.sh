# Escolhe o PHP: o do sistema (com a extensão gd, instalado pelo setup.sh) ou, se não houver, o que estiver no PATH.
if [ -x /usr/bin/php ] && /usr/bin/php -m 2>/dev/null | grep -qi '^gd$'; then PHP=/usr/bin/php; else PHP=$(command -v php); fi
