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
        $counts = ['students' => $c['pending'], 'chat' => $c['unread'], 'assessments' => $c['assessments'], 'requests' => $c['requests']];
    } else {
        $counts['chat'] = (int) DB::val('SELECT COUNT(*) FROM messages WHERE student_id = ? AND read_at IS NULL AND (sender_id IS NULL OR sender_id <> ?)', [$u['id'], $u['id']]);
    }
}
$student = [
    ['home', '/app', 'home', 'Início'],
    ['workouts', '/app/treinos', 'dumbbell', 'Treinos'],
    ['calendar', '/app/calendario', 'calendar', 'Calendário'],
    ['chat', '/app/mensagens', 'message', 'Mensagens'],
    ['self', '/app/autoavaliacao', 'smile', 'Autoavaliação'],
    ['nutrition', '/app/alimentacao', 'droplet', 'Alimentação'],
    ['assessments', '/app/avaliacoes', 'camera', 'Avaliações físicas'],
    ['subscription', '/app/assinatura', 'card', 'Assinatura'],
    ['profile', '/app/perfil', 'user', 'Meu perfil'],
];
$trainer = [
    ['home', '/painel', 'home', 'Painel'],
    ['students', '/painel/alunos', 'users', 'Meus alunos'],
    ['chat', '/painel/mensagens', 'message', 'Mensagens'],
    ['calendar', '/painel/calendario', 'calendar', 'Calendário'],
    ['assessments', '/painel/avaliacoes', 'camera', 'Avaliações físicas'],
    ['requests', '/painel/solicitacoes', 'repeat', 'Pedidos de mudança'],
    ['_label', '', '', 'Planejamento'],
    ['library', '/painel/biblioteca', 'book', 'Biblioteca'],
    ['templates', '/painel/modelos', 'layers', 'Modelos de treino'],
    ['_label', '', '', 'Gestão'],
    ['finance', '/painel/financeiro', 'card', 'Financeiro'],
    ['settings', '/painel/configuracoes', 'settings', 'Configurações'],
];
$items = $mode === 'trainer' ? $trainer : $student;
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(($title ?? '') !== '' ? $title . ' · ' . $brand : $brand) ?></title>
<meta name="theme-color" content="#090707">
<meta name="color-scheme" content="dark">
<meta name="robots" content="noindex">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>:root{--accent:<?= e(accent_color()) ?>}</style>
</head>
<body data-csrf="<?= e(csrf_token()) ?>" data-mode="<?= e($mode) ?>"<?= $mode === 'student' ? ' class="has-tabbar"' : '' ?>>
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<div class="shell">
  <aside class="sidebar" id="sidebar" aria-label="Menu principal">
    <a class="brand" href="<?= $mode === 'trainer' ? '/painel' : '/app' ?>"><span class="brand-mark">WP</span><span><?= e($brand) ?></span></a>
    <nav class="nav">
      <?php foreach ($items as [$key, $href, $ico, $label]): ?>
        <?php if ($key === '_label'): ?>
          <div class="nav-label"><?= e($label) ?></div>
        <?php else: ?>
          <a href="<?= e($href) ?>" class="<?= $nav === $key ? 'active' : '' ?>" <?= $nav === $key ? 'aria-current="page"' : '' ?>>
            <?= icon($ico) ?><span><?= e($label) ?></span>
            <?php if (!empty($counts[$key])): ?><span class="count" data-counter="<?= e($key) ?>"><?= (int) $counts[$key] ?></span><?php endif; ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <?php if ($u): ?>
        <div class="me"><?= avatar($u, 'sm') ?><div class="grow truncate"><?= e(first_name($u['name'])) ?><small><?= $u['role'] === 'developer' ? 'Desenvolvedor · ' . ($mode === 'trainer' ? 'interface do Renan' : 'interface do aluno') : ($mode === 'trainer' ? 'Treinador' : 'Aluno') ?></small></div></div>
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
      <button class="btn ghost icon-only menu-btn" type="button" data-open-menu aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menu"><?= icon('menu') ?></button>
      <div class="title"><?= e($title ?? '') ?></div>
      <div class="spacer"></div>
      <?= $topRight ?? '' ?>
    </header>
    <main id="main" class="content<?= !empty($narrow) ? ' narrow' : '' ?>" tabindex="-1">
      <?= $content ?>
    </main>
  </div>
</div>
<?php if ($mode === 'student'): ?>
<nav class="tabbar" aria-label="Atalhos">
  <?php foreach ([['home', '/app', 'home', 'Início'], ['workouts', '/app/treinos', 'dumbbell', 'Treinos'], ['calendar', '/app/calendario', 'calendar', 'Agenda'], ['chat', '/app/mensagens', 'message', 'Chat']] as [$key, $href, $ico, $label]): ?>
    <a href="<?= e($href) ?>" class="<?= $nav === $key ? 'active' : '' ?>" <?= $nav === $key ? 'aria-current="page"' : '' ?>><?= icon($ico) ?><span><?= e($label) ?></span><?php if (!empty($counts[$key])): ?><i class="tab-dot" aria-label="<?= (int) $counts[$key] ?> não lidas"></i><?php endif; ?></a>
  <?php endforeach; ?>
  <button type="button" data-open-menu aria-controls="sidebar" aria-expanded="false"><?= icon('menu') ?><span>Mais</span></button>
</nav>
<?php endif; ?>
<div class="toasts" id="toasts" aria-live="polite" data-flashes="<?= e(json_enc(take_flashes())) ?>"></div>
<div class="offline-bar" role="status"><?= icon('wifi-off', 'icon-sm') ?> Sem conexão — suas alterações serão enviadas quando voltar.</div>
<div class="update-banner" id="update-banner" role="status"><span class="grow small">Nova versão disponível.</span><button class="btn sm" type="button" data-sw-update>Atualizar</button></div>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php foreach (($scripts ?? []) as $s): ?><script src="<?= asset('js/' . $s . '.js') ?>" defer></script><?php endforeach; ?>
</body>
</html>
