<?php
/**
 * Inicialização do Wruch Personal.
 * Carrega variáveis de ambiente, configuração, autoload, tratamento de erros e sessão.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('APP_VERSION', '1.0.0');

require APP_DIR . '/lib/Env.php';
Env::load(getenv('WRUCH_ENV_FILE') ?: APP_ROOT . '/.env');

require APP_DIR . '/config.php';
require APP_DIR . '/lib/helpers.php';

spl_autoload_register(function (string $class): void {
    foreach ([APP_DIR . '/lib/', APP_DIR . '/controllers/'] as $dir) {
        $file = $dir . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', storage_path('logs/php-error.log'));

set_exception_handler(function (Throwable $e): void {
    Log::error('Exceção não tratada: ' . $e->getMessage(), [
        'file' => $e->getFile() . ':' . $e->getLine(),
        'trace' => substr($e->getTraceAsString(), 0, 3000),
    ]);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, (string) $e . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (is_json_request()) {
        echo json_encode(['ok' => false, 'error' => 'Erro interno. Tente novamente em instantes.']);
        return;
    }
    $debug = config('app.debug') ? $e : null;
    include APP_DIR . '/views/shared/error500.php';
});

set_error_handler(function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});
