<?php
use App\Core\Csrf;
use App\Core\Security as Sec;
use App\Core\Setting;
$siteKey = (string)Setting::get('recaptcha_site_key', '');
?>
<h2 class="text-2xl font-bold mb-1">Create account</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Earn your first crypto in seconds.</p>
<form method="post" action="/register" class="space-y-3">
  <?= Csrf::field() ?>
  <label class="block">
    <span class="text-sm">Username</span>
    <input name="username" required minlength="3" maxlength="32" pattern="[a-z0-9_]+"
           class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
    <span class="text-xs text-slate-400">Lowercase letters, digits, underscore.</span>
  </label>
  <label class="block">
    <span class="text-sm">Email</span>
    <input name="email" type="email" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <label class="block">
    <span class="text-sm">Password</span>
    <input name="password" type="password" minlength="8" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <label class="block">
    <span class="text-sm">Confirm password</span>
    <input name="password_confirm" type="password" minlength="8" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <label class="block">
    <span class="text-sm">Referral (optional)</span>
    <input name="referral" value="<?= Sec::e($referral ?? '') ?>" class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <?php if ($siteKey): ?>
    <div class="g-recaptcha" data-sitekey="<?= Sec::e($siteKey) ?>"></div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
  <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg">Create account</button>
</form>
<div class="text-sm text-center mt-4">
  Already have an account? <a href="/login" class="text-indigo-600 hover:underline">Sign in</a>
</div>
