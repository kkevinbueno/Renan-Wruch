<?php
/**
 * Tarefas agendadas do Wruch Personal.
 * cPanel (HostGator) → Cron Jobs → a cada 10 minutos:
 *   /usr/local/bin/php /home/USUARIO/wruch/cron/run.php >/dev/null 2>&1
 */
require dirname(__DIR__) . '/app/bootstrap.php';
foreach (Cron::run() as $line) {
    echo '[' . gmdate('Y-m-d H:i:s') . "] {$line}\n";
}
