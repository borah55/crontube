<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Security</h2>

<div class="grid md:grid-cols-2 gap-4">
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4">
    <h3 class="font-semibold mb-3">Blacklist IP</h3>
    <form method="post" action="/admin/security/blacklist" class="flex gap-2 text-sm">
      <?= Csrf::field() ?>
      <input name="ip_address" required placeholder="1.2.3.4" class="flex-1 rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
      <input name="reason" placeholder="reason" class="flex-1 rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
      <button class="bg-rose-600 text-white px-3 py-2 rounded">Ban</button>
    </form>
    <ul class="mt-3 max-h-72 overflow-y-auto text-sm divide-y divide-slate-100 dark:divide-slate-700">
      <?php foreach ($blacklist as $b): ?>
        <li class="flex justify-between py-2">
          <div class="font-mono"><?= Sec::e($b['ip_address']) ?></div>
          <div class="text-slate-500 text-xs flex-1 px-2"><?= Sec::e($b['reason'] ?? '') ?></div>
          <form method="post" action="/admin/security/whitelist" class="inline">
            <?= Csrf::field() ?>
            <input type="hidden" name="ip_address" value="<?= Sec::e($b['ip_address']) ?>">
            <button class="text-xs hover:underline text-emerald-600">Remove</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4">
    <h3 class="font-semibold mb-3">Recent events</h3>
    <ul class="max-h-96 overflow-y-auto text-sm divide-y divide-slate-100 dark:divide-slate-700">
      <?php foreach ($logs as $l): ?>
        <li class="py-2">
          <div class="flex items-center justify-between">
            <div class="font-mono text-xs"><?= Sec::e($l['ip_address'] ?? '-') ?></div>
            <div class="text-xs text-slate-500"><?= Helpers::timeAgo($l['created_at']) ?></div>
          </div>
          <div class="font-bold"><?= Sec::e($l['event']) ?> <span class="text-xs text-slate-500"><?= Sec::e($l['username'] ?? '') ?></span></div>
          <div class="text-xs text-slate-500"><?= Sec::e($l['message']) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
