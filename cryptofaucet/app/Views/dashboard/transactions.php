<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Transactions</h2>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">When</th><th>Type</th><th>Coin</th><th class="text-right">Amount</th><th class="text-right">Balance</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($r['created_at']) ?></td>
          <td><?= Sec::e($r['type']) ?></td>
          <td><?= Sec::e($r['coin_code'] ?? '-') ?></td>
          <td class="text-right font-mono <?= ((float)$r['amount']) >= 0 ? 'text-emerald-600' : 'text-rose-500' ?>">
            <?= Helpers::formatCrypto((float)$r['amount']) ?>
          </td>
          <td class="text-right font-mono"><?= Helpers::formatCrypto((float)$r['balance_after']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="p-6 text-center text-slate-400">Nothing yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php if (($pages ?? 1) > 1): ?>
  <div class="mt-3 flex justify-center gap-1 text-sm">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-700' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
