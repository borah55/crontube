<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Setting;
use App\Models\Coin;
use App\Models\User;

final class FaucetController extends Controller
{
    public function index(): void
    {
        $u = $this->auth->user();
        $coins = Coin::active();
        $cooldown = (int)Setting::get('faucet_cooldown_seconds', 300);
        $dailyLimit = (int)Setting::get('faucet_daily_limit', 100);

        $coinId = (int)($this->input('coin') ?? ($coins[0]['id'] ?? 0));
        $coin = null;
        foreach ($coins as $c) {
            if ((int)$c['id'] === $coinId) { $coin = $c; break; }
        }
        $coin ??= $coins[0] ?? null;

        $lastClaim = $this->db->fetch(
            'SELECT * FROM claims WHERE user_id = ? AND coin_id = ? ORDER BY id DESC LIMIT 1',
            [$u['id'], $coin['id'] ?? 0]
        );
        $secondsLeft = 0;
        if ($lastClaim) {
            $elapsed = time() - strtotime($lastClaim['claimed_at']);
            $secondsLeft = max(0, $cooldown - $elapsed);
        }
        $todayCount = (int)$this->db->column(
            'SELECT COUNT(*) FROM claims WHERE user_id = ? AND DATE(claimed_at) = CURDATE()',
            [$u['id']]
        );
        $recaptchaSite = (string)Setting::get('recaptcha_site_key', '');

        $this->render('faucet/index', compact(
            'u','coins','coin','cooldown','dailyLimit','secondsLeft','todayCount','recaptchaSite'
        ));
    }

    public function claim(): void
    {
        $u = $this->auth->user();
        $ip = Security::clientIp();
        $coinId = (int)$this->input('coin_id', 0);

        // Per-user/IP burst protection (separate from cooldown which is enforced below).
        if (!RateLimiter::hit('claim:user:' . $u['id'], 30, 60)
            || !RateLimiter::hit('claim:ip:'   . $ip,        60, 60)) {
            $this->json(['ok' => false, 'error' => 'rate_limited'], 429);
            return;
        }

        // Block known-bad IPs.
        if (Security::isIpBlocked($ip)) {
            Security::logEvent((int)$u['id'], 'claim_blocked_ip', 'Blacklist/VPN', 'warning');
            $this->json(['ok' => false, 'error' => 'ip_blocked'], 403);
            return;
        }

        $coin = $coinId ? Coin::find($coinId) : Coin::defaultCoin();
        if (!$coin || (int)$coin['is_active'] !== 1) {
            $this->json(['ok' => false, 'error' => 'invalid_coin'], 400);
            return;
        }

        // reCAPTCHA (skipped if not configured).
        $captcha = (string)$this->input('g-recaptcha-response', '');
        if (!Security::verifyRecaptcha($captcha)) {
            $this->json(['ok' => false, 'error' => 'captcha_failed'], 400);
            return;
        }

        $cooldown = (int)Setting::get('faucet_cooldown_seconds', 300);
        $dailyLimit = (int)Setting::get('faucet_daily_limit', 100);

        // Cooldown check (per coin).
        $last = $this->db->fetch(
            'SELECT claimed_at FROM claims WHERE user_id = ? AND coin_id = ? ORDER BY id DESC LIMIT 1',
            [$u['id'], $coin['id']]
        );
        if ($last) {
            $elapsed = time() - strtotime($last['claimed_at']);
            if ($elapsed < $cooldown) {
                $this->json(['ok' => false, 'error' => 'cooldown', 'seconds_left' => $cooldown - $elapsed], 429);
                return;
            }
        }

        // Daily limit (across all coins).
        $today = (int)$this->db->column(
            'SELECT COUNT(*) FROM claims WHERE user_id = ? AND DATE(claimed_at) = CURDATE()',
            [$u['id']]
        );
        if ($today >= $dailyLimit) {
            $this->json(['ok' => false, 'error' => 'daily_limit', 'limit' => $dailyLimit], 429);
            return;
        }

        // Multi-account protection: same IP+device used by another account today?
        $fp = Security::deviceFingerprint();
        $other = (int)$this->db->column(
            'SELECT COUNT(DISTINCT user_id) FROM claims
             WHERE ip_address = ? AND device_fp = ? AND DATE(claimed_at) = CURDATE() AND user_id <> ?',
            [$ip, $fp, $u['id']]
        );
        if ($other > 0) {
            Security::logEvent((int)$u['id'], 'multi_account', "Same IP/device as $other other accounts today", 'warning');
            $this->json(['ok' => false, 'error' => 'multi_account'], 403);
            return;
        }

        // Random reward.
        $min = (float)$coin['min_reward'];
        $max = max($min, (float)$coin['max_reward']);
        $reward = $min + lcg_value() * ($max - $min);
        $reward = round($reward, 8);

        // Credit + log.
        $balance = User::credit((int)$u['id'], 'faucet', $reward, (int)$coin['id'], ['ip' => $ip]);
        $this->db->insert('claims', [
            'user_id'    => $u['id'],
            'coin_id'    => $coin['id'],
            'amount'     => number_format($reward, 8, '.', ''),
            'ip_address' => $ip,
            'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'device_fp'  => $fp,
            'claimed_at' => date('Y-m-d H:i:s'),
        ]);

        // Referral bonus.
        if (!empty($u['referred_by'])) {
            $percent = (float)Setting::get('referral_percent', 10);
            if ($percent > 0) {
                $refBonus = round($reward * $percent / 100, 8);
                if ($refBonus > 0) {
                    User::credit((int)$u['referred_by'], 'referral', $refBonus, (int)$coin['id'], ['from' => $u['id']]);
                    $this->db->run('UPDATE users SET referral_earnings = referral_earnings + ? WHERE id = ?', [
                        number_format($refBonus, 8, '.', ''), $u['referred_by'],
                    ]);
                }
            }
        }

        $this->json([
            'ok'           => true,
            'amount'       => $reward,
            'coin'         => $coin['code'],
            'balance'      => $balance,
            'next_seconds' => $cooldown,
        ]);
    }
}
