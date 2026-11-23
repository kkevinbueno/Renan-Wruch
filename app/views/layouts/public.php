<?php
/** @var string $content */
$brand = Settings::get('brand_name');
$u = Auth::user();
$theme = ui_theme();
?><!doctype html>
<html lang="pt-BR"<?= $theme ? ' data-theme="' . e($theme) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(($title ?? '') !== '' && $title !== $brand ? $title . ' · ' . $brand : $brand . ' — Consultoria esportiva online') ?></title>
<meta name="description" content="<?= e(Settings::get('public_intro')) ?>">
<meta name="theme-color" content="#f3f6f9" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#090707" media="(prefers-color-scheme: dark)">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/Anton-Regular.woff" as="font" type="font/woff" crossorigin>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-csrf="<?= e(csrf_token()) ?>">
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<header class="public-nav">
  <a class="brand" href="/"><span class="brand-mark">WP</span><span><?= e($brand) ?></span></a>
  <div class="links">
    <button class="theme-btn" type="button" data-theme-toggle aria-label="Alternar modo claro/noturno"><?= icon('moon', 'icon moon') ?><?= icon('sun', 'icon sun') ?></button>
    <?php if ($u): ?>
      <a class="btn sm" href="<?= e(Auth::home()) ?>">Acessar</a>
    <?php else: ?>
      <a class="btn secondary sm" href="/entrar">Entrar</a>
      <a class="btn sm" href="/cadastro">Criar conta</a>
    <?php endif; ?>
  </div>
</header>
<main id="main" tabindex="-1">
  <?= $content ?>
</main>
<footer class="public-foot">
  <span class="row"><span class="brand-mark" style="width:30px;height:30px;font-size:12px">WP</span>© <?= local_now()->format('Y') ?> <?= e($brand) ?> · <?= e(Settings::get('trainer_name')) ?></span>
  <span class="row"><a href="/privacidade">Privacidade</a><a href="/termos">Termos de uso</a></span>
</footer>
<div class="toasts" id="toasts" aria-live="polite" data-flashes="<?= e(json_enc(take_flashes())) ?>"></div>
<div class="offline-bar" role="status"><?= icon('wifi-off', 'icon-sm') ?> Sem conexão</div>
<div class="update-banner" id="update-banner" role="status"><span class="grow small">Nova versão disponível.</span><button class="btn sm" type="button" data-sw-update>Atualizar</button></div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
