<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Erro</title>
<link rel="stylesheet" href="/assets/css/app.css"></head>
<body><div class="auth-wrap"><div class="auth-card card" style="text-align:center">
<p class="muted small">Erro 500</p><h1 class="mt">Algo deu errado</h1>
<p class="muted mt">O problema foi registrado. Tente novamente em instantes.</p>
<?php if (!empty($debug)): ?><pre style="text-align:left;white-space:pre-wrap;font-size:12px;margin-top:16px"><?= htmlspecialchars((string) $debug) ?></pre><?php endif; ?>
<div class="btn-row mt-lg" style="justify-content:center"><a class="btn" href="/">Voltar</a></div>
</div></div></body></html>
