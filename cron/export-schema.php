<?php
/** Gera database/schema.mysql.sql para importar pelo phpMyAdmin. */
require dirname(__DIR__) . '/app/bootstrap.php';
file_put_contents(APP_ROOT . '/database/schema.mysql.sql', Migrator::exportMysql());
echo "database/schema.mysql.sql gerado.\n";
