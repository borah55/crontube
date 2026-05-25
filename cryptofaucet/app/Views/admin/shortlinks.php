<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Shortlinks</h2>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 mb-4">
  <h3 class="font-semibold mb-3">New shortlink</h3>
  <p class="text-xs text-slate-500 mb-2">Use <code>{RETURN_URL}</code> in your target URL where the shortener should redirect users back, e.g. <code>https://shortener.example/?dest={RETURN_URL}</code>.</p>
  <form method="post" action="/admin/shortlinks/save" class="grid md:grid-cols-3 gap-3 text-sm">
    <?= Csrf::field() ?>
    <input name="name" placeholder="Name" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="target_url" placeholder="https://...?dest={RETURN_URL}" required class="md:col-span-2 rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <select name="coin_id" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
      <option value="">Coin</option>
      <?php foreach ($coins as $c): ?><option value="<?= (int)$c['id'] ?>"><?= Sec::e($c['code']) ?></option><?php endforeach; ?>
    </select>
    <input name="reward" placeholder="Reward" required class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <input name="daily_limit" placeholder="Daily limit (5)" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
    <button class="md:col-span-3 bg-indigo-600 text-white py-2 rounded">Save shortlink</button>
  </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">Name</th><th>Target</th><th>Coin</th><th>Reward</th><th>Limit</th><th>Active</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($r['name']) ?></td>
          <td class="text-xs break-all max-w-[16rem]"><?= Sec::e($r['target_url']) ?></td>
          <td><?= Sec::e($r['coin_code']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['reward']) ?></td>
          <td><?= (int)$r['daily_limit'] ?></td>
          <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
          <td>
            <form method="post" action="/admin/shortlinks/<?= (int)$r['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete?')">
              <?= Csrf::field() ?>
              <button class="text-xs bg-rose-600 text-white px-2 py-1 rounded">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
