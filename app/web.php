<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// HTTPS obrigatório em produção.
if (config('app.force_https') && !Session::isHttps() && PHP_SAPI !== 'cli-server') {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? parse_url(config('app.url'), PHP_URL_HOST)) . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// Cabeçalhos de segurança.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=(self), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; connect-src 'self'; form-action 'self' https://accounts.google.com https://appleid.apple.com https://www.mercadopago.com.br https://www.mercadopago.com https://sandbox.mercadopago.com.br; base-uri 'self'; frame-ancestors 'none'");
if (Session::isHttps()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

Session::start();

// Publicações agendadas também são verificadas a cada acesso (caso o cron atrase).
try {
    if (random_int(1, 5) === 1 || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/app')) {
        Workouts::publishDue();
    }
} catch (PDOException $e) {
    // Banco indisponível/não migrado: a rota exibirá o erro adequado.
}

View::share('authUser', Auth::user());

require __DIR__ . '/routes.php';

Router::dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
