<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">PTC Ads</h2>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 mb-4">
  <h3 class="font-semibold mb-3">New ad</h3>
  <form method="post" action="/admin/ptc/save" class="grid md:grid-cols-3 gap-3 text-sm">
    <?= Csrf::field() ?>
    <input name="title" placeholder="Title" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="target_url" placeholder="https://..." required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="image_url" placeholder="Image URL (optional)" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="description" placeholder="Description" class="md:col-span-3 rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <select name="coin_id" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
      <option value="">Coin</option>
      <?php foreach ($coins as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= Sec::e($c['code']) ?></option>
      <?php endforeach; ?>
    </select>
    <input name="reward" placeholder="Reward" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="duration" placeholder="Seconds (15)" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="daily_limit" placeholder="Daily limit per user (0=∞)" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="max_views" placeholder="Total max views (0=∞)" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <select name="status" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
      <option value="active">Active</option><option value="paused">Paused</option><option value="ended">Ended</option>
    </select>
    <button class="md:col-span-3 bg-indigo-600 text-white py-2 rounded">Save ad</button>
  </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">Title</th><th>Coin</th><th>Reward</th><th>Duration</th><th>Views</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $a): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($a['title']) ?></td>
          <td><?= Sec::e($a['coin_code']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$a['reward']) ?></td>
          <td><?= (int)$a['duration'] ?>s</td>
          <td><?= (int)$a['views_count'] ?>/<?= (int)$a['max_views'] ?: '∞' ?></td>
          <td><?= Sec::e($a['status']) ?></td>
          <td>
            <form method="post" action="/admin/ptc/<?= (int)$a['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete?')">
              <?= Csrf::field() ?>
              <button class="text-xs bg-rose-600 text-white px-2 py-1 rounded">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
