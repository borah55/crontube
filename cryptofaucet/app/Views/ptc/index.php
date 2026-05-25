<?php
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">PTC Ads</h2>
<?php if (empty($ads)): ?>
  <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow text-center text-slate-500">No active ads right now. Check back soon!</div>
<?php else: ?>
  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($ads as $a): ?>
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 flex flex-col">
        <?php if (!empty($a['image_url'])): ?>
          <img src="<?= Sec::e($a['image_url']) ?>" alt="" class="rounded mb-3 max-h-32 object-cover w-full">
        <?php endif; ?>
        <div class="font-bold mb-1"><?= Sec::e($a['title']) ?></div>
        <div class="text-sm text-slate-500 dark:text-slate-400 mb-3 flex-1"><?= Sec::e($a['description'] ?? '') ?></div>
        <div class="flex justify-between items-center mt-2">
          <div>
            <div class="font-mono font-bold text-emerald-600"><?= Helpers::formatCrypto((float)$a['reward']) ?> <?= Sec::e($a['coin_code']) ?></div>
            <div class="text-xs text-slate-500"><?= (int)$a['duration'] ?>s</div>
          </div>
          <?php $disabled = (int)$a['daily_limit'] > 0 && (int)$a['today_views'] >= (int)$a['daily_limit']; ?>
          <a href="/ptc/view/<?= (int)$a['id'] ?>"
             class="<?= $disabled ? 'bg-slate-300 cursor-not-allowed pointer-events-none' : 'bg-indigo-600 hover:bg-indigo-700' ?> text-white font-semibold px-3 py-2 rounded">
            <?= $disabled ? 'Limit reached' : 'View' ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
