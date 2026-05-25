<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Withdrawals</h2>

<div class="mb-4 flex gap-2 text-sm">
  <?php foreach (['pending','processing','paid','failed','cancelled',''] as $s): ?>
    <a href="?status=<?= urlencode($s) ?>" class="px-3 py-1 rounded <?= $status === $s ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-700' ?>">
      <?= $s === '' ? 'All' : $s ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">When</th><th>User</th><th>Coin</th><th>Amount</th><th>Net</th><th>Address</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $w): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3 text-xs"><?= Sec::e($w['created_at']) ?></td>
          <td><?= Sec::e($w['username'] ?? '-') ?></td>
          <td><?= Sec::e($w['coin_code']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$w['amount']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$w['net_amount']) ?></td>
          <td class="text-xs font-mono break-all max-w-[14rem]"><?= Sec::e($w['wallet_address']) ?></td>
          <td><?= Sec::e($w['status']) ?></td>
          <td class="space-x-1 whitespace-nowrap">
            <?php if ($w['status'] === 'pending'): ?>
              <form method="post" action="/admin/withdrawals/<?= (int)$w['id'] ?>/approve" class="inline" onsubmit="return confirm('Send via FaucetPay now?')">
                <?= Csrf::field() ?>
                <button class="text-xs bg-emerald-600 text-white px-2 py-1 rounded">Approve</button>
              </form>
              <form method="post" action="/admin/withdrawals/<?= (int)$w['id'] ?>/reject" class="inline" onsubmit="return confirm('Reject and refund?')">
                <?= Csrf::field() ?>
                <button class="text-xs bg-rose-600 text-white px-2 py-1 rounded">Reject</button>
              </form>
            <?php else: ?>
              <span class="text-xs text-slate-400"><?= Sec::e($w['payout_id'] ?? '') ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
