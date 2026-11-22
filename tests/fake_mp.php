<?php
/**
 * Servidor falso da API do Mercado Pago, usado APENAS nos testes automatizados.
 * O estado de cada recurso fica em FAKE_MP_STATE (JSON) e é controlado pelo teste.
 */
$stateFile = getenv('FAKE_MP_STATE') ?: '/tmp/fake-mp-state.json';
$state = is_file($stateFile) ? json_decode((string) file_get_contents($stateFile), true) : [];
$state += ['preapprovals' => [], 'payments' => [], 'authorized' => [], 'calls' => [], 'seq' => 0];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode((string) file_get_contents('php://input'), true) ?: [];
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$state['calls'][] = ['method' => $method, 'path' => $path, 'idempotency' => $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null];
header('Content-Type: application/json');

$out = function (int $code, array $data) use (&$state, $stateFile) {
    file_put_contents($stateFile, json_encode($state));
    http_response_code($code);
    echo json_encode($data);
    exit;
};
if ($auth !== 'Bearer TEST-ACCESS-TOKEN') {
    $out(401, ['message' => 'invalid token']);
}
if ($method === 'POST' && $path === '/preapproval') {
    $id = 'pre' . (++$state['seq']);
    $state['preapprovals'][$id] = ['id' => $id, 'status' => 'pending', 'external_reference' => $body['external_reference'], 'payer_email' => $body['payer_email'], 'auto_recurring' => $body['auto_recurring'], 'reason' => $body['reason']];
    $out(201, $state['preapprovals'][$id] + ['init_point' => "https://www.mercadopago.com.br/subscriptions/checkout?preapproval_id={$id}"]);
}
if ($method === 'POST' && $path === '/checkout/preferences') {
    $id = 'pref' . (++$state['seq']);
    $out(201, ['id' => $id, 'init_point' => "https://www.mercadopago.com.br/checkout/v1/redirect?pref_id={$id}", 'external_reference' => $body['external_reference'], 'items' => $body['items']]);
}
if (preg_match('#^/preapproval/([\w-]+)$#', $path, $m)) {
    if (!isset($state['preapprovals'][$m[1]])) $out(404, ['message' => 'not found']);
    if ($method === 'PUT') {
        $state['preapprovals'][$m[1]] = array_merge($state['preapprovals'][$m[1]], $body);
    }
    $out(200, $state['preapprovals'][$m[1]]);
}
if ($method === 'GET' && $path === '/v1/payments/search') {
    $ref = $_GET['external_reference'] ?? '';
    $out(200, ['results' => array_values(array_filter($state['payments'], fn($p) => ($p['external_reference'] ?? '') === $ref))]);
}
if (preg_match('#^/v1/payments/(\w+)$#', $path, $m)) {
    isset($state['payments'][$m[1]]) ? $out(200, $state['payments'][$m[1]]) : $out(404, ['message' => 'not found']);
}
if (preg_match('#^/authorized_payments/(\w+)$#', $path, $m)) {
    isset($state['authorized'][$m[1]]) ? $out(200, $state['authorized'][$m[1]]) : $out(404, ['message' => 'not found']);
}
$out(404, ['message' => 'rota desconhecida ' . $path]);
