<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FaucetPay;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Setting;
use App\Models\Coin;
use App\Models\User;

final class WithdrawController extends Controller
{
    public function index(): void
    {
        $u = $this->auth->user();
        $coins = Coin::active();
        $history = $this->db->fetchAll(
            'SELECT w.*, c.code AS coin_code FROM withdrawals w
             LEFT JOIN coins c ON c.id = w.coin_id
             WHERE w.user_id = ? ORDER BY w.id DESC LIMIT 25',
            [$u['id']]
        );
        $faucetpayStatus = (new FaucetPay())->isConfigured() ? 'configured' : 'not_configured';
        $maxPerIp = (int)Setting::get('withdraw_max_per_ip_per_day', 2);

        $this->render('withdraw/index', compact('u','coins','history','faucetpayStatus','maxPerIp'));
    }

    public function submit(): void
    {
        $u = $this->auth->user();
        $ip = Security::clientIp();

        if (!RateLimiter::hit('withdraw:user:' . $u['id'], 5, 600)
            || !RateLimiter::hit('withdraw:ip:'   . $ip,    10, 600)) {
            $this->redirect('/withdraw', 'Too many withdrawal attempts. Slow down.', 'error');
        }

        $coinId  = (int)$this->input('coin_id', 0);
        $address = trim((string)$this->input('wallet_address', ''));
        $amount  = (float)$this->input('amount', 0);

        $coin = Coin::find($coinId);
        if (!$coin || (int)$coin['is_active'] !== 1) {
            $this->redirect('/withdraw', 'Invalid coin.', 'error');
        }
        if ($address === '' || !preg_match('/^[A-Za-z0-9_@.\-]{6,190}$/', $address)) {
            $this->redirect('/withdraw', 'Invalid wallet address.', 'error');
        }
        if ($amount < (float)$coin['min_withdraw']) {
            $this->redirect('/withdraw', "Minimum withdraw is {$coin['min_withdraw']} {$coin['code']}.", 'error');
        }
        if ((float)$u['balance'] < $amount) {
            $this->redirect('/withdraw', 'Insufficient balance.', 'error');
        }

        // Account age guard.
        $minAge = (int)Setting::get('withdraw_min_account_age_minutes', 30);
        if ($minAge > 0 && (time() - strtotime($u['created_at'])) < $minAge * 60) {
            $this->redirect('/withdraw', "Account must be at least $minAge minutes old.", 'error');
        }

        // Per-IP-per-day cap.
        $perIp = (int)Setting::get('withdraw_max_per_ip_per_day', 2);
        $todayIp = (int)$this->db->column(
            'SELECT COUNT(*) FROM withdrawals WHERE ip_address = ? AND DATE(created_at) = CURDATE() AND status IN (?,?,?)',
            [$ip, 'pending', 'processing', 'paid']
        );
        if ($todayIp >= $perIp) {
            Security::logEvent((int)$u['id'], 'withdraw_blocked', 'Per-IP daily limit', 'warning');
            $this->redirect('/withdraw', "Maximum $perIp withdrawals per day from this IP.", 'error');
        }

        if (Security::isIpBlocked($ip)) {
            $this->redirect('/withdraw', 'Withdrawals not allowed from this network.', 'error');
        }

        // Calculate fee.
        $feeFlat = (float)$coin['withdraw_fee'];
        $feePct  = (float)$coin['withdraw_fee_percent'];
        $fee = round($feeFlat + ($amount * $feePct / 100), 8);
        $net = round($amount - $fee, 8);
        if ($net <= 0) {
            $this->redirect('/withdraw', 'Amount too small after fees.', 'error');
        }

        // Verify address with FaucetPay (if API configured).
        $fp = new FaucetPay();
        if ($fp->isConfigured()) {
            $check = $fp->checkAddress($address, $coin['code']);
            $status = (int)($check['status'] ?? 0);
            if ($status !== 200) {
                $this->redirect('/withdraw', 'FaucetPay rejected the address: ' . ($check['message'] ?? 'unknown'), 'error');
            }
        }

        // Reserve funds atomically.
        $newBalance = User::debit((int)$u['id'], 'withdrawal', $amount, (int)$coin['id'], [
            'address' => $address, 'fee' => $fee, 'net' => $net,
        ]);
        if ($newBalance === false) {
            $this->redirect('/withdraw', 'Insufficient balance.', 'error');
        }

        $this->db->insert('withdrawals', [
            'user_id'        => $u['id'],
            'coin_id'        => $coin['id'],
            'amount'         => number_format($amount, 8, '.', ''),
            'fee'            => number_format($fee, 8, '.', ''),
            'net_amount'     => number_format($net, 8, '.', ''),
            'wallet_address' => $address,
            'status'         => 'pending',
            'ip_address'     => $ip,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->redirect('/withdraw', 'Withdrawal request submitted. It will be processed shortly.');
    }
}
