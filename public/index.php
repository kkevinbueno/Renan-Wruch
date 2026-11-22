<?php
/**
 * Ponto de entrada único do Wruch Personal.
 * Em hospedagem compartilhada, o código fica FORA da pasta pública. Se a pasta do aplicativo não estiver
 * logo acima desta, crie "wruch-root.php" ao lado deste arquivo retornando o caminho absoluto, ex.:
 *   <?php return '/home/usuario/wruch';
 */
$root = is_file(__DIR__ . '/wruch-root.php') ? require __DIR__ . '/wruch-root.php' : dirname(__DIR__);
require $root . '/app/web.php';
