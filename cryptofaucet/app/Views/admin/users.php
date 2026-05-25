<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Users (<?= number_format($total) ?>)</h2>
<form method="get" class="mb-4 flex gap-2">
  <input name="q" value="<?= Sec::e($q) ?>" placeholder="username, email, or id"
         class="flex-1 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2">
  <button class="bg-indigo-600 text-white px-4 py-2 rounded">Search</button>
</form>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 text-left">
      <tr>
        <th class="p-3">ID</th><th>Username</th><th>Email</th><th>Status</th>
        <th>Balance</th><th>Earned</th><th>Withdrawn</th><th>Joined</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-slate-100 dark:border-slate-700">
          <td class="p-3 font-mono">#<?= (int)$r['id'] ?></td>
          <td><?= Sec::e($r['username']) ?> <?php if ($r['role']==='admin'): ?><span class="text-amber-600 text-xs">[admin]</span><?php endif; ?></td>
          <td class="text-xs"><?= Sec::e($r['email']) ?></td>
          <td><span class="px-2 py-0.5 rounded text-xs <?= $r['status']==='active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>"><?= Sec::e($r['status']) ?></span></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['balance']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['total_earned']) ?></td>
          <td class="font-mono"><?= Helpers::formatCrypto((float)$r['total_withdrawn']) ?></td>
          <td class="text-xs"><?= Sec::e($r['created_at']) ?></td>
          <td class="space-x-1 whitespace-nowrap">
            <?php if ($r['status'] === 'banned'): ?>
              <form method="post" action="/admin/users/<?= (int)$r['id'] ?>/unban" class="inline">
                <?= Csrf::field() ?>
                <button class="text-xs bg-emerald-600 text-white px-2 py-1 rounded">Unban</button>
              </form>
            <?php else: ?>
              <form method="post" action="/admin/users/<?= (int)$r['id'] ?>/ban" class="inline">
                <?= Csrf::field() ?>
                <input name="reason" placeholder="reason" class="w-24 text-xs rounded border px-1 py-0.5">
                <button class="text-xs bg-rose-600 text-white px-2 py-1 rounded">Ban</button>
              </form>
            <?php endif; ?>
            <form method="post" action="/admin/users/<?= (int)$r['id'] ?>/credit" class="inline">
              <?= Csrf::field() ?>
              <input name="amount" placeholder="±amount" class="w-20 text-xs rounded border px-1 py-0.5">
              <button class="text-xs bg-indigo-600 text-white px-2 py-1 rounded">Adjust</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
  <div class="mt-3 flex justify-center gap-1 text-sm">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?page=<?= $i ?>&q=<?= urlencode($q) ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-700' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
