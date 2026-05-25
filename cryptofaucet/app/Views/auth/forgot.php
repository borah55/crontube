<?php use App\Core\Csrf; ?>
<h2 class="text-2xl font-bold mb-1">Reset password</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-4">We will email you a reset link.</p>
<form method="post" action="/forgot" class="space-y-3">
  <?= Csrf::field() ?>
  <label class="block">
    <span class="text-sm">Email</span>
    <input name="email" type="email" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg">Send reset link</button>
</form>
<div class="text-sm text-center mt-4"><a href="/login" class="text-slate-500 hover:underline">Back to sign in</a></div>
