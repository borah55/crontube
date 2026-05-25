<?php use App\Core\Security as Sec; ?>
<div class="text-center py-10">
  <div class="text-6xl font-extrabold text-indigo-500"><?= (int)$status ?></div>
  <p class="mt-3 text-slate-600 dark:text-slate-300"><?= Sec::e($message) ?></p>
  <a href="/" class="mt-5 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Go home</a>
</div>
