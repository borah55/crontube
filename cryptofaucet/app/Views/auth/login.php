<?php use App\Core\Csrf; ?>
<h2 class="text-2xl font-bold mb-1">Sign in</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Welcome back!</p>
<form method="post" action="/login" class="space-y-4">
  <?= Csrf::field() ?>
  <label class="block">
    <span class="text-sm">Username or email</span>
    <input name="login" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <label class="block">
    <span class="text-sm">Password</span>
    <input name="password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  </label>
  <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg">Sign in</button>
</form>
<div class="text-sm text-center mt-4 space-x-4">
  <a href="/register" class="text-indigo-600 hover:underline">Create account</a>
  <a href="/forgot" class="text-slate-500 hover:underline">Forgot password?</a>
</div>
