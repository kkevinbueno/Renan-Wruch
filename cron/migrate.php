<?php
/** Cria/atualiza as tabelas do banco: php cron/migrate.php */
require dirname(__DIR__) . '/app/bootstrap.php';
$ran = Migrator::migrate(fn($m) => print($m . "\n"));
echo $ran ? "Concluído.\n" : "Banco já está atualizado.\n";
