<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<div class="grid md:grid-cols-3 gap-4 mb-6">
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-5 md:col-span-2">
    <div class="flex items-center justify-between mb-3">
      <div>
        <div class="text-sm text-slate-500 dark:text-slate-400">Welcome back</div>
        <div class="text-2xl font-bold"><?= Sec::e($u['username']) ?></div>
      </div>
      <button onclick="CF.dailyBonus(this)" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 rounded-lg">
        Daily bonus
      </button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
      <div class="bg-slate-100 dark:bg-slate-700 p-3 rounded-lg">
        <div class="text-xs text-slate-500 dark:text-slate-400">Balance</div>
        <div class="font-bold"><span id="cf-balance"><?= Helpers::formatCrypto((float)$u['balance']) ?></span></div>
      </div>
      <div class="bg-slate-100 dark:bg-slate-700 p-3 rounded-lg">
        <div class="text-xs text-slate-500 dark:text-slate-400">Today earned</div>
        <div class="font-bold"><?= Helpers::formatCrypto((float)$stats['today_earned']) ?></div>
      </div>
      <div class="bg-slate-100 dark:bg-slate-700 p-3 rounded-lg">
        <div class="text-xs text-slate-500 dark:text-slate-400">Total earned</div>
        <div class="font-bold"><?= Helpers::formatCrypto((float)$stats['total_earned']) ?></div>
      </div>
      <div class="bg-slate-100 dark:bg-slate-700 p-3 rounded-lg">
        <div class="text-xs text-slate-500 dark:text-slate-400">Withdrawn</div>
        <div class="font-bold"><?= Helpers::formatCrypto((float)$stats['total_withdrawn']) ?></div>
      </div>
    </div>
  </div>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-5">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Referrals</div>
    <div class="text-3xl font-bold"><?= number_format((int)$stats['referrals']) ?></div>
    <div class="text-xs text-slate-500 dark:text-slate-400">earned <?= Helpers::formatCrypto((float)$stats['referral_earn']) ?></div>
    <a href="/referrals" class="mt-2 inline-block text-indigo-600 hover:underline text-sm">Invite friends &rarr;</a>
  </div>
</div>

<div class="grid md:grid-cols-3 gap-3 mb-6">
  <a href="/faucet" class="block bg-gradient-to-br from-indigo-600 to-purple-600 text-white p-5 rounded-xl shadow hover:opacity-90">
    <div class="font-bold text-lg">Faucet</div>
    <div class="text-sm opacity-80">Claim every <?= (int)\App\Core\Setting::get('faucet_cooldown_seconds', 300) / 60 ?> minutes</div>
  </a>
  <a href="/hilo" class="block bg-gradient-to-br from-emerald-600 to-teal-600 text-white p-5 rounded-xl shadow hover:opacity-90">
    <div class="font-bold text-lg">Hi-Lo</div>
    <div class="text-sm opacity-80">Provably fair</div>
  </a>
  <a href="/withdraw" class="block bg-gradient-to-br from-amber-600 to-rose-600 text-white p-5 rounded-xl shadow hover:opacity-90">
    <div class="font-bold text-lg">Withdraw</div>
    <div class="text-sm opacity-80">Pay out via FaucetPay</div>
  </a>
</div>

<?php if (!empty($announcements)): ?>
  <div class="space-y-2 mb-6">
    <?php foreach ($announcements as $a): ?>
      <div class="rounded-lg p-3 border-l-4 border-indigo-500 bg-white dark:bg-slate-800">
        <div class="font-semibold"><?= Sec::e($a['title']) ?></div>
        <div class="text-sm text-slate-600 dark:text-slate-300"><?= nl2br(Sec::e($a['body'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-5">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-bold">Recent transactions</h3>
    <a href="/transactions" class="text-indigo-600 text-sm hover:underline">View all</a>
  </div>
  <table class="w-full text-sm">
    <thead class="text-left text-slate-500 dark:text-slate-400">
      <tr><th class="py-2">When</th><th>Type</th><th>Coin</th><th class="text-right">Amount</th></tr>
    </thead>
    <tbody>
      <?php foreach ($recent as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="py-2"><?= Helpers::timeAgo($r['created_at']) ?></td>
          <td><?= Sec::e($r['type']) ?></td>
          <td><?= Sec::e($r['coin_code'] ?? '-') ?></td>
          <td class="text-right font-mono <?= ((float)$r['amount']) >= 0 ? 'text-emerald-600' : 'text-rose-500' ?>">
            <?= Helpers::formatCrypto((float)$r['amount']) ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recent)): ?>
        <tr><td colspan="4" class="py-6 text-center text-slate-400">No transactions yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
