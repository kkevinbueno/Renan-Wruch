<?php
/**
 * Importa (ou completa) a biblioteca de exercícios a partir de app/data.
 * Pode ser executado quantas vezes quiser: não cria duplicatas nem sobrescreve edições do Renan.
 * Uso: php cron/import-exercises.php
 */
require dirname(__DIR__) . '/app/bootstrap.php';
$r = ExerciseLibrary::import();
Settings::set('exercise_catalog_version', ExerciseLibrary::CATALOG_VERSION);
echo "Catálogo: {$r['total']} exercícios · novos: {$r['inserted']} · completados: {$r['updated']}\n";
