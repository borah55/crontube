<?php
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-2"><?= Sec::e($ad['title']) ?></h2>
<div class="text-sm text-slate-500 mb-3"><?= Sec::e($ad['description'] ?? '') ?></div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 mb-4 flex items-center justify-between">
  <div class="text-sm">
    Wait <span id="ptc-timer" class="font-bold text-indigo-600"><?= (int)$ad['duration'] ?></span>s,
    then click claim.
  </div>
  <button id="ptc-claim" disabled class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-4 py-2 rounded">
    Claim reward
  </button>
</div>

<iframe src="<?= Sec::e($ad['target_url']) ?>" class="w-full h-[60vh] rounded-xl border border-slate-200 dark:border-slate-700"></iframe>

<script>CF.ptc(document.getElementById('ptc-timer'), document.getElementById('ptc-claim'), <?= (int)$ad['id'] ?>, <?= (int)$ad['duration'] ?>, <?= json_encode($token) ?>);</script>
