<?php
/** @var string $content */
$u = Auth::user();
$mode = Auth::mode();
$nav = $nav ?? '';
$brand = Settings::get('brand_name');
$counts = [];
if ($u) {
    if ($mode === 'trainer') {
        $c = Cron::trainerCounters();
        $counts = ['home' => $c['pending'] + $c['assessments'] + $c['requests'], 'chat' => $c['unread']];
    } else {
        $counts['chat'] = (int) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND read_at IS NULL AND deleted_at IS NULL AND (sender_id IS NULL OR sender_id <> ?)', [$u['id'], $u['id']]);
    }
}
// Itens principais (5) — as telas internas acendem o item a que pertencem.
if ($mode === 'trainer') {
    $items = [
        ['home', '/painel', 'home', 'Início'],
        ['students', '/painel/alunos', 'users', 'Alunos'],
        ['chat', '/painel/mensagens', 'message', 'Chat'],
        ['calendar', '/painel/calendario', 'calendar', 'Agenda'],
        ['more', '/painel/mais', 'grid', 'Mais'],
    ];
    $group = ['requests' => 'home', 'assessments' => 'home', 'library' => 'more', 'templates' => 'more', 'finance' => 'more', 'settings' => 'more'];
    $subs = [['library', '/painel/biblioteca', 'Biblioteca'], ['finance', '/painel/financeiro', 'Financeiro'], ['settings', '/painel/configuracoes', 'Configurações']];
    $homeUrl = '/painel';
} else {
    $items = [
        ['home', '/app', 'home', 'Hoje'],
        ['workouts', '/app/treinos', 'dumbbell', 'Treinos'],
        ['calendar', '/app/calendario', 'calendar', 'Agenda'],
        ['chat', '/app/mensagens', 'message', 'Chat'],
        ['me', '/app/eu', 'user', 'Eu'],
    ];
    $group = ['self' => 'home', 'nutrition' => 'home', 'assessments' => 'me', 'subscription' => 'me', 'profile' => 'me', 'progress' => 'me'];
    $subs = [];
    $homeUrl = '/app';
}
$active = $group[$nav] ?? $nav;
$activeIdx = 0;
foreach ($items as $i => $it) if ($it[0] === $active) $activeIdx = $i;
$theme = ui_theme();
?><!doctype html>
<html lang="pt-BR"<?= $theme ? ' data-theme="' . e($theme) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(($title ?? '') !== '' ? $title . ' · ' . $brand : $brand) ?></title>
<meta name="theme-color" content="#f3f6f9" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#090707" media="(prefers-color-scheme: dark)">
<meta name="robots" content="noindex">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/Anton-Regular.woff" as="font" type="font/woff" crossorigin>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-csrf="<?= e(csrf_token()) ?>" data-mode="<?= e($mode) ?>" class="has-tabbar">
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<div class="shell">
  <aside class="sidebar" id="sidebar" aria-label="Menu principal">
    <a class="brand" href="<?= $homeUrl ?>"><span class="brand-mark">WP</span><span><?= e($brand) ?></span></a>
    <nav class="nav">
      <?php foreach ($items as [$key, $href, $ico, $label]): ?>
        <a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>" <?= $nav === $key ? 'aria-current="page"' : '' ?>>
          <?= icon($ico) ?><span><?= e($label) ?></span>
          <?php if (!empty($counts[$key])): ?><span class="count" data-counter="<?= e($key) ?>"><?= (int) $counts[$key] ?></span><?php endif; ?>
        </a>
        <?php if ($key === 'more'): foreach ($subs as [$sk, $sh, $sl]): ?>
          <a href="<?= e($sh) ?>" class="sub <?= $nav === $sk || ($sk === 'library' && $nav === 'templates') ? 'active' : '' ?>"><?= e($sl) ?></a>
        <?php endforeach; endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <?php if ($u): ?>
        <div class="me"><?= avatar($u, 'sm') ?><div class="grow truncate"><?= e(first_name($u['name'])) ?><small><?= $u['role'] === 'developer' ? 'Desenvolvedor · ' . ($mode === 'trainer' ? 'visão do Renan' : 'visão do aluno') : ($mode === 'trainer' ? 'Treinador' : 'Aluno') ?></small></div></div>
        <?php if (Auth::isDeveloper()): ?>
          <form method="post" action="/alternar-interface">
            <?= csrf_field() ?>
            <input type="hidden" name="to" value="<?= $mode === 'trainer' ? 'student' : 'trainer' ?>">
            <button class="navlink switch-interface" type="submit"><?= icon('repeat') ?><span>Alternar para <?= $mode === 'trainer' ? 'aluno' : 'Renan' ?></span></button>
          </form>
        <?php endif; ?>
        <form method="post" action="/sair">
          <?= csrf_field() ?>
          <button class="navlink" type="submit"><?= icon('logout') ?><span>Sair</span></button>
        </form>
      <?php endif; ?>
    </div>
  </aside>
  <div class="scrim" data-close-menu></div>
  <div class="main">
    <header class="topbar">
      <a href="<?= $homeUrl ?>" aria-label="Início"><span class="brand-mark">WP</span></a>
      <div class="title"><?= e($title ?? '') ?></div>
      <div class="spacer"></div>
      <?= $topRight ?? '' ?>
      <button class="theme-btn" type="button" data-theme-toggle aria-label="Alternar modo claro/noturno"><?= icon('moon', 'icon moon') ?><?= icon('sun', 'icon sun') ?></button>
    </header>
    <main id="main" class="content<?= !empty($narrow) ? ' narrow' : '' ?>" tabindex="-1">
      <?= $content ?>
    </main>
  </div>
</div>
<nav class="tabbar" aria-label="Navegação" style="--idx:<?= $activeIdx ?>">
  <span class="pill" aria-hidden="true"></span>
  <?php foreach ($items as [$key, $href, $ico, $label]): ?>
    <a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>" <?= $nav === $key ? 'aria-current="page"' : '' ?>><?= icon($ico) ?><span><?= e($label) ?></span><?php if (!empty($counts[$key])): ?><i class="tab-dot" aria-label="<?= (int) $counts[$key] ?> pendentes"></i><?php endif; ?></a>
  <?php endforeach; ?>
</nav>
<div class="toasts" id="toasts" aria-live="polite" data-flashes="<?= e(json_enc(take_flashes())) ?>"></div>
<div class="offline-bar" role="status"><?= icon('wifi-off', 'icon-sm') ?> Sem conexão — suas alterações serão enviadas quando voltar.</div>
<div class="update-banner" id="update-banner" role="status"><span class="grow small">Nova versão disponível.</span><button class="btn sm" type="button" data-sw-update>Atualizar</button></div>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php foreach (($scripts ?? []) as $s): ?><script src="<?= asset('js/' . $s . '.js') ?>" defer></script><?php endforeach; ?>
</body>
</html>
