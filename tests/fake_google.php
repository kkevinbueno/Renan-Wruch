<?php
/**
 * Servidor falso do endpoint de token do Google, usado APENAS nos testes automatizados.
 * O teste envia como "code" as declarações (claims) do usuário em base64url; aqui elas viram um
 * id_token no mesmo formato JWT que o Google devolve. Em produção o app fala com o Google de verdade.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');
$b64 = fn(string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === '/token') {
    if (($_POST['client_secret'] ?? '') !== 'test-google-secret' || ($_POST['grant_type'] ?? '') !== 'authorization_code') {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client']);
        exit;
    }
    $claims = json_decode((string) base64_decode(strtr((string) ($_POST['code'] ?? ''), '-_', '+/')), true);
    if (!is_array($claims)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant']);
        exit;
    }
    $claims += ['iss' => 'https://accounts.google.com', 'aud' => $_POST['client_id'] ?? '', 'exp' => time() + 3600, 'iat' => time()];
    echo json_encode(['access_token' => 'x', 'id_token' => $b64('{"alg":"RS256"}') . '.' . $b64(json_encode($claims)) . '.' . $b64('sig'), 'token_type' => 'Bearer']);
    exit;
}
http_response_code(404);
echo json_encode(['error' => 'not_found']);
