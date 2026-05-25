<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<section class="bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-700 text-white rounded-2xl shadow-xl p-8 md:p-12 mb-8">
  <h1 class="text-3xl md:text-5xl font-extrabold mb-3"><?= Sec::e($siteName) ?></h1>
  <p class="text-lg opacity-90 mb-6 max-w-2xl">
    Earn free crypto every 5 minutes through faucet claims, Hi-Lo, PTC ads, shortlinks &amp; tasks.
    Withdraw instantly through FaucetPay.
  </p>
  <div class="flex gap-3 flex-wrap">
    <a href="/register" class="bg-white text-indigo-700 hover:bg-slate-100 font-bold px-6 py-3 rounded-xl shadow">Start earning</a>
    <a href="/login" class="bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3 rounded-xl">Sign in</a>
  </div>
</section>

<section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <?php $cards = [
      ['Users',          number_format($stats['users'])],
      ['Total claims',   number_format($stats['claims'])],
      ['Withdrawals paid', number_format($stats['paid_count'])],
      ['Crypto paid',    Helpers::formatCrypto($stats['paid_amount'])],
  ]; foreach ($cards as [$label, $val]): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow text-center">
      <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-300"><?= Sec::e((string)$val) ?></div>
      <div class="text-sm text-slate-500 dark:text-slate-400"><?= Sec::e($label) ?></div>
    </div>
  <?php endforeach; ?>
</section>

<section class="grid md:grid-cols-3 gap-4 mb-8">
  <?php $features = [
      ['Faucet', 'Claim free crypto every 5 minutes. Random reward, multi-coin support.'],
      ['Hi-Lo', 'Provably-fair 1.97x payout game. Bet, predict, win.'],
      ['PTC &amp; Shortlinks', 'View ads or complete shortlinks for instant rewards.'],
  ]; foreach ($features as [$t, $d]): ?>
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow">
      <h3 class="font-bold text-lg mb-1"><?= $t ?></h3>
      <p class="text-slate-500 dark:text-slate-400 text-sm"><?= $d ?></p>
    </div>
  <?php endforeach; ?>
</section>

<?php if (!empty($announcements)): ?>
<section class="space-y-3">
  <?php foreach ($announcements as $a): ?>
    <div class="rounded-lg p-4 border-l-4 border-indigo-500 bg-white dark:bg-slate-800 shadow">
      <div class="font-bold"><?= Sec::e($a['title']) ?></div>
      <div class="text-sm text-slate-600 dark:text-slate-300"><?= nl2br(Sec::e($a['body'])) ?></div>
    </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (!empty($coins)): ?>
<section class="mt-8">
  <h3 class="font-bold mb-3">Supported coins</h3>
  <div class="flex flex-wrap gap-3">
    <?php foreach ($coins as $c): ?>
      <span class="px-3 py-2 rounded-lg bg-white dark:bg-slate-800 shadow border border-slate-200 dark:border-slate-700">
        <strong><?= Sec::e($c['code']) ?></strong>
        <span class="text-slate-500 dark:text-slate-400 text-sm">– <?= Sec::e($c['name']) ?></span>
      </span>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
