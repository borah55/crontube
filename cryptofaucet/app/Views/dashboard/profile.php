<?php
use App\Core\Csrf;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Profile</h2>

<div class="grid md:grid-cols-2 gap-4">
  <div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow">
    <h3 class="font-bold mb-3">Wallet address</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">Used as the default for FaucetPay withdrawals.</p>
    <form method="post" action="/profile/wallet" class="space-y-3">
      <?= Csrf::field() ?>
      <input name="wallet_address" value="<?= Sec::e($u['wallet_address'] ?? '') ?>"
             placeholder="FaucetPay email or wallet address"
             class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Save</button>
    </form>
  </div>

  <div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow">
    <h3 class="font-bold mb-3">Change password</h3>
    <form method="post" action="/profile/password" class="space-y-3">
      <?= Csrf::field() ?>
      <input name="current_password" type="password" placeholder="Current password" required
             class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      <input name="new_password" type="password" minlength="8" placeholder="New password" required
             class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      <input name="confirm_password" type="password" minlength="8" placeholder="Confirm new password" required
             class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Update password</button>
    </form>
  </div>

  <div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow md:col-span-2">
    <h3 class="font-bold mb-2">Account info</h3>
    <dl class="text-sm grid md:grid-cols-3 gap-2">
      <div><dt class="text-slate-500">Email</dt><dd><?= Sec::e($u['email']) ?></dd></div>
      <div><dt class="text-slate-500">Joined</dt><dd><?= Sec::e($u['created_at']) ?></dd></div>
      <div><dt class="text-slate-500">Referral code</dt><dd class="font-mono"><?= Sec::e($u['referral_code']) ?></dd></div>
    </dl>
  </div>
</div>
