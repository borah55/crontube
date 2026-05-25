<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
$siteName = Sec::e($siteName ?? 'Crypto Faucet');
$user     = $authUser ?? null;
$flashes  = $flash ?? [];
$nav = [
    ['/admin',                 'Dashboard'],
    ['/admin/users',           'Users'],
    ['/admin/coins',           'Coins'],
    ['/admin/withdrawals',     'Withdrawals'],
    ['/admin/ptc',             'PTC Ads'],
    ['/admin/shortlinks',      'Shortlinks'],
    ['/admin/announcements',   'Announcements'],
    ['/admin/security',        'Security'],
    ['/admin/settings',        'Settings'],
    ['/admin/backup',          'Backup DB'],
];
$current = '/' . trim($_GET['_url'] ?? '/admin', '/');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= Sec::e(Csrf::token()) ?>">
<title>Admin &middot; <?= $siteName ?></title>
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
<body class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen flex">
  <aside class="w-60 bg-slate-900 text-slate-100 min-h-screen p-4 hidden md:block">
    <div class="font-bold text-xl mb-6">Admin</div>
    <nav class="space-y-1 text-sm">
      <?php foreach ($nav as [$url, $label]): ?>
        <a href="<?= $url ?>" class="block px-3 py-2 rounded
          <?= str_starts_with($current, $url) ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800' ?>">
          <?= Sec::e($label) ?>
        </a>
      <?php endforeach; ?>
      <a href="/dashboard" class="block px-3 py-2 rounded hover:bg-slate-800">&larr; User site</a>
    </nav>
  </aside>

  <div class="flex-1 min-w-0">
    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-4 py-3 flex justify-between items-center">
      <div class="font-semibold">Admin Panel</div>
      <div class="flex items-center gap-3">
        <button data-theme-toggle class="px-2 py-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700">
          <span class="dark:hidden">&#9788;</span><span class="hidden dark:inline">&#9789;</span>
        </button>
        <span class="text-sm"><?= Sec::e($user['username'] ?? '') ?></span>
        <form method="post" action="/logout"><?= Csrf::field() ?><button class="text-sm hover:underline">Logout</button></form>
      </div>
    </header>

    <?php foreach ($flashes as $type => $msgs): ?>
      <?php foreach ($msgs as $m): ?>
        <div class="m-4 rounded-lg p-3 text-sm
          <?= $type === 'error' ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-200'
             : ($type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200'
                : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200') ?>">
          <?= Sec::e($m) ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <main class="p-4 md:p-6"><?= $content ?></main>
  </div>
<script src="<?= Helpers::asset('js/app.js') ?>"></script>
</body>
</html>
