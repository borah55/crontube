<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Setting;

final class SettingsController extends Controller
{
    /** Keys are presented in this order; only these can be saved through the form. */
    private const ALLOWED = [
        'site_name','site_url','site_description','default_coin_code',
        'faucet_cooldown_seconds','faucet_daily_limit','referral_percent',
        'hilo_house_edge_percent','withdraw_max_per_ip_per_day','withdraw_min_account_age_minutes',
        'recaptcha_site_key','recaptcha_secret','faucetpay_api_key',
        'maintenance_mode','maintenance_message',
        'proxy_check_enabled','vpn_block_enabled','proxycheck_api_key',
        'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from',
        'telegram_bot_token','telegram_chat_id','online_window_minutes',
    ];

    public function index(): void
    {
        $values = [];
        foreach (self::ALLOWED as $k) {
            $values[$k] = (string)Setting::get($k, '');
        }
        $this->render('admin/settings', ['values' => $values, 'allowed' => self::ALLOWED], 'admin');
    }

    public function save(): void
    {
        foreach (self::ALLOWED as $k) {
            $v = $this->input($k);
            if ($v !== null) {
                Setting::set($k, is_string($v) ? trim($v) : (string)$v);
            }
        }
        $this->redirect('/admin/settings', 'Settings saved.');
    }
}
