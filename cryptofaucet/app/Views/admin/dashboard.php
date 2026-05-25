<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<div class="flex flex-wrap items-center gap-3 mb-6">
  <h2 class="text-2xl font-bold flex-1">Dashboard</h2>
  <span class="cf-live text-sm text-emerald-600"><?= (int)$stats['online_users'] ?> online</span>
  <form method="post" action="/admin/maintenance/toggle" class="inline">
    <?= Csrf::field() ?>
    <button class="text-sm px-3 py-1 rounded <?= $stats['maintenance'] ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-700' ?>">
      Maintenance: <?= $stats['maintenance'] ? 'ON' : 'OFF' ?>
    </button>
  </form>
</div>

<?php
$cards = [
    ['Users', number_format($stats['users'])],
    ['Banned', number_format($stats['users_banned'])],
    ['Joined today', number_format($stats['users_today'])],
    ['Claims today', number_format($stats['claims_today'])],
    ['Claims total', number_format($stats['claims_total'])],
    ['Pending withdrawals', number_format($stats['pending_wd'])],
    ['Paid withdrawals', number_format($stats['paid_wd_count'])],
    ['Crypto paid', Helpers::formatCrypto((float)$stats['paid_wd'])],
    ['Hi-Lo volume', Helpers::formatCrypto((float)$stats['hilo_volume'])],
    ['Hi-Lo PnL (house)', Helpers::formatCrypto((float)$stats['hilo_pnl'])],
    ['PTC views', number_format($stats['ptc_views'])],
];
?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
  <?php foreach ($cards as [$label, $val]): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4">
      <div class="text-xs text-slate-500 dark:text-slate-400"><?= Sec::e($label) ?></div>
      <div class="text-xl font-bold mt-1"><?= Sec::e((string)$val) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
    <h3 class="font-bold p-4 border-b border-slate-100 dark:border-slate-700">Recent withdrawals</h3>
    <table class="w-full text-sm">
      <thead class="text-left bg-slate-50 dark:bg-slate-700/50"><tr><th class="p-3">User</th><th>Coin</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recentWd as $w): ?>
          <tr class="border-t border-slate-100 dark:border-slate-700">
            <td class="p-3"><?= Sec::e($w['username'] ?? '-') ?></td>
            <td><?= Sec::e($w['coin_code'] ?? '-') ?></td>
            <td class="font-mono"><?= Helpers::formatCrypto((float)$w['amount']) ?></td>
            <td><?= Sec::e($w['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
    <h3 class="font-bold p-4 border-b border-slate-100 dark:border-slate-700">Security log</h3>
    <table class="w-full text-sm">
      <thead class="text-left bg-slate-50 dark:bg-slate-700/50"><tr><th class="p-3">When</th><th>User</th><th>Event</th><th>Severity</th></tr></thead>
      <tbody>
        <?php foreach ($recentSecurity as $s): ?>
          <tr class="border-t border-slate-100 dark:border-slate-700">
            <td class="p-3"><?= Helpers::timeAgo($s['created_at']) ?></td>
            <td><?= Sec::e($s['username'] ?? '-') ?></td>
            <td><?= Sec::e($s['event']) ?></td>
            <td><?= Sec::e($s['severity']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
