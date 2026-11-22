<?php
/** Gera um valor seguro para APP_KEY e CRON_HTTP_TOKEN. */
echo 'APP_KEY=' . bin2hex(random_bytes(32)) . "\n";
echo 'CRON_HTTP_TOKEN=' . bin2hex(random_bytes(24)) . "\n";
