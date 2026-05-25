<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Shortlinks</h2>
<?php if (empty($links)): ?>
  <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow text-slate-500 text-center">No active shortlinks. Check back soon!</div>
<?php else: ?>
  <div class="grid md:grid-cols-2 gap-3">
    <?php foreach ($links as $l): ?>
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 flex justify-between items-center">
        <div>
          <div class="font-bold"><?= Sec::e($l['name']) ?></div>
          <div class="text-xs text-slate-500"><?= (int)$l['today_done'] ?> / <?= (int)$l['daily_limit'] ?> today</div>
        </div>
        <div class="text-right">
          <div class="font-mono text-emerald-600 font-bold"><?= Helpers::formatCrypto((float)$l['reward']) ?> <?= Sec::e($l['coin_code']) ?></div>
          <a href="/shortlinks/start/<?= (int)$l['id'] ?>" target="_blank" rel="nofollow noopener"
             class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded mt-1 inline-block">
             Start
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
