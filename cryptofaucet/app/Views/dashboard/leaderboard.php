<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Referral Leaderboard</h2>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">Rank</th><th>Username</th><th class="text-right">Referrals</th><th class="text-right">Earned (their)</th></tr>
    </thead>
    <tbody>
      <?php foreach ($top as $i => $row): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3 font-bold">#<?= $i + 1 ?></td>
          <td><?= Sec::e($row['username']) ?></td>
          <td class="text-right font-mono"><?= (int)$row['refs'] ?></td>
          <td class="text-right font-mono"><?= Helpers::formatCrypto((float)$row['earned']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($top)): ?>
        <tr><td colspan="4" class="p-6 text-center text-slate-400">Be the first to refer!</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
