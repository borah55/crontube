<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Coins</h2>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 mb-4">
  <h3 class="font-semibold mb-3">Add / edit coin</h3>
  <form method="post" action="/admin/coins/save" class="grid md:grid-cols-3 gap-3 text-sm">
    <?= Csrf::field() ?>
    <input name="id" type="hidden">
    <input name="code" placeholder="Code (LTC)" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="name" placeholder="Name" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="faucetpay_token" placeholder="FaucetPay token" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="min_reward" placeholder="Min reward" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="max_reward" placeholder="Max reward" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="min_withdraw" placeholder="Min withdraw" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="withdraw_fee" placeholder="Flat fee" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="withdraw_fee_percent" placeholder="Fee %" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="display_order" placeholder="Order" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
    <button class="md:col-span-3 bg-indigo-600 text-white py-2 rounded">Save coin</button>
  </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">Code</th><th>Name</th><th>Reward</th><th>Min Withdraw</th><th>Fee</th><th>Active</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3 font-bold"><?= Sec::e($r['code']) ?></td>
          <td><?= Sec::e($r['name']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['min_reward']) ?> – <?= Helpers::formatCrypto((float)$r['max_reward']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['min_withdraw']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['withdraw_fee']) ?> + <?= Sec::e($r['withdraw_fee_percent']) ?>%</td>
          <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
          <td>
            <form method="post" action="/admin/coins/<?= (int)$r['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete this coin?')">
              <?= Csrf::field() ?>
              <button class="text-xs bg-rose-600 text-white px-2 py-1 rounded">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
