<?php
declare(strict_types=1);

final class WebhookController
{
    public function mercadopago(): void
    {
        $raw = (string) file_get_contents('php://input');
        $code = Billing::handleWebhook($_GET, $_SERVER, $raw);
        http_response_code($code);
        header('Content-Type: text/plain');
        echo $code === 200 ? 'ok' : 'erro';
    }

    /** Alternativa ao cron por linha de comando: /cron?token=CRON_HTTP_TOKEN */
    public function cron(): void
    {
        $token = (string) config('cron.http_token');
        if ($token === '' || !hash_equals($token, (string) input('token', ''))) {
            abort(404);
        }
        header('Content-Type: text/plain; charset=utf-8');
        foreach (Cron::run() as $line) {
            echo $line, "\n";
        }
    }
}
