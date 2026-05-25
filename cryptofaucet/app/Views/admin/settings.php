<?php
use App\Core\Csrf;
use App\Core\Security as Sec;
$labels = [
    'site_name' => 'Site name',
    'site_url' => 'Site URL',
    'site_description' => 'Site description',
    'default_coin_code' => 'Default coin code',
    'faucet_cooldown_seconds' => 'Faucet cooldown (s)',
    'faucet_daily_limit' => 'Faucet daily limit',
    'referral_percent' => 'Referral %',
    'hilo_house_edge_percent' => 'Hi-Lo house edge %',
    'withdraw_max_per_ip_per_day' => 'Max withdrawals/IP/day',
    'withdraw_min_account_age_minutes' => 'Min account age (min)',
    'recaptcha_site_key' => 'reCAPTCHA site key',
    'recaptcha_secret' => 'reCAPTCHA secret',
    'faucetpay_api_key' => 'FaucetPay API key',
    'maintenance_mode' => 'Maintenance (0/1)',
    'maintenance_message' => 'Maintenance message',
    'proxy_check_enabled' => 'Proxy check enabled (0/1)',
    'vpn_block_enabled' => 'VPN block enabled (0/1)',
    'proxycheck_api_key' => 'proxycheck.io API key',
    'smtp_host' => 'SMTP host',
    'smtp_port' => 'SMTP port',
    'smtp_user' => 'SMTP user',
    'smtp_pass' => 'SMTP pass',
    'smtp_from' => 'SMTP from',
    'telegram_bot_token' => 'Telegram bot token',
    'telegram_chat_id' => 'Telegram chat id',
    'online_window_minutes' => 'Online window (min)',
];
?>
<h2 class="text-2xl font-bold mb-4">Settings</h2>
<form method="post" action="/admin/settings" class="bg-white dark:bg-slate-800 rounded-xl shadow p-5 grid md:grid-cols-2 gap-4">
  <?= Csrf::field() ?>
  <?php foreach ($allowed as $k): ?>
    <label class="block">
      <span class="text-sm"><?= Sec::e($labels[$k] ?? $k) ?></span>
      <?php if (str_contains($k, 'message')): ?>
        <textarea name="<?= Sec::e($k) ?>" rows="2"
          class="mt-1 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2"><?= Sec::e($values[$k] ?? '') ?></textarea>
      <?php else: ?>
        <input name="<?= Sec::e($k) ?>" value="<?= Sec::e($values[$k] ?? '') ?>"
               type="<?= str_contains($k, 'pass') || str_contains($k, 'secret') || str_contains($k, 'api') ? 'password' : 'text' ?>"
               class="mt-1 w-full rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 font-mono">
      <?php endif; ?>
    </label>
  <?php endforeach; ?>
  <div class="md:col-span-2">
    <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg">Save settings</button>
  </div>
</form>
