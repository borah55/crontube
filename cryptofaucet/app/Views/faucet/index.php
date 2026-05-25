<?php
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Security as Sec;
?>
<h2 class="text-2xl font-bold mb-4">Faucet</h2>
<div class="grid md:grid-cols-3 gap-4">
  <div class="md:col-span-2">
    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl shadow-xl p-8 text-center">
      <div class="text-sm opacity-80 mb-2">Random reward between</div>
      <div class="text-3xl font-bold mb-4">
        <?= Helpers::formatCrypto((float)($coin['min_reward'] ?? 0)) ?> – <?= Helpers::formatCrypto((float)($coin['max_reward'] ?? 0)) ?>
        <span class="text-xl"><?= Sec::e($coin['code'] ?? '') ?></span>
      </div>

      <form id="cf-faucet-form" onsubmit="event.preventDefault(); CF.faucet(this, document.getElementById('cf-claim-btn'), document.getElementById('cf-timer'))" class="space-y-4">
        <?= Csrf::field() ?>
        <select name="coin_id" class="text-slate-800 rounded-lg p-2">
          <?php foreach ($coins as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($coin && (int)$c['id'] === (int)$coin['id']) ? 'selected' : '' ?>>
              <?= Sec::e($c['code']) ?> – <?= Sec::e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <?php if ($recaptchaSite): ?>
          <div class="g-recaptcha mx-auto" data-sitekey="<?= Sec::e($recaptchaSite) ?>"></div>
          <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>

        <div>
          <button id="cf-claim-btn" type="submit"
                  class="cf-claim-btn bg-white text-indigo-700 hover:bg-slate-100 font-extrabold text-2xl px-10 py-5 rounded-2xl shadow-lg <?= $secondsLeft > 0 ? 'opacity-60 pointer-events-none' : '' ?>">
            Claim
          </button>
        </div>
        <div class="text-sm">
          Next claim in: <span id="cf-timer" class="font-mono"><?= $secondsLeft > 0 ? sprintf('%02d:%02d', floor($secondsLeft/60), $secondsLeft%60) : 'Ready!' ?></span>
        </div>
      </form>
    </div>
  </div>
  <div class="space-y-3">
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow">
      <div class="text-sm text-slate-500 dark:text-slate-400">Today claimed</div>
      <div class="font-bold text-2xl"><?= (int)$todayCount ?> / <?= (int)$dailyLimit ?></div>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow">
      <div class="text-sm text-slate-500 dark:text-slate-400">Cooldown</div>
      <div class="font-bold text-2xl"><?= (int)$cooldown / 60 ?> min</div>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow text-sm space-y-1">
      <div><strong>Tips:</strong></div>
      <div>&bull; No VPN/proxy</div>
      <div>&bull; One account per IP/device</div>
      <div>&bull; Bot/auto-claim is detected and banned</div>
    </div>
  </div>
</div>
<?php if ($secondsLeft > 0): ?>
<script>CF.startCountdown(document.getElementById('cf-timer'), <?= (int)$secondsLeft ?>, document.getElementById('cf-claim-btn'));</script>
<?php endif; ?>
