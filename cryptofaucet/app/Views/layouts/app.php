<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
use App\Core\Setting;
$siteName = Sec::e($siteName ?? 'Crypto Faucet');
$user     = $authUser ?? null;
$flashes  = $flash ?? [];
?><!doctype html>
<html lang="en" class="">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= Sec::e(Csrf::token()) ?>">
<title><?= $siteName ?></title>
<meta name="description" content="<?= Sec::e(Setting::get('site_description','')) ?>">
<script>
// Pre-paint dark mode (avoid FOUC)
(function () {
    try {
        var t = localStorage.getItem('cf_theme');
        if (!t && matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
        if (t === 'dark') document.documentElement.classList.add('dark');
    } catch (e) {}
})();
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' }</script>
<link rel="stylesheet" href="<?= Helpers::asset('css/app.css') ?>">
</head>
<body class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen flex flex-col">

<header class="bg-white dark:bg-slate-800 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 font-bold text-lg">
      <span class="inline-flex w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 text-white items-center justify-center">CF</span>
      <?= $siteName ?>
    </a>
    <nav class="hidden md:flex items-center gap-1">
      <?php if ($user): ?>
        <a href="/dashboard" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Dashboard</a>
        <a href="/faucet" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Faucet</a>
        <a href="/hilo" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Hi-Lo</a>
        <a href="/ptc" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">PTC</a>
        <a href="/shortlinks" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Shortlinks</a>
        <a href="/withdraw" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Withdraw</a>
        <a href="/referrals" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Referrals</a>
        <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
          <a href="/admin" class="px-3 py-2 rounded text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30">Admin</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="/login" class="px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Login</a>
        <a href="/register" class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Sign up</a>
      <?php endif; ?>
    </nav>
    <div class="flex items-center gap-2">
      <button data-theme-toggle class="px-2 py-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700" title="Toggle theme">
        <span class="dark:hidden">&#9788;</span>
        <span class="hidden dark:inline">&#9789;</span>
      </button>
      <?php if ($user): ?>
        <span class="hidden sm:inline-flex items-center px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm">
          Bal: <span id="cf-balance" class="ml-1 font-mono"><?= Sec::e(\App\Core\Helpers::formatCrypto((float)$user['balance'])) ?></span>
        </span>
        <a href="/profile" class="px-2 py-1 hover:underline text-sm"><?= Sec::e($user['username']) ?></a>
        <form method="post" action="/logout" class="inline">
          <?= Csrf::field() ?>
          <button class="px-2 py-1 text-sm hover:underline">Logout</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php foreach ($flashes as $type => $msgs): ?>
  <?php foreach ($msgs as $m): ?>
    <div class="max-w-7xl mx-auto w-full mt-3 px-4">
      <div class="rounded-lg p-3 text-sm
        <?= $type === 'error' ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-200'
           : ($type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200'
              : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200') ?>">
        <?= Sec::e($m) ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>

<main class="flex-1 w-full max-w-7xl mx-auto px-4 py-6">
  <?= $content ?>
</main>

<footer class="border-t border-slate-200 dark:border-slate-700 py-6 text-center text-sm text-slate-500">
  &copy; <?= date('Y') ?> <?= $siteName ?>.  Built with PHP &amp; Tailwind.
</footer>

<script src="<?= Helpers::asset('js/app.js') ?>"></script>
</body>
</html>
