<?php
/**
 * Testes automatizados de ponta a ponta do Wruch Personal.
 *   php tests/run.php            (usa SQLite temporário, servidor embutido do PHP e um Mercado Pago falso)
 *
 * Cobre: autenticação, permissões, uploads, publicação de treinos (imediata e agendada),
 * avaliações físicas, chat, notificações, pagamentos/webhooks e acesso de desenvolvedores (fluxos A–F).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/wruch-tests-' . getmypid();
@mkdir($tmp . '/storage', 0777, true);
$port = 8190 + (getmypid() % 500);
$mpPort = $port + 1;
$env = [
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'true', 'APP_URL' => "http://127.0.0.1:{$port}",
    'APP_KEY' => 'test-app-key-' . bin2hex(random_bytes(8)), 'FORCE_HTTPS' => 'false',
    'DB_DRIVER' => 'sqlite', 'DB_SQLITE_PATH' => "{$tmp}/db.sqlite", 'STORAGE_PATH' => "{$tmp}/storage",
    'MAIL_TRANSPORT' => 'log', 'TRAINER_EMAILS' => 'renanrdw@gmail.com', 'DEVELOPER_EMAILS' => 'dev@wruch.test',
    'MP_ACCESS_TOKEN' => 'TEST-ACCESS-TOKEN', 'MP_WEBHOOK_SECRET' => 'whsec_test', 'MP_API_BASE' => "http://127.0.0.1:{$mpPort}",
    'FAKE_MP_STATE' => "{$tmp}/mp.json", 'WRUCH_ENV_FILE' => "{$tmp}/none.env", 'CRON_HTTP_TOKEN' => 'crontoken123',
];
foreach ($env as $k => $v) putenv("{$k}={$v}");

require $root . '/app/bootstrap.php';
Migrator::migrate();

$procEnv = array_merge(getenv(), $env);
$spawn = function (string $cmd) use ($procEnv, $root) {
    return proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes, $root, $procEnv);
};
$server = $spawn("exec php -S 127.0.0.1:{$port} -t public public/router.php");
$mp = $spawn("exec php -S 127.0.0.1:{$mpPort} tests/fake_mp.php");
register_shutdown_function(function () use ($server, $mp, $tmp, $port) {
    if (getenv('WRUCH_TEST_KEEP')) { // mantém o servidor para inspeção visual
        echo "\nServidor mantido em http://127.0.0.1:{$port} (dados em {$tmp})\n";
        return;
    }
    proc_terminate($server);
    proc_terminate($mp);
    exec('rm -rf ' . escapeshellarg($tmp));
});
for ($i = 0; $i < 50; $i++) {
    if (@fsockopen('127.0.0.1', $port) && @fsockopen('127.0.0.1', $mpPort)) break;
    usleep(100000);
}

// ───────────── Utilitários ─────────────
final class Client
{
    public string $jar;
    public string $csrf = '';
    public array $last = [];
    public function __construct(public string $base, string $name)
    {
        $this->jar = sys_get_temp_dir() . "/wruch-jar-{$name}-" . getmypid();
        @unlink($this->jar);
    }
    public function req(string $method, string $path, array|string|null $data = null, array $headers = [], bool $follow = false): array
    {
        $ch = curl_init($this->base . $path);
        $h = ['X-Forwarded-Proto: http'];
        foreach ($headers as $k => $v) $h[] = "{$k}: {$v}";
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_FOLLOWLOCATION => $follow, CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => 30]);
        if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $raw = (string) curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $body = substr($raw, $info['header_size']);
        $headersRaw = substr($raw, 0, $info['header_size']);
        preg_match('/^Location:\s*(.+)$/mi', $headersRaw, $m);
        if (preg_match('/data-csrf="([^"]+)"/', $body, $c)) $this->csrf = html_entity_decode($c[1]);
        return $this->last = ['status' => (int) $info['http_code'], 'body' => $body, 'location' => trim($m[1] ?? ''), 'headers' => $headersRaw, 'json' => json_decode($body, true)];
    }
    public function get(string $p, bool $follow = false): array { return $this->req('GET', $p, null, [], $follow); }
    public function post(string $p, array $data = [], bool $follow = false): array
    {
        if ($this->csrf === '') $this->get('/entrar');
        $data['_csrf'] = $data['_csrf'] ?? $this->csrf;
        return $this->req('POST', $p, http_build_query($data), ['Content-Type' => 'application/x-www-form-urlencoded'], $follow);
    }
    public function postJson(string $p, array $data): array
    {
        return $this->req('POST', $p, json_encode($data), ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-CSRF-Token' => $this->csrf, 'X-Requested-With' => 'fetch']);
    }
    public function upload(string $p, array $fields, array $files): array
    {
        $data = $fields;
        foreach ($files as $k => [$path, $mime, $name]) $data[$k] = new CURLFile($path, $mime, $name);
        return $this->req('POST', $p, $data, ['Accept' => 'application/json', 'X-CSRF-Token' => $this->csrf, 'X-Requested-With' => 'fetch']);
    }
}

$results = ['pass' => 0, 'fail' => 0, 'errors' => []];
$current = '';
function ok(bool $cond, string $msg): void
{
    global $results, $current;
    if ($cond) { $results['pass']++; return; }
    $results['fail']++;
    $results['errors'][] = "[{$current}] {$msg}";
    echo "   ✗ {$msg}\n";
}
function eq(mixed $a, mixed $b, string $msg): void { ok($a == $b, $msg . ' (esperado ' . var_export($b, true) . ', obtido ' . var_export($a, true) . ')'); }
function section(string $name): void { global $current; $current = $name; echo "\n▶ {$name}\n"; }
function db(string $sql, array $p = []): array { DB::reset(); return DB::all($sql, $p); }
function dbv(string $sql, array $p = []): mixed { DB::reset(); return DB::val($sql, $p); }
function mailLink(string $to, string $template): ?string
{
    $t = dbv('SELECT text_body FROM email_queue WHERE to_email = ? AND template = ? ORDER BY id DESC LIMIT 1', [$to, $template]);
    return $t && preg_match('~https?://\S+~', (string) $t, $m) ? $m[0] : null;
}
function pathOf(string $url): string { $u = parse_url($url); return ($u['path'] ?? '/') . (isset($u['query']) ? '?' . $u['query'] : ''); }
function makeJpeg(string $file, int $w = 600, int $h = 800): string
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 200, 200, 210));
    imagejpeg($im, $file, 80);
    return $file;
}
function cron(array $extraEnv = []): string
{
    global $procEnv, $root;
    $envStr = '';
    foreach ($extraEnv as $k => $v) $envStr .= $k . '=' . escapeshellarg((string) $v) . ' ';
    $exports = '';
    foreach ($procEnv as $k => $v) if (is_string($v) && preg_match('/^(APP_|DB_|STORAGE|MAIL_|TRAINER|DEVELOPER|MP_|FAKE_|WRUCH_|CRON_)/', $k)) $exports .= $k . '=' . escapeshellarg($v) . ' ';
    return (string) shell_exec("cd {$root} && env {$exports}{$envStr} php cron/run.php 2>&1");
}
function mpState(?array $set = null): array
{
    $f = getenv('FAKE_MP_STATE');
    if ($set !== null) file_put_contents($f, json_encode($set));
    return is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
}
function mpWebhook(Client $c, string $type, string $id, string $secret = 'whsec_test', ?string $notifId = null): array
{
    $reqId = bin2hex(random_bytes(8));
    $ts = (string) time();
    $manifest = "id:" . strtolower($id) . ";request-id:{$reqId};ts:{$ts};";
    $sig = hash_hmac('sha256', $manifest, $secret);
    $body = json_encode(['id' => $notifId ?? random_int(1, PHP_INT_MAX), 'type' => $type, 'action' => $type . '.updated', 'data' => ['id' => $id]]);
    return $c->req('POST', "/webhooks/mercadopago?data.id={$id}&type={$type}", $body, ['Content-Type' => 'application/json', 'x-signature' => "ts={$ts},v1={$sig}", 'x-request-id' => $reqId]);
}

$base = "http://127.0.0.1:{$port}";
$img = makeJpeg("{$tmp}/foto.jpg");

// ───────────── Testes unitários ─────────────
section('Unidades: assinatura de webhook, lembretes, JWT da Apple, valores');
$secret = 'segredo';
$manifest = 'id:123abc;request-id:req-1;ts:1700000000;';
ok(MercadoPago::validSignature('ts=1700000000,v1=' . hash_hmac('sha256', $manifest, $secret), 'req-1', '123ABC', $secret), 'assinatura válida aceita (id alfanumérico em minúsculas)');
ok(!MercadoPago::validSignature('ts=1700000000,v1=deadbeef', 'req-1', '123abc', $secret), 'assinatura inválida recusada');
ok(!MercadoPago::validSignature('', 'req-1', '123abc', $secret), 'assinatura ausente recusada');
eq(Cron::reminderOffsets(3, 2), [3, 0], 'lembretes D-3 e D-0');
eq(Cron::reminderOffsets(6, 3), [6, 3, 0], 'três lembretes espaçados');
eq(Cron::reminderOffsets(5, 1), [5], 'um lembrete');
eq(parse_money('1.234,56'), 123456, 'conversão de moeda BR');
eq(money(9990), 'R$ 99,90', 'formatação de moeda');
$key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
openssl_sign('dados', $der, $key, OPENSSL_ALGO_SHA256);
$raw = OAuth::derToRaw($der);
eq(strlen($raw), 64, 'assinatura ES256 convertida para 64 bytes (R||S)');
$toDer = function (string $raw): string {
    $enc = function (string $i) { $i = ltrim($i, "\x00"); if (ord($i[0]) > 0x7f) $i = "\x00" . $i; return "\x02" . chr(strlen($i)) . $i; };
    $seq = $enc(substr($raw, 0, 32)) . $enc(substr($raw, 32));
    return "\x30" . chr(strlen($seq)) . $seq;
};
eq(openssl_verify('dados', $toDer($raw), openssl_pkey_get_details($key)['key'], OPENSSL_ALGO_SHA256), 1, 'R||S volta a validar com a chave pública');
ok(video_embed_url('https://youtu.be/dQw4w9WgXcQ') === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'incorporação de vídeo do YouTube');
ok(e('<script>') === '&lt;script&gt;', 'escape de HTML');

// ───────────── Fluxo A ─────────────
section('Fluxo A — novo aluno: cadastro → e-mail → aprovação → avaliação inicial → treino → execução');
$renan = new Client($base, 'renan');
$aluno = new Client($base, 'aluno');
$intruso = new Client($base, 'intruso');

$aluno->get('/cadastro');
$r = $aluno->post('/cadastro', ['name' => 'Ana <script>alert(1)</script> Souza', 'email' => 'ana@example.com', 'password' => 'senhaforte1', 'password_confirm' => 'senhaforte1']);
ok(str_contains($aluno->get('/cadastro')['body'], 'aceitar os Termos'), 'cadastro sem consentimento é recusado');
$r = $aluno->post('/cadastro', ['name' => 'Ana <script>alert(1)</script> Souza', 'email' => 'ana@example.com', 'password' => 'senhaforte1', 'password_confirm' => 'senhaforte1', 'consent_terms' => 1, 'consent_health' => 1, 'role' => 'trainer']);
eq($r['location'], "/confirmar-email", 'após cadastro pede confirmação de e-mail');
eq(dbv("SELECT role FROM users WHERE email = 'ana@example.com'"), 'student', 'campo "role" enviado no formulário é ignorado');
eq($aluno->get('/app')['location'], "/confirmar-email", 'sem e-mail confirmado não acessa a plataforma');
$link = mailLink('ana@example.com', 'verify_email');
ok((bool) $link, 'e-mail de confirmação enfileirado com link');
eq(dbv("SELECT status FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'verify_email'"), 'sent', 'e-mail de confirmação enviado imediatamente');
$r = $aluno->get(pathOf($link));
ok(str_contains($r['location'], '/app'), 'link confirma o e-mail');
eq($aluno->get('/app')['location'], "/aguardando", 'aluno pendente vai para "Cadastro em análise"');
ok(str_contains($aluno->get('/aguardando')['body'], 'Cadastro em análise'), 'página de aguardo exibida');
ok(!str_contains($aluno->get('/aguardando')['body'], '<script>alert(1)'), 'nome com HTML é exibido escapado (XSS)');
eq($aluno->get('/painel')['location'], "/app", 'aluno não acessa o painel do Renan');
$anaId = (int) dbv("SELECT id FROM users WHERE email = 'ana@example.com'");
eq($aluno->req('GET', "/api/mensagens/{$anaId}", null, ['Accept' => 'application/json'])['status'], 403, 'pendente não acessa o chat');

// Renan
$renan->get('/cadastro');
$renan->post('/cadastro', ['name' => 'Renan Wruch', 'email' => 'renanrdw@gmail.com', 'password' => 'renanforte1', 'password_confirm' => 'renanforte1', 'consent_terms' => 1, 'consent_health' => 1]);
eq(dbv("SELECT role FROM users WHERE email = 'renanrdw@gmail.com'"), 'student', 'e-mail do Renan sem confirmação NÃO recebe permissão de treinador');
$renan->get(pathOf(mailLink('renanrdw@gmail.com', 'verify_email')));
eq(dbv("SELECT role FROM users WHERE email = 'renanrdw@gmail.com'"), 'trainer', 'após confirmação, papel de treinador vem da configuração do servidor');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'renanrdw@gmail.com' AND template = 'signup_pending'"), 'Renan notificado do novo cadastro');
$r = $renan->get('/painel');
eq($r['status'], 200, 'Renan acessa o painel');
ok(str_contains($r['body'], 'aguardando aprovação') && str_contains($r['body'], 'Cadastro novo'), 'painel mostra cadastros aguardando aprovação');
$renan->get("/painel/alunos/{$anaId}");
$renan->post("/painel/alunos/{$anaId}/aprovar", ['note' => 'Bem-vinda']);
eq(dbv('SELECT status FROM users WHERE id = ?', [$anaId]), 'active', 'Renan aprova o cadastro');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'approved'"), 'aluno avisado da aprovação por e-mail');

eq($aluno->get('/app')['location'], "/app/avaliacao-inicial", 'aprovado precisa concluir a avaliação inicial');
$aluno->get('/app/avaliacao-inicial');
$r = $aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'front'], ['photo' => [$img, 'image/jpeg', 'frente.jpg']]);
ok(($r['json']['ok'] ?? false) === true, 'foto de frente enviada');
file_put_contents("{$tmp}/fake.jpg", "<?php echo 'x'; ?>");
$r = $aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'side'], ['photo' => ["{$tmp}/fake.jpg", 'image/jpeg', 'lado.jpg']]);
eq($r['status'], 422, 'arquivo PHP disfarçado de imagem é recusado');
file_put_contents("{$tmp}/svg.svg", '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
eq($aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'side'], ['photo' => ["{$tmp}/svg.svg", 'image/svg+xml', 'x.svg']])['status'], 422, 'SVG (risco de XSS) é recusado');
$aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'side'], ['photo' => [$img, 'image/jpeg', 'lado.jpg']]);
$r = $aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'front'], ['photo' => [$img, 'image/jpeg', 'frente2.jpg']]);
eq((int) dbv("SELECT COUNT(*) FROM files WHERE context = 'anamnesis' AND kind = 'front' AND deleted_at IS NULL"), 1, 'substituir a foto mantém apenas uma de frente');
$photoId = (int) $r['json']['id'];
$stored = db('SELECT * FROM files WHERE id = ?', [$photoId])[0];
ok(!str_starts_with(realpath(Files::fullPath($stored)) ?: '', realpath($root . '/public')), 'fotos ficam fora da pasta pública');
eq($stored['mime'], 'image/jpeg', 'imagem reprocessada como JPEG');

$answers = ['full_name' => 'Ana Souza', 'birth_date' => '1995-04-10', 'phone' => '19999990000', 'goals' => ['performance'], 'goal_details' => 'Melhorar no beach tennis',
    'modalities' => ['beach-tennis', 'musculacao'], 'main_modality' => 'beach-tennis', 'training_experience' => '6m2a', 'week_routine' => 'Trabalho das 9 às 18', 'available_days' => ['1', '3', '5'],
    'session_time' => '60', 'limitations' => 'nenhuma', 'injuries' => 'entorse em 2022'];
$r = $aluno->req('POST', '/app/avaliacao-inicial', http_build_query(['_csrf' => $aluno->csrf, 'action' => 'save', 'a' => ['full_name' => 'Ana Souza', 'phone' => '1999']]), ['Accept' => 'application/json', 'X-Requested-With' => 'fetch']);
ok(($r['json']['ok'] ?? false) === true, 'rascunho da anamnese salvo (retomável)');
eq(json_decode((string) dbv('SELECT data FROM anamneses WHERE student_id = ?', [$anaId]), true)['phone'], '1999', 'rascunho persistido no banco');
$aluno->post('/app/avaliacao-inicial', ['action' => 'submit', 'a' => $answers, 'photo_consent' => 1]);
ok(str_contains($aluno->get('/app/avaliacao-inicial')['body'], 'Fotografia de Costas'), 'envio sem a foto de costas é bloqueado');
$aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'back'], ['photo' => [$img, 'image/jpeg', 'costas.jpg']]);
$aluno->upload('/app/avaliacao-inicial/foto', ['slot' => 'extra'], ['photo' => [$img, 'image/jpeg', 'extra.jpg']]);
$r = $aluno->post('/app/avaliacao-inicial', ['action' => 'submit', 'a' => $answers, 'personal_notes' => 'Sinto o joelho às vezes', 'photo_consent' => 1]);
eq($r['location'], "/app?enviado=1", 'avaliação inicial enviada');
ok((bool) dbv('SELECT onboarding_completed_at FROM student_profiles WHERE user_id = ?', [$anaId]), 'data e hora do envio registradas');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'renanrdw@gmail.com' AND template = 'onboarding_submitted'"), 'Renan notificado da avaliação inicial');
$r = $aluno->get('/app');
ok(str_contains($r['body'], 'Seu treino está sendo preparado pelo Renan.'), 'sem treino publicado: mensagem obrigatória exibida');
ok(str_contains($r['body'], 'Avaliação inicial enviada'), 'confirmação de envio exibida');

$r = $renan->get("/painel/alunos/{$anaId}?aba=dados");
ok(str_contains($r['body'], 'entorse em 2022') && str_contains($r['body'], 'Sinto o joelho'), 'Renan vê a anamnese e as observações');
ok(preg_match('~/media/(\d+)\?e=\d+&amp;s=\w+~', $r['body']) === 1, 'Renan recebe as fotos por URL temporária assinada');

// Biblioteca + treino
$r = $renan->post('/painel/biblioteca', ['name' => 'Agachamento livre', 'modality_id' => (int) dbv("SELECT id FROM modalities WHERE slug = 'musculacao'"), 'category' => 'Membros inferiores', 'instructions' => 'Desça até 90°', 'video_url' => 'https://youtu.be/abcdefghijk']);
$exId = (int) dbv("SELECT id FROM exercises WHERE name = 'Agachamento livre'");
ok($exId > 0, 'exercício criado na biblioteca');
$renan->get('/painel');
$found = array_column($renan->req('GET', '/api/biblioteca?q=agach', null, ['Accept' => 'application/json'])['json']['results'] ?? [], 'id');
ok(in_array($exId, $found, true) && count($found) > 1, 'busca na biblioteca encontra o exercício personalizado e os do catálogo');
$musc = (int) dbv("SELECT id FROM modalities WHERE slug = 'musculacao'");
$r = $renan->post("/painel/alunos/{$anaId}/treinos", ['label' => 'Treino A', 'name' => 'Força inferior', 'modality_id' => $musc, 'weekdays' => [0, 1, 2, 3, 4, 5, 6]]);
preg_match('~/painel/treinos/(\d+)~', $r['location'], $m);
$wid = (int) ($m[1] ?? 0);
ok($wid > 0, 'treino criado para o aluno');
$renan->get("/painel/treinos/{$wid}");
$items = [
    ['exercise_id' => $exId, 'name' => 'Agachamento livre', 'instructions' => 'Para a Ana: cuidado com o joelho', 'params' => ['sets' => '3', 'reps' => '10', 'load' => '20', 'rest' => '60s'], 'fields' => ['sets', 'reps', 'load', 'rest']],
    ['name' => 'Prancha', 'params' => ['sets' => '3', 'duration' => '30s'], 'fields' => ['sets', 'duration']],
];
$r = $renan->postJson("/painel/treinos/{$wid}/salvar", ['items' => $items, 'general_notes' => 'Aqueça 5 min']);
ok(($r['json']['ok'] ?? false) === true, 'rascunho do treino salvo pelo editor');
eq(dbv('SELECT instructions FROM exercises WHERE id = ?', [$exId]), 'Desça até 90°', 'instrução específica do aluno não altera a biblioteca');
eq($aluno->get('/app')['status'], 200, 'aluno continua vendo a área sem treino publicado');
ok(str_contains($aluno->get('/app')['body'], 'sendo preparado'), 'rascunho não aparece para o aluno');
$renan->post("/painel/treinos/{$wid}/publicar", ['when' => 'now']);
eq(dbv("SELECT status FROM workout_versions WHERE workout_id = ? AND version_no = 1", [$wid]), 'published', 'treino publicado');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'workout_published'"), 'aluno recebe e-mail de treino publicado');
$r = $aluno->get('/app');
ok(str_contains($r['body'], 'Força inferior') && str_contains($r['body'], 'Agachamento livre'), 'treino do dia em destaque no dashboard');

$r = $aluno->post("/app/treinos/{$wid}/iniciar", ['date' => today()]);
preg_match('~/app/sessao/(\d+)~', $r['location'], $m);
$sid = (int) ($m[1] ?? 0);
ok($sid > 0, 'aluno inicia o treino');
$s = $aluno->get("/app/sessao/{$sid}");
ok(str_contains($s['body'], 'Carga') && str_contains($s['body'], 'Duração'), 'formulário se adapta aos campos de cada exercício');
$item1 = (int) dbv('SELECT id FROM workout_items WHERE version_id = (SELECT id FROM workout_versions WHERE workout_id = ? AND version_no = 1) ORDER BY position LIMIT 1', [$wid]);
$r = $aluno->postJson("/app/sessao/{$sid}/registro", ['item_id' => $item1, 'completed' => 1, 'data' => ['sets' => [['reps' => '10', 'load' => '22', 'done' => true], ['reps' => '9', 'load' => '22', 'done' => true]], 'notes' => 'Pesado']]);
ok(($r['json']['ok'] ?? false) && $r['json']['done'] === 1, 'salvamento automático de exercício');
ok(str_contains($aluno->get("/app/sessao/{$sid}")['body'], 'value="22"'), 'retomada do treino em andamento mantém os dados');
$aluno->post("/app/sessao/{$sid}/concluir", ['notes' => 'Ótimo treino']);
eq(dbv('SELECT status FROM workout_sessions WHERE id = ?', [$sid]), 'completed', 'treino concluído');
$r = $renan->get("/painel/alunos/{$anaId}/sessoes/{$sid}");
ok(str_contains($r['body'], 'Ótimo treino') && str_contains($r['body'], '22'), 'Renan visualiza o registro de execução');
$aluno->post("/app/treinos/{$wid}/situacao", ['date' => date_add_days(today(), -1), 'status' => 'postponed', 'notes' => 'Viagem']);
eq(dbv('SELECT status FROM workout_sessions WHERE workout_id = ? AND scheduled_date = ?', [$wid, date_add_days(today(), -1)]), 'postponed', 'aluno informa treino adiado');

// ───────────── Fluxo B ─────────────
section('Fluxo B — nova versão do treino: agendada, histórico intacto');
$renan->get("/painel/treinos/{$wid}");
$v2 = (int) dbv("SELECT id FROM workout_versions WHERE workout_id = ? AND status = 'draft'", [$wid]);
ok($v2 > 0, 'edição cria rascunho v2 a partir da versão publicada');
$items[0]['params']['load'] = '30';
$items[] = ['name' => 'Afundo', 'params' => ['sets' => '3', 'reps' => '12'], 'fields' => ['sets', 'reps']];
$renan->postJson("/painel/treinos/{$wid}/salvar", ['items' => $items]);
$when = local_now()->modify('+2 days')->format('Y-m-d\TH:i');
$renan->post("/painel/treinos/{$wid}/publicar", ['when' => 'schedule', 'publish_at' => $when]);
eq(dbv('SELECT status FROM workout_versions WHERE id = ?', [$v2]), 'scheduled', 'publicação agendada');
eq(dbv("SELECT version_no FROM workout_versions WHERE workout_id = ? AND status = 'published'", [$wid]), 1, 'versão anterior continua valendo até a data');
ok(!str_contains($aluno->get("/app/treinos/{$wid}")['body'], 'Afundo'), 'aluno ainda não vê a nova versão');
cron(['WRUCH_FAKE_TIME' => time() + 3 * 86400]);
eq(dbv("SELECT version_no FROM workout_versions WHERE workout_id = ? AND status = 'published'", [$wid]), 2, 'cron publicou a versão agendada');
eq(dbv("SELECT status FROM workout_versions WHERE workout_id = ? AND version_no = 1", [$wid]), 'superseded', 'versão 1 preservada no histórico');
ok(str_contains($aluno->get("/app/treinos/{$wid}")['body'], 'Afundo'), 'nova versão aparece para o aluno');
eq(dbv('SELECT v.version_no FROM workout_sessions s JOIN workout_versions v ON v.id = s.version_id WHERE s.id = ?', [$sid]), 1, 'registro antigo continua ligado à versão 1');
eq(json_decode((string) dbv('SELECT params FROM workout_items WHERE id = ?', [$item1]), true)['load'], '20', 'carga prescrita no histórico não foi sobrescrita');
ok((int) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'workout_published'") >= 2, 'aluno avisado da nova versão');
// Pedido de mudança de dia
$aluno->post("/app/treinos/{$wid}/mudar-dia", ['original_date' => date_add_days(today(), 1), 'requested_date' => date_add_days(today(), 2), 'note' => 'Compromisso']);
$rid = (int) dbv("SELECT id FROM reschedule_requests WHERE student_id = ? AND status = 'pending'", [$anaId]);
ok($rid > 0, 'aluno solicita mudança de dia');
eq((int) dbv('SELECT COUNT(*) FROM schedule_overrides'), 0, 'programação não muda antes da aprovação');
$renan->get('/painel/solicitacoes');
$renan->post("/painel/solicitacoes/{$rid}", ['decision' => 'approve']);
eq((int) dbv('SELECT COUNT(*) FROM schedule_overrides WHERE request_id = ?', [$rid]), 1, 'aprovação altera a programação oficial');

// ───────────── Fluxo C ─────────────
section('Fluxo C — avaliação física: lembrete → envio → revisão → próxima data');
$renan->get("/painel/alunos/{$anaId}");
$renan->post("/painel/alunos/{$anaId}/configuracoes", ['self_eval_every_days' => 7, 'categories' => ['sono', 'alimentacao', 'disposicao'], 'assessment_every_days' => 30, 'next_assessment_on' => date_add_days(today(), 3), 'assessment_remind_days_before' => 3, 'assessment_reminders_count' => 2, 'assessment_overdue_alert' => 1]);
eq(dbv('SELECT next_assessment_on FROM student_profiles WHERE user_id = ?', [$anaId]), date_add_days(today(), 3), 'Renan define a próxima avaliação');
cron();
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'assessment_reminder'"), 'sistema envia lembrete com antecedência');
cron();
eq((int) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'assessment_reminder'"), 1, 'lembrete não é duplicado');
$r = $aluno->get('/app/avaliacoes/nova');
preg_match('~/app/avaliacoes/(\d+)~', $r['location'], $m);
$aid = (int) $m[1];
$aluno->get("/app/avaliacoes/{$aid}");
foreach (['front', 'side', 'back'] as $slot) $aluno->upload("/app/avaliacoes/{$aid}/foto", ['slot' => $slot], ['photo' => [$img, 'image/jpeg', "{$slot}.jpg"]]);
$aluno->post("/app/avaliacoes/{$aid}/enviar", ['action' => 'submit', 'm' => ['weight_kg' => '61,5'], 'student_notes' => 'Me sentindo bem']);
eq(dbv('SELECT status FROM physical_assessments WHERE id = ?', [$aid]), 'submitted', 'aluno envia avaliação com 3 fotos');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'renanrdw@gmail.com' AND template = 'assessment_received'"), 'Renan notificado');
$r = $renan->get("/painel/avaliacoes/{$aid}");
ok(str_contains($r['body'], 'Me sentindo bem') && str_contains($r['body'], '61,5'), 'Renan vê fotos, medidas e observações');
$renan->post("/painel/avaliacoes/{$aid}", ['trainer_comments' => 'Boa evolução', 'trainer_guidance' => 'Manter hidratação', 'mark_reviewed' => 1]);
eq(dbv('SELECT status FROM physical_assessments WHERE id = ?', [$aid]), 'reviewed', 'avaliação marcada como revisada');
eq(dbv('SELECT next_assessment_on FROM student_profiles WHERE user_id = ?', [$anaId]), date_add_days(today(), 30), 'próxima avaliação calculada (envio + 30 dias)');
ok(str_contains($aluno->get("/app/avaliacoes/{$aid}")['body'], 'Manter hidratação'), 'aluno vê as orientações');

// Privacidade das fotos
$fid = (int) dbv("SELECT id FROM files WHERE context = 'assessment' AND context_id = ? LIMIT 1", [$aid]);
preg_match('~/media/' . $fid . '\?e=(\d+)&amp;s=(\w+)~', $aluno->get("/app/avaliacoes/{$aid}")['body'], $m);
$ownUrl = "/media/{$fid}?e={$m[1]}&s={$m[2]}";
$r = $aluno->get($ownUrl);
eq($r['status'], 200, 'dono acessa a própria foto pela URL temporária');
ok(str_contains($r['headers'], 'no-store'), 'foto entregue com Cache-Control no-store');
eq((new Client($base, 'anon'))->get($ownUrl)['status'], 401, 'sem login a foto não abre');
eq($aluno->get("/media/{$fid}?e={$m[1]}&s=" . str_repeat('0', 32))['status'], 403, 'assinatura adulterada é recusada');
eq($aluno->get("/media/{$fid}")['status'], 403, 'sem assinatura temporária é recusada');
// Outro aluno ativo não acessa a foto
$intruso->get('/cadastro');
$intruso->post('/cadastro', ['name' => 'Bruno Lima', 'email' => 'bruno@example.com', 'password' => 'senhaforte2', 'password_confirm' => 'senhaforte2', 'consent_terms' => 1, 'consent_health' => 1]);
$intruso->get(pathOf(mailLink('bruno@example.com', 'verify_email')));
$brunoId = (int) dbv("SELECT id FROM users WHERE email = 'bruno@example.com'");
$renan->post("/painel/alunos/{$brunoId}/aprovar");
eq($intruso->get($ownUrl)['status'], 403, 'URL de outro usuário não funciona (vinculada à sessão)');
$exp = time() + 3600;
$brunoSigned = "/media/{$fid}?e={$exp}&s=" . substr(hash_hmac('sha256', "{$fid}|{$exp}|{$brunoId}", getenv('APP_KEY')), 0, 32);
eq($intruso->get($brunoSigned)['status'], 403, 'mesmo com URL assinada válida, outro aluno não vê a foto (regra de acesso)');
eq($intruso->req('GET', "/api/mensagens/{$anaId}", null, ['Accept' => 'application/json'])['status'], 403, 'aluno não lê conversa de outro aluno');
eq($intruso->get("/painel/alunos/{$anaId}")['location'], "/app", 'aluno não abre perfil de outro aluno no painel');
ok(in_array($intruso->get("/app/sessao/{$sid}")['status'], [303, 404], true), 'sessão de treino de outro aluno não é acessível');

// ───────────── Fluxo D ─────────────
section('Fluxo D — chat com texto, arquivo e indicadores de leitura');
$aluno->get('/app/mensagens');
$r = $aluno->postJson("/api/mensagens/{$anaId}", ['body' => 'Oi Renan, dúvida no agachamento']);
ok(($r['json']['ok'] ?? false) && $r['json']['message']['mine'], 'aluno envia mensagem');
$r = $aluno->upload("/api/mensagens/{$anaId}", ['body' => 'Segue foto'], ['file' => [$img, 'image/jpeg', 'execucao.jpg']]);
ok(($r['json']['message']['file']['kind'] ?? '') === 'image', 'aluno envia imagem no chat');
file_put_contents("{$tmp}/doc.pdf", "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
$r = $aluno->upload("/api/mensagens/{$anaId}", [], ['file' => ["{$tmp}/doc.pdf", 'application/pdf', 'exame.pdf']]);
ok(($r['json']['message']['file']['kind'] ?? '') === 'document', 'aluno envia documento PDF');
file_put_contents("{$tmp}/x.exe", "MZ\x90\x00" . str_repeat("\x00", 200));
eq($aluno->upload("/api/mensagens/{$anaId}", [], ['file' => ["{$tmp}/x.exe", 'application/octet-stream', 'x.exe']])['status'], 422, 'executável é bloqueado');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'renanrdw@gmail.com' AND template = 'chat_unread' AND status = 'queued'"), 'aviso de mensagem agrupado (enviado depois, não na hora)');
eq((int) dbv("SELECT COUNT(*) FROM email_queue WHERE template = 'chat_unread' AND to_email = 'renanrdw@gmail.com'"), 1, 'três mensagens geram um único aviso');
ok(!str_contains((string) dbv("SELECT text_body FROM email_queue WHERE template = 'chat_unread'"), 'agachamento'), 'e-mail não traz o conteúdo da mensagem');
$r = $renan->req('GET', "/api/mensagens/{$anaId}?after=0", null, ['Accept' => 'application/json']);
eq(count($r['json']['messages'] ?? []), 3, 'Renan recebe as mensagens');
eq((int) dbv('SELECT COUNT(*) FROM messages WHERE student_id = ? AND read_at IS NULL', [$anaId]), 0, 'mensagens marcadas como lidas ao abrir');
$renan->get('/painel/mensagens/' . $anaId);
$r = $renan->postJson("/api/mensagens/{$anaId}", ['body' => 'Mantenha a coluna neutra']);
$lastMine = (int) $r['json']['message']['id'];
$r = $aluno->req('GET', "/api/mensagens/{$anaId}?after=0", null, ['Accept' => 'application/json']);
$texts = array_column($r['json']['messages'], 'body');
ok(in_array('Mantenha a coluna neutra', $texts, true), 'aluno vê a resposta');
ok($r['json']['read_up_to'] >= 1, 'aluno vê indicador de leitura das próprias mensagens');
cron(['WRUCH_FAKE_TIME' => time() + 20 * 60]);
eq(dbv("SELECT status FROM email_queue WHERE template = 'chat_unread' AND to_email = 'renanrdw@gmail.com'"), 'skipped', 'aviso cancelado porque o Renan já leu');

// ───────────── Fluxo E ─────────────
section('Fluxo E — assinatura no Mercado Pago com webhook assinado e idempotente');
$renan->get('/painel/financeiro');
$renan->post('/painel/planos', ['name' => 'Consultoria mensal', 'price' => '199,90', 'period_months' => 1, 'allow_recurring' => 1, 'allow_one_time' => 1, 'active' => 1]);
$planId = (int) dbv("SELECT id FROM plans WHERE name = 'Consultoria mensal'");
eq((int) dbv('SELECT price_cents FROM plans WHERE id = ?', [$planId]), 19990, 'plano cadastrado com preço');
ok(str_contains((new Client($base, 'pub'))->get('/')['body'], 'R$ 199,90'), 'plano aparece na página pública');
$aluno->get('/app/assinatura');
$r = $aluno->post('/app/assinatura/checkout', ['plan_id' => $planId, 'mode' => 'recurring']);
ok(str_starts_with($r['location'], 'https://www.mercadopago.com.br/subscriptions/checkout'), 'checkout redireciona ao Mercado Pago');
$sub = db("SELECT * FROM subscriptions WHERE student_id = ? ORDER BY id DESC LIMIT 1", [$anaId])[0];
eq($sub['status'], 'pending', 'assinatura começa pendente');
ok((bool) array_filter(mpState()['calls'], fn($c) => $c['path'] === '/preapproval' && $c['idempotency']), 'requisição à API usa chave de idempotência');
$aluno->get('/app/assinatura/retorno?ref=' . $sub['external_reference'] . '&status=approved&collection_status=approved');
eq(dbv('SELECT status FROM subscriptions WHERE id = ?', [$sub['id']]), 'pending', 'voltar à página de sucesso NÃO marca como pago');
$st = mpState();
$st['preapprovals'][$sub['provider_ref']]['status'] = 'authorized';
$st['preapprovals'][$sub['provider_ref']]['next_payment_date'] = date('c', strtotime('+1 month'));
mpState($st);
$hook = new Client($base, 'mp');
eq(mpWebhook($hook, 'subscription_preapproval', $sub['provider_ref'], 'segredo-errado')['status'], 401, 'webhook com assinatura inválida é recusado');
eq(dbv('SELECT status FROM subscriptions WHERE id = ?', [$sub['id']]), 'pending', 'assinatura inválida não altera nada');
eq(mpWebhook($hook, 'subscription_preapproval', $sub['provider_ref'], 'whsec_test', 'n1')['status'], 200, 'webhook válido aceito');
eq(dbv('SELECT status FROM subscriptions WHERE id = ?', [$sub['id']]), 'active', 'assinatura ativada pelo webhook (dados confirmados na API)');
$st = mpState();
$st['authorized']['ap1'] = ['id' => 'ap1', 'preapproval_id' => $sub['provider_ref'], 'transaction_amount' => 199.9, 'status' => 'processed', 'payment' => ['id' => 'pay777', 'status' => 'approved', 'status_detail' => 'accredited'], 'date_created' => date('c')];
mpState($st);
mpWebhook($hook, 'subscription_authorized_payment', 'ap1', 'whsec_test', 'n2');
eq(dbv("SELECT status FROM payments WHERE provider_payment_id = 'pay777'"), 'approved', 'pagamento recorrente registrado');
$end1 = dbv('SELECT current_period_end FROM subscriptions WHERE id = ?', [$sub['id']]);
ok($end1 > today(), 'período pago calculado');
mpWebhook($hook, 'subscription_authorized_payment', 'ap1', 'whsec_test', 'n2');
mpWebhook($hook, 'subscription_authorized_payment', 'ap1', 'whsec_test', 'n3');
eq((int) dbv("SELECT COUNT(*) FROM payments WHERE provider_payment_id = 'pay777'"), 1, 'notificação repetida não duplica o pagamento');
eq(dbv('SELECT current_period_end FROM subscriptions WHERE id = ?', [$sub['id']]), $end1, 'notificação repetida não estende o período duas vezes');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'payment_approved'"), 'aluno avisado do pagamento');
ok(str_contains($aluno->get('/app/assinatura')['body'], 'Em dia'), 'situação financeira "Em dia"');
// Pagamento recusado
$st = mpState();
$st['authorized']['ap2'] = ['id' => 'ap2', 'preapproval_id' => $sub['provider_ref'], 'transaction_amount' => 199.9, 'status' => 'recycling', 'payment' => ['id' => 'pay778', 'status' => 'rejected', 'status_detail' => 'cc_rejected_insufficient_amount']];
mpState($st);
mpWebhook($hook, 'subscription_authorized_payment', 'ap2', 'whsec_test', 'n4');
eq(dbv('SELECT status FROM subscriptions WHERE id = ?', [$sub['id']]), 'payment_failed', 'pagamento recusado tratado');
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'renanrdw@gmail.com' AND template = 'payment_failed_trainer'"), 'Renan avisado da recusa');
// Pagamento avulso (Pix)
$intruso->get('/app/assinatura');
$r = $intruso->post('/app/assinatura/checkout', ['plan_id' => $planId, 'mode' => 'one_time']);
ok(str_contains($r['location'], 'checkout/v1/redirect'), 'checkout avulso (Checkout Pro) para Pix/boleto/cartão');
$subB = db('SELECT * FROM subscriptions WHERE student_id = ? ORDER BY id DESC LIMIT 1', [$brunoId])[0];
$st = mpState();
$st['payments']['9001'] = ['id' => 9001, 'status' => 'approved', 'status_detail' => 'accredited', 'transaction_amount' => 199.9, 'payment_type_id' => 'bank_transfer', 'date_approved' => date('c'), 'external_reference' => $subB['external_reference']];
mpState($st);
mpWebhook($hook, 'payment', '9001', 'whsec_test', 'n5');
eq(dbv('SELECT status FROM subscriptions WHERE id = ?', [$subB['id']]), 'active', 'pagamento Pix aprovado ativa o período');
eq(dbv('SELECT current_period_end FROM subscriptions WHERE id = ?', [$subB['id']]), (new DateTimeImmutable(today()))->modify('+1 month')->format('Y-m-d'), 'período de 1 mês');
// Política de inadimplência
DB::reset();
Settings::set('billing_policy', 'restrict');
$carla = new Client($base, 'carla');
$carla->get('/cadastro');
$carla->post('/cadastro', ['name' => 'Carla Dias', 'email' => 'carla@example.com', 'password' => 'senhaforte3', 'password_confirm' => 'senhaforte3', 'consent_terms' => 1, 'consent_health' => 1]);
$carla->get(pathOf(mailLink('carla@example.com', 'verify_email')));
$carlaId = (int) dbv("SELECT id FROM users WHERE email = 'carla@example.com'");
$renan->post("/painel/alunos/{$carlaId}/aprovar");
DB::reset();
DB::update('student_profiles', ['onboarding_completed_at' => now()], 'user_id = ?', [$carlaId]);
eq($carla->get('/app')['location'], "/app/situacao", 'sem assinatura e com restrição ativa, acesso aos treinos é limitado');
eq($carla->get('/app/mensagens')['status'], 200, 'aluno inadimplente ainda acessa o chat');
eq($aluno->get('/app')['status'], 200, 'aluno em carência mantém acesso');
$renan->post("/painel/alunos/{$carlaId}/configuracoes", ['self_eval_every_days' => 7, 'categories' => ['sono'], 'assessment_every_days' => 30, 'access_override' => 'allow']);
eq($carla->get('/app')['status'], 200, 'Renan libera acesso individualmente');
DB::reset();
Settings::set('billing_policy', 'off');
// Cancelamento de renovação
$aluno->get('/app/assinatura');
$aluno->post("/app/assinatura/{$sub['id']}/cancelar");
eq(dbv('SELECT status FROM subscriptions WHERE id = ?', [$sub['id']]), 'cancelled', 'aluno cancela a renovação');
eq(mpState()['preapprovals'][$sub['provider_ref']]['status'], 'cancelled', 'cancelamento enviado ao Mercado Pago');

// ───────────── Fluxo F ─────────────
section('Fluxo F — desenvolvedores alternam interfaces com auditoria');
$dev = new Client($base, 'dev');
$dev->get('/cadastro');
$dev->post('/cadastro', ['name' => 'Dev Teste', 'email' => 'dev@wruch.test', 'password' => 'devforte123', 'password_confirm' => 'devforte123', 'consent_terms' => 1, 'consent_health' => 1]);
$dev->get(pathOf(mailLink('dev@wruch.test', 'verify_email')));
$devId = (int) dbv("SELECT id FROM users WHERE email = 'dev@wruch.test'");
eq(dbv('SELECT role FROM users WHERE id = ?', [$devId]), 'developer', 'papel de desenvolvedor vem do servidor');
eq($dev->get('/painel')['location'], "/app", 'desenvolvedor começa na interface de aluno');
$dev->get('/app/perfil');
$dev->post('/alternar-interface', ['to' => 'trainer']);
eq($dev->get('/painel')['status'], 200, 'desenvolvedor alterna para a interface do Renan (sem a senha dele)');
$dev->get("/painel/alunos/{$anaId}");
$dev->post("/painel/alunos/{$anaId}/configuracoes", ['self_eval_every_days' => 5, 'categories' => ['sono', 'motivacao'], 'assessment_every_days' => 30]);
$log = db("SELECT * FROM audit_logs WHERE action = 'student.settings' ORDER BY id DESC LIMIT 1")[0];
eq((int) $log['actor_id'], $devId, 'ação registrada com a identidade real do desenvolvedor');
eq($log['acting_as'], 'trainer', 'registro indica a interface usada');
ok((bool) dbv("SELECT COUNT(*) FROM audit_logs WHERE actor_id = ? AND action = 'dev.switch_interface'", [$devId]), 'alternância registrada na auditoria');
$r = $dev->get('/painel/configuracoes?aba=registros');
ok(str_contains($r['body'], 'Dev Teste') && str_contains($r['body'], 'dev'), 'Renan vê ações do desenvolvedor nos registros');
$dev->post('/alternar-interface', ['to' => 'student']);
eq($dev->get('/painel')['location'], "/app", 'desenvolvedor volta para a interface de aluno');
$aluno->get('/app');
eq($aluno->post('/alternar-interface', ['to' => 'trainer'])['status'], 403, 'aluno comum não consegue alternar interface');
eq($aluno->get('/painel')['location'], "/app", 'aluno continua sem acesso ao painel');
ok(!str_contains($aluno->get('/app')['body'], 'Alternar para'), 'aluno não vê a opção de alternar');
eq($dev->get('/admin')['status'], 404, 'não existe painel administrativo separado');

// ───────────── Segurança e conta ─────────────
section('Segurança: CSRF, limite de tentativas, recuperação de senha, sessões, cabeçalhos');
$r = $aluno->req('POST', '/app/perfil', http_build_query(['name' => 'Hacker']), ['Content-Type' => 'application/x-www-form-urlencoded']);
ok(in_array($r['status'], [303, 419], true) && !str_contains((string) $r['location'], '/app/perfil?ok'), 'POST sem token CSRF é recusado');
ok(dbv('SELECT name FROM users WHERE id = ?', [$anaId]) !== 'Hacker', 'dados não alterados sem CSRF');
$bf = new Client($base, 'bf');
$bf->get('/entrar');
for ($i = 0; $i < 9; $i++) $bf->post('/entrar', ['email' => 'ana@example.com', 'password' => 'errada' . $i]);
$bf->post('/entrar', ['email' => 'ana@example.com', 'password' => 'senhaforte1']);
ok(str_contains($bf->get('/entrar')['body'], 'Muitas tentativas'), 'login bloqueado após tentativas repetidas (mesmo com a senha certa)');
$r = $bf->get('/entrar');
ok(str_contains($r['headers'], 'X-Frame-Options: DENY') && str_contains($r['headers'], 'Content-Security-Policy'), 'cabeçalhos de segurança presentes');
$fresh = (new Client($base, 'fresh'))->get('/entrar');
ok((bool) preg_match('/Set-Cookie: wruch_sid=[^\n]*HttpOnly[^\n]*SameSite=Lax/i', $fresh['headers']), 'cookie de sessão HttpOnly e SameSite');
$fp = new Client($base, 'fp');
$fp->get('/esqueci-senha');
$fp->post('/esqueci-senha', ['email' => 'naoexiste@example.com']);
ok(str_contains($fp->get('/entrar')['body'], 'Se houver uma conta'), 'recuperação não revela se o e-mail existe');
$fp->post('/esqueci-senha', ['email' => 'ana@example.com']);
$link = mailLink('ana@example.com', 'password_reset');
ok((bool) $link, 'link de redefinição enviado');
$fp->get(pathOf($link));
parse_str((string) parse_url($link, PHP_URL_QUERY), $qs);
$fp->post('/redefinir-senha', ['t' => $qs['t'], 'password' => 'novasenha99', 'password_confirm' => 'novasenha99']);
eq($aluno->get('/app')['location'], "/entrar", 'sessões antigas encerradas após troca de senha');
RateLimit::clear('login-email:ana@example.com');
$novo = new Client($base, 'novo');
$novo->get('/entrar');
$r = $novo->post('/entrar', ['email' => 'ana@example.com', 'password' => 'novasenha99']);
eq($r['location'], "/app", 'login com a nova senha');
eq($fp->post('/redefinir-senha', ['t' => $qs['t'], 'password' => 'outra12345', 'password_confirm' => 'outra12345'])['location'], "/esqueci-senha", 'link de redefinição é de uso único');
$r = $novo->get('/painel/alunos?q=' . urlencode("' OR 1=1 --"));
ok(in_array($r['status'], [200, 303], true), 'entrada maliciosa em busca não causa erro (consultas parametrizadas)');
eq($novo->postJson('/app/perfil', ['name' => 'x'])['status'], 419, 'JSON sem cabeçalho CSRF também é recusado');
$r = $novo->get('/app/perfil/exportar');
ok(str_contains($r['body'], '"anamneses"') && str_contains($r['headers'], 'attachment'), 'aluno exporta os próprios dados (LGPD)');
$novo->get('/app/perfil');
$novo->post('/app/perfil/lgpd', ['type' => 'deletion', 'details' => 'Quero excluir']);
ok((bool) dbv("SELECT COUNT(*) FROM data_requests WHERE user_id = ? AND request_type = 'deletion'", [$anaId]), 'pedido de exclusão registrado');
eq((new Client($base, 'c1'))->get('/cron?token=errado')['status'], 404, 'cron por URL exige token');
eq((new Client($base, 'c2'))->get('/cron?token=crontoken123')['status'], 200, 'cron por URL com token');

// ───────────── Notificações ─────────────
section('Notificações: preferências, fila, novas tentativas e falhas registradas');
$novo->post('/app/perfil/notificacoes', ['prefs' => ['message_digest' => 1]]);
ok(!Notify::prefEnabled($anaId, 'workout_published'), 'preferência não essencial desativada');
DB::reset();
Clock::set(null);
$before = (int) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'workout_published'");
Notify::send($anaId, 'workout_published', ['workout' => 'X'], ['pref' => 'workout_published', 'dedupe' => 'teste-pref']);
eq((int) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'workout_published'"), $before, 'e-mail não essencial respeita a preferência');
Notify::send($anaId, 'password_changed', [], ['dedupe' => 'teste-essencial']);
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE dedupe_key = 'teste-essencial'"), 'e-mail essencial sempre enfileirado');
Notify::send($anaId, 'password_changed', [], ['dedupe' => 'teste-essencial']);
eq((int) dbv("SELECT COUNT(*) FROM email_queue WHERE dedupe_key = 'teste-essencial'"), 1, 'chave de deduplicação evita duplicatas');
$out = cron(['MAIL_TRANSPORT' => 'smtp', 'MAIL_HOST' => '127.0.0.1', 'MAIL_PORT' => '1', 'MAIL_ENCRYPTION' => 'none']);
$row = db("SELECT * FROM email_queue WHERE dedupe_key = 'teste-essencial'")[0];
eq($row['status'], 'queued', 'falha de envio volta para a fila');
eq((int) $row['attempts'], 1, 'tentativa contabilizada');
ok(str_contains((string) $row['last_error'], 'SMTP'), 'erro registrado para diagnóstico');
cron(['WRUCH_FAKE_TIME' => time() + 600]);
eq(dbv("SELECT status FROM email_queue WHERE dedupe_key = 'teste-essencial'"), 'sent', 'nova tentativa com sucesso');

// ───────────── Reativação e PWA ─────────────
section('Reativação com novo ciclo, PWA e responsividade básica');
$renan->get("/painel/alunos/{$brunoId}");
$renan->post("/painel/alunos/{$brunoId}/situacao", ['status' => 'ended']);
eq($intruso->get('/app')['location'], "/app/situacao", 'aluno encerrado é direcionado para a situação da conta');
$renan->post("/painel/alunos/{$brunoId}/reativar", ['new_cycle' => 1, 'new_onboarding' => 1]);
eq((int) dbv('SELECT cycle FROM student_profiles WHERE user_id = ?', [$brunoId]), 2, 'novo ciclo iniciado');
eq((int) dbv('SELECT COUNT(*) FROM student_cycles WHERE student_id = ?', [$brunoId]), 2, 'histórico de ciclos preservado');
eq($intruso->get('/app')['location'], "/app/avaliacao-inicial", 'nova avaliação inicial solicitada');
$pub = new Client($base, 'pwa');
$m = $pub->get('/manifest.webmanifest');
ok(($m['json']['display'] ?? '') === 'standalone' && count($m['json']['icons'] ?? []) >= 2, 'manifesto PWA válido com ícones');
$sw = $pub->get('/sw.js')['body'];
ok(substr_count($sw, 'cache.put') === 1 && str_contains($sw, "startsWith('/assets/')"), 'service worker só guarda arquivos estáticos');
ok(str_contains($pub->get('/')['body'], 'name="viewport"'), 'páginas com viewport para celular');
$home = $pub->get('/')['body'];
ok(str_contains($home, 'wa.me/5519998089854') && str_contains($home, 'instagram.com/renanwruch') && str_contains($home, 'mailto:renanrdw@gmail.com'), 'contatos funcionais na página pública');

// ───────────── Navegação completa ─────────────
section('Todas as telas abrem sem erro (aluno, Renan e desenvolvedor)');
$novo->get('/app/autoavaliacao');
$novo->post('/app/autoavaliacao', ['date' => today(), 'scores' => ['sono' => 2, 'motivacao' => 5]]);
ok((bool) dbv('SELECT COUNT(*) FROM self_assessments WHERE student_id = ?', [$anaId]), 'autoavaliação salva');
$novo->post('/app/autoavaliacao', ['date' => date_add_days(today(), -1), 'scores' => ['sono' => 5, 'motivacao' => 5], 'notes' => 'Dormi bem']);
$novo->post('/app/autoavaliacao/lembrete', ['time' => '07:30']);
eq(dbv('SELECT self_eval_reminder_time FROM student_profiles WHERE user_id = ?', [$anaId]), '07:30', 'aluno escolhe o horário do lembrete');
$novo->post('/app/alimentacao', ['date' => today(), 'meal' => 'almoco', 'description' => 'Arroz, feijão e frango', 'water_ml' => 500]);
ok((bool) dbv('SELECT COUNT(*) FROM nutrition_logs WHERE student_id = ?', [$anaId]), 'registro de alimentação salvo');
$renan->get('/painel/calendario');
$renan->post('/painel/eventos', ['student_id' => $anaId, 'type' => 'activity', 'event_date' => date_add_days(today(), 1), 'title' => 'Torneio de beach tennis', 'start_time' => '09:00', 'notify' => 1]);
$evId = (int) dbv("SELECT id FROM calendar_events WHERE title = 'Torneio de beach tennis'");
ok($evId > 0, 'Renan cria evento no calendário');
eq((int) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'ana@example.com' AND template = 'calendar_changed'"), 0, 'aviso de calendário respeita a preferência desligada pela aluna');
$renan->post('/painel/eventos', ['student_id' => $brunoId, 'type' => 'appointment', 'event_date' => date_add_days(today(), 2), 'title' => 'Conversa de alinhamento', 'notify' => 1]);
ok((bool) dbv("SELECT COUNT(*) FROM email_queue WHERE to_email = 'bruno@example.com' AND template = 'calendar_changed'"), 'aluno avisado da alteração no calendário');
ok(str_contains($novo->get('/app/calendario?dia=' . date_add_days(today(), 1))['body'], 'Torneio de beach tennis'), 'evento aparece automaticamente para o aluno');
$renan->post('/painel/modelos', ['name' => 'Base força', 'modality_id' => $musc]);
$tplId = (int) dbv("SELECT id FROM workouts WHERE is_template = 1 AND name = 'Base força'");
ok($tplId > 0, 'modelo criado');
$renan->post("/painel/treinos/{$wid}/modelo", ['template_name' => 'Modelo do treino A']);
$tpl2 = (int) dbv("SELECT id FROM workouts WHERE is_template = 1 AND name = 'Modelo do treino A'");
eq((int) dbv("SELECT COUNT(*) FROM workout_items i JOIN workout_versions v ON v.id = i.version_id WHERE v.workout_id = ?", [$tpl2]), 3, 'treino salvo como modelo reutilizável');
$r = $renan->post("/painel/alunos/{$brunoId}/treinos", ['label' => 'Treino B', 'name' => 'Do modelo', 'modality_id' => $musc, 'weekdays' => [2], 'template_id' => $tpl2]);
preg_match('~/painel/treinos/(\d+)~', $r['location'], $m);
eq((int) dbv("SELECT COUNT(*) FROM workout_items i JOIN workout_versions v ON v.id = i.version_id WHERE v.workout_id = ?", [(int) $m[1]]), 3, 'treino criado a partir de modelo');
$renan->post("/painel/treinos/{$wid}/duplicar", ['student_id' => $anaId]);
ok((int) dbv('SELECT COUNT(*) FROM workouts WHERE student_id = ? AND is_template = 0', [$anaId]) === 2, 'treino duplicado');
$renan->post("/painel/biblioteca/{$exId}/duplicar");
ok((int) dbv("SELECT COUNT(*) FROM exercises WHERE name = 'Agachamento livre (cópia)' AND is_custom = 1") === 1, 'exercício duplicado na biblioteca');
$v1 = (int) dbv('SELECT id FROM workout_versions WHERE workout_id = ? AND version_no = 1', [$wid]);
$studentPages = ['/app', '/app/treinos', "/app/treinos/{$wid}", '/app/historico', '/app/autoavaliacao', '/app/autoavaliacao?data=' . date_add_days(today(), -1), '/app/alimentacao', '/app/avaliacoes', "/app/avaliacoes/{$aid}", '/app/calendario', '/app/calendario?ver=mes', '/app/mensagens', '/app/perfil', '/app/assinatura', "/app/sessao/{$sid}", '/app/eu', '/app/progresso', "/app/sessao/{$sid}/concluido"];
foreach ($studentPages as $pg) {
    $r = $novo->get($pg);
    ok($r['status'] === 200 && !str_contains($r['body'], 'Algo deu errado') && !str_contains($r['body'], 'Warning:'), "aluno abre {$pg} (status {$r['status']})");
}
$trainerPages = ['/painel', '/painel/mais', '/painel/alunos?situacao=late', '/painel/alunos', '/painel/alunos?situacao=active&q=Ana&ordem=activity&modalidade=' . $musc, '/painel/alunos?ordem=assessment&testes=1'];
foreach (array_keys(TrainerStudentsController::TABS) as $tab) $trainerPages[] = "/painel/alunos/{$anaId}?aba={$tab}";
$trainerPages = array_merge($trainerPages, ["/painel/alunos/{$anaId}?aba=calendario&ver=mes&evento={$evId}", "/painel/alunos/{$brunoId}", "/painel/treinos/{$wid}", "/painel/treinos/{$wid}/versoes/{$v1}", "/painel/treinos/{$tpl2}", '/painel/modelos', '/painel/biblioteca', '/painel/biblioteca?arquivados=1', '/painel/biblioteca/novo', "/painel/biblioteca/{$exId}", '/painel/solicitacoes', '/painel/avaliacoes', '/painel/avaliacoes?situacao=revisadas', "/painel/avaliacoes/{$aid}", '/painel/calendario', "/painel/calendario?ver=mes&aluno={$anaId}&evento={$evId}", '/painel/mensagens', "/painel/mensagens/{$anaId}", '/painel/financeiro', "/painel/financeiro?plano={$planId}", '/painel/financeiro?situacao=active']);
foreach (array_keys(TrainerSettingsController::TABS) as $tab) $trainerPages[] = "/painel/configuracoes?aba={$tab}";
foreach ($trainerPages as $pg) {
    $r = $renan->get($pg);
    ok($r['status'] === 200 && !str_contains($r['body'], 'Algo deu errado') && !str_contains($r['body'], 'Warning:'), "Renan abre {$pg} (status {$r['status']})");
}
$dev->get('/app/perfil');
$dev->post('/alternar-interface', ['to' => 'trainer']);
foreach (['/painel', "/painel/alunos/{$anaId}?aba=dados", '/painel/configuracoes?aba=registros'] as $pg) {
    eq($dev->get($pg)['status'], 200, "desenvolvedor abre {$pg}");
}
$renan->get('/painel/configuracoes?aba=pagina');
$renan->post('/painel/configuracoes', ['tab' => 'pagina', 'public_headline' => 'Treino planejado para você', 'public_intro' => 'Intro', 'public_about' => 'Formado em Educação Física', 'contact_whatsapp' => '+55 19 99808-9854', 'contact_whatsapp_label' => '+55 19 99808-9854', 'contact_instagram' => 'https://www.instagram.com/renanwruch/', 'contact_email' => 'renanrdw@gmail.com']);
$home = preg_replace('/\s+/u', ' ', strip_tags((new Client($base, 'pub2'))->get('/')['body']));
ok(str_contains($home, 'Treino planejado para você') && str_contains($home, 'Formado em Educação Física'), 'Renan edita a página pública pela própria interface');
eq(dbv("SELECT setting_value FROM settings WHERE setting_key = 'contact_whatsapp'"), '5519998089854', 'WhatsApp normalizado');

// ───────────── Resultado ─────────────
echo "\n" . str_repeat('─', 60) . "\n";
printf("Resultado: %d verificações aprovadas, %d falharam.\n", $results['pass'], $results['fail']);
if ($results['fail']) {
    echo "\nFalhas:\n - " . implode("\n - ", $results['errors']) . "\n";
    $log = glob("{$tmp}/storage/logs/*.log");
    foreach ($log as $l) echo "\nLog {$l}:\n" . substr((string) file_get_contents($l), -3000) . "\n";
    exit(1);
}
exit(0);
