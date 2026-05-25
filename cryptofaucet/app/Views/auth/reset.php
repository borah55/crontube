<?php
use App\Core\Csrf;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-1">Choose a new password</h2>
<form method="post" action="/reset/<?= Sec::e($token) ?>" class="space-y-3">
  <?= Csrf::field() ?>
  <label class="block">
    <span class="text-sm">New password</span>
    <input name="password" type="password" minlength="8" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <label class="block">
    <span class="text-sm">Confirm</span>
    <input name="password_confirm" type="password" minlength="8" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg">Update password</button>
</form>
