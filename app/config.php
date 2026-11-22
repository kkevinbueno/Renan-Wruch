<?php
declare(strict_types=1);

/**
 * Configuração central. Todos os segredos vêm do arquivo .env (fora da pasta pública).
 * Veja .env.example para a descrição de cada variável.
 */
$GLOBALS['__config'] = [
    'app' => [
        'name' => Env::get('APP_NAME', 'Wruch Personal'),
        'url' => rtrim((string) Env::get('APP_URL', 'http://localhost:8080'), '/'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'key' => (string) Env::get('APP_KEY', ''),
        'timezone' => Env::get('APP_TIMEZONE', 'America/Sao_Paulo'),
        'force_https' => Env::bool('FORCE_HTTPS', true),
        'storage' => Env::get('STORAGE_PATH', APP_ROOT . '/storage'),
        'consent_version' => '2026-11',
    ],
    'db' => [
        'driver' => Env::get('DB_DRIVER', 'mysql'),
        'host' => Env::get('DB_HOST', 'localhost'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', ''),
        'user' => Env::get('DB_USER', ''),
        'pass' => Env::get('DB_PASS', ''),
        'sqlite_path' => Env::get('DB_SQLITE_PATH', APP_ROOT . '/storage/database.sqlite'),
    ],
    'roles' => [
        // Papéis atribuídos SOMENTE por configuração no servidor, nunca por formulário público.
        'trainer_emails' => Env::list('TRAINER_EMAILS'),
        'developer_emails' => Env::list('DEVELOPER_EMAILS'),
    ],
    'mail' => [
        'transport' => Env::get('MAIL_TRANSPORT', 'smtp'), // smtp | mail | log
        'host' => Env::get('MAIL_HOST', ''),
        'port' => (int) Env::get('MAIL_PORT', '465'),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'ssl'), // ssl | tls | none
        'user' => Env::get('MAIL_USER', ''),
        'pass' => Env::get('MAIL_PASS', ''),
        'from' => Env::get('MAIL_FROM', 'nao-responda@wruchpersonal.com'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'Wruch Personal'),
        'trainer_notify' => Env::get('TRAINER_NOTIFY_EMAIL', 'renanrdw@gmail.com'),
    ],
    'google' => [
        'client_id' => Env::get('GOOGLE_CLIENT_ID', ''),
        'client_secret' => Env::get('GOOGLE_CLIENT_SECRET', ''),
    ],
    'apple' => [
        'client_id' => Env::get('APPLE_CLIENT_ID', ''), // Services ID
        'team_id' => Env::get('APPLE_TEAM_ID', ''),
        'key_id' => Env::get('APPLE_KEY_ID', ''),
        'private_key_path' => Env::get('APPLE_PRIVATE_KEY_PATH', ''),
    ],
    'mercadopago' => [
        'access_token' => Env::get('MP_ACCESS_TOKEN', ''),
        'public_key' => Env::get('MP_PUBLIC_KEY', ''),
        'webhook_secret' => Env::get('MP_WEBHOOK_SECRET', ''),
        'api_base' => rtrim((string) Env::get('MP_API_BASE', 'https://api.mercadopago.com'), '/'),
        'statement_descriptor' => Env::get('MP_STATEMENT_DESCRIPTOR', 'WRUCHPERSONAL'),
    ],
    'cron' => [
        // Permite disparar o cron por URL quando o painel não oferece cron por linha de comando.
        'http_token' => Env::get('CRON_HTTP_TOKEN', ''),
    ],
];
