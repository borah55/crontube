<?php
use App\Core\Csrf;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Announcements</h2>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 mb-4">
  <h3 class="font-semibold mb-3">New announcement</h3>
  <form method="post" action="/admin/announcements/save" class="space-y-3 text-sm">
    <?= Csrf::field() ?>
    <input name="title" placeholder="Title" required class="w-full rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    <textarea name="body" placeholder="Body" required rows="3" class="w-full rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600"></textarea>
    <div class="flex gap-3 items-center">
      <select name="level" class="rounded border px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
        <option value="info">Info</option><option value="success">Success</option>
        <option value="warning">Warning</option><option value="danger">Danger</option>
      </select>
      <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
      <button class="ml-auto bg-indigo-600 text-white px-3 py-2 rounded">Save</button>
    </div>
  </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr><th class="p-3">Title</th><th>Level</th><th>Active</th><th>Created</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3"><?= Sec::e($r['title']) ?></td>
          <td><?= Sec::e($r['level']) ?></td>
          <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
          <td class="text-xs"><?= Sec::e($r['created_at']) ?></td>
          <td>
            <form method="post" action="/admin/announcements/<?= (int)$r['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete?')">
              <?= Csrf::field() ?>
              <button class="text-xs bg-rose-600 text-white px-2 py-1 rounded">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
