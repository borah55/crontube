<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Withdraw via FaucetPay</h2>

<?php if ($faucetpayStatus !== 'configured'): ?>
  <div class="rounded p-3 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 mb-4 text-sm">
    FaucetPay API key is not configured.  Withdrawals will be queued for manual processing.
  </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-4">
  <div class="md:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-xl shadow">
    <form method="post" action="/withdraw" class="space-y-4">
      <?= Csrf::field() ?>
      <label class="block">
        <span class="text-sm">Coin</span>
        <select name="coin_id" required class="mt-1 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
          <?php foreach ($coins as $c): ?>
            <option value="<?= (int)$c['id'] ?>">
              <?= Sec::e($c['code']) ?> (min <?= Helpers::formatCrypto((float)$c['min_withdraw']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block">
        <span class="text-sm">Wallet address</span>
        <input name="wallet_address" required placeholder="FaucetPay email or wallet"
               value="<?= Sec::e($u['wallet_address'] ?? '') ?>"
               class="mt-1 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      </label>
      <label class="block">
        <span class="text-sm">Amount</span>
        <input name="amount" required type="number" step="0.00000001" min="0"
               class="mt-1 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
      </label>
      <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2 rounded-lg">Request withdrawal</button>
    </form>
  </div>
  <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow text-sm space-y-2">
    <div class="text-slate-500 dark:text-slate-400">Your balance</div>
    <div class="text-2xl font-bold font-mono"><?= Helpers::formatCrypto((float)$u['balance']) ?></div>
    <div class="text-xs text-slate-500 dark:text-slate-400">Max <?= (int)$maxPerIp ?> withdrawals per IP per day.</div>
  </div>
</div>

<h3 class="font-bold mt-8 mb-3">History</h3>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">When</th><th>Coin</th><th>Amount</th><th>Fee</th><th>Status</th><th>Tx</th></tr>
    </thead>
    <tbody>
      <?php foreach ($history as $w): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($w['created_at']) ?></td>
          <td><?= Sec::e($w['coin_code']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$w['amount']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$w['fee']) ?></td>
          <td>
            <span class="px-2 py-0.5 rounded-full text-xs
              <?= match($w['status']) {
                'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'pending', 'processing' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                default => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
              } ?>"><?= Sec::e($w['status']) ?></span>
          </td>
          <td class="font-mono text-xs"><?= Sec::e($w['payout_id'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($history)): ?>
        <tr><td colspan="6" class="p-6 text-center text-slate-400">No withdrawals yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
