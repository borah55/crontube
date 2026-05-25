<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
$siteName = Sec::e($siteName ?? 'Crypto Faucet');
$flashes  = $flash ?? [];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= Sec::e(Csrf::token()) ?>">
<meta name="base-path" content="<?= Sec::e(\App\Core\Application::$basePath) ?>">
<title><?= $siteName ?></title>
<script>
(function () { try {
    var t = localStorage.getItem('cf_theme');
    if (!t && matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
    if (t === 'dark') document.documentElement.classList.add('dark');
} catch (e) {} })();
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' }</script>
<link rel="stylesheet" href="<?= Helpers::asset('css/app.css') ?>">
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-700 via-purple-700 to-violet-800 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-slate-800 dark:text-slate-100 flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <a href="/" class="inline-flex items-center gap-2 text-white font-bold text-2xl">
        <span class="inline-flex w-10 h-10 rounded-full bg-white/15 items-center justify-center">CF</span>
        <?= $siteName ?>
      </a>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6">
      <?php foreach ($flashes as $type => $msgs): ?>
        <?php foreach ($msgs as $m): ?>
          <div class="mb-4 rounded-lg p-3 text-sm
            <?= $type === 'error' ? 'bg-rose-50 text-rose-700'
               : ($type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700') ?>">
            <?= Sec::e($m) ?>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
    <p class="text-center text-white/70 text-xs mt-4">&copy; <?= date('Y') ?> <?= $siteName ?></p>
  </div>
<script src="<?= Helpers::asset('js/app.js') ?>"></script>
</body>
</html>
