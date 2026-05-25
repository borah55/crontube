<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Referrals</h2>
<div class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white rounded-xl p-5 mb-4">
  <div class="text-sm opacity-80 mb-1">Earn <?= (int)$percent ?>% on every claim made by your referrals.</div>
  <div class="flex flex-col sm:flex-row gap-2 mt-2">
    <input value="<?= Sec::e($referralUrl) ?>" readonly id="ref-url"
           class="flex-1 rounded-lg p-2 text-slate-800 font-mono text-sm">
    <button class="bg-white text-indigo-700 font-semibold px-4 py-2 rounded-lg"
            onclick="navigator.clipboard.writeText(document.getElementById('ref-url').value); CF.toast('Link copied','success')">
      Copy link
    </button>
  </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">User</th><th>Joined</th><th class="text-right">Earned</th></tr>
    </thead>
    <tbody>
      <?php foreach ($referrals as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($r['username']) ?></td>
          <td><?= Sec::e($r['created_at']) ?></td>
          <td class="text-right font-mono"><?= Helpers::formatCrypto((float)$r['total_earned']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($referrals)): ?>
        <tr><td colspan="3" class="p-6 text-center text-slate-400">No referrals yet. Share your link!</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
