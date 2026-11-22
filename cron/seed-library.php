<?php
/** OPCIONAL: cria uma biblioteca inicial apenas com nomes/categorias comuns (sem instruções). */
require dirname(__DIR__) . '/app/bootstrap.php';
echo Seeder::starterLibrary() . " exercícios adicionados.\n";
