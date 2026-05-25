<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Hi-Lo</h2>
<div class="grid md:grid-cols-3 gap-4">
  <div class="md:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow p-5">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-3">
      Will the next roll (0-99) be HIGH (&gt;<?= (int)$target ?>) or LOW (&lt;<?= (int)$target ?>)?  Roll = <?= (int)$target ?> is push.
      Multiplier <strong><?= number_format((float)$multiplier, 2) ?>x</strong>, house edge <?= (float)$edge ?>%.
    </div>

    <form id="hilo-form" onsubmit="return false;" class="space-y-3">
      <?= Csrf::field() ?>
      <div class="grid grid-cols-2 gap-3">
        <select name="coin_id" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
          <?php foreach ($coins as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= Sec::e($c['code']) ?></option>
          <?php endforeach; ?>
        </select>
        <input name="amount" type="number" step="0.00000001" min="0" placeholder="Bet amount" required
               class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      </div>
      <input name="client_seed" value="<?= Sec::e($clientSeed) ?>" placeholder="Client seed (optional)"
             class="w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">

      <div class="flex gap-3">
        <button type="button" onclick="CF.hilo(document.getElementById('hilo-form'), 'low', document.getElementById('hilo-result'))"
                class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 rounded-lg">LOW</button>
        <button type="button" onclick="CF.hilo(document.getElementById('hilo-form'), 'high', document.getElementById('hilo-result'))"
                class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-lg">HIGH</button>
      </div>

      <div id="hilo-result" class="text-center mt-4 min-h-[60px]"></div>
    </form>
  </div>

  <div class="space-y-3">
    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow text-sm">
      <div class="font-bold mb-2">Provably fair</div>
      <div class="text-slate-500 dark:text-slate-400">Server seed hash</div>
      <div class="font-mono break-all text-xs"><?= Sec::e($serverSeedHash) ?></div>
      <div class="text-slate-500 dark:text-slate-400 mt-2">Nonce</div>
      <div class="font-mono text-xs"><?= (int)$nonce ?></div>
      <div class="text-xs text-slate-500 mt-2">Server seed is rotated every 100 rounds and revealed on rotation.</div>
    </div>
    <a href="/hilo/history" class="block text-center bg-slate-100 dark:bg-slate-700 px-3 py-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600">My history</a>
  </div>
</div>

<h3 class="font-bold mt-8 mb-2">Recent rounds</h3>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">When</th><th>Bet</th><th>Pred</th><th>Roll</th><th>Result</th><th class="text-right">Payout</th></tr>
    </thead>
    <tbody>
      <?php foreach ($history as $h): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Helpers::timeAgo($h['created_at']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$h['bet_amount']) ?> <?= Sec::e($h['coin_code']) ?></td>
          <td><?= Sec::e($h['prediction']) ?></td>
          <td class="font-mono"><?= (int)$h['roll_value'] ?></td>
          <td><span class="font-bold <?= $h['result'] === 'win' ? 'text-emerald-500' : ($h['result']==='loss' ? 'text-rose-500' : 'text-amber-500') ?>"><?= Sec::e($h['result']) ?></span></td>
          <td class="text-right font-mono"><?= Helpers::formatCrypto((float)$h['payout']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($history)): ?>
        <tr><td colspan="6" class="p-6 text-center text-slate-400">No rounds yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
