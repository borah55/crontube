<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Hi-Lo history</h2>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr>
        <th class="p-3">When</th><th>Coin</th><th>Bet</th><th>Pred</th><th>Roll</th>
        <th>Result</th><th class="text-right">Payout</th><th>Hash</th><th>Nonce</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($r['created_at']) ?></td>
          <td><?= Sec::e($r['coin_code']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['bet_amount']) ?></td>
          <td><?= Sec::e($r['prediction']) ?></td>
          <td class="font-mono"><?= (int)$r['roll_value'] ?></td>
          <td><span class="<?= $r['result'] === 'win' ? 'text-emerald-500' : ($r['result']==='loss' ? 'text-rose-500' : 'text-amber-500') ?>"><?= Sec::e($r['result']) ?></span></td>
          <td class="text-right font-mono"><?= Helpers::formatCrypto((float)$r['payout']) ?></td>
          <td class="font-mono text-xs truncate max-w-[10rem]"><?= Sec::e(substr($r['server_seed_hash'], 0, 12)) ?>&hellip;</td>
          <td class="font-mono text-xs"><?= (int)$r['nonce'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
