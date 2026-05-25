<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Security;
use App\Core\Setting;
use App\Models\Coin;
use App\Models\Transaction;
use App\Models\User;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $u = $this->auth->user();
        $coin = Coin::defaultCoin();

        $stats = [
            'today_earned'   => Transaction::dailySumForUser((int)$u['id']),
            'total_earned'   => (float)$u['total_earned'],
            'total_withdrawn'=> (float)$u['total_withdrawn'],
            'referrals'      => (int)$this->db->column('SELECT COUNT(*) FROM users WHERE referred_by = ?', [$u['id']]),
            'referral_earn'  => (float)$u['referral_earnings'],
            'last_claim_at'  => $this->db->column('SELECT claimed_at FROM claims WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$u['id']]),
        ];
        $recent = Transaction::forUser((int)$u['id'], 10);
        $announcements = $this->db->fetchAll('SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 3');

        $this->render('dashboard/index', compact('u', 'coin', 'stats', 'recent', 'announcements'));
    }

    public function profile(): void
    {
        $u = $this->auth->user();
        $coins = Coin::active();
        $this->render('dashboard/profile', compact('u', 'coins'));
    }

    public function saveWallet(): void
    {
        $address = trim((string)$this->input('wallet_address', ''));
        if ($address !== '' && !preg_match('/^[A-Za-z0-9_@.\-]{6,190}$/', $address)) {
            $this->redirect('/profile', 'Invalid wallet address format.', 'error');
        }
        $this->db->update('users', ['wallet_address' => $address ?: null], 'id = :_id', ['_id' => $this->auth->id()]);
        Security::logEvent($this->auth->id(), 'wallet_change', 'Wallet address updated');
        $this->redirect('/profile', 'Wallet address saved.');
    }

    public function changePassword(): void
    {
        $current = (string)$this->input('current_password', '');
        $new     = (string)$this->input('new_password', '');
        $confirm = (string)$this->input('confirm_password', '');

        $u = $this->auth->user();
        if (!password_verify($current, $u['password_hash'])) {
            $this->redirect('/profile', 'Current password is incorrect.', 'error');
        }
        if (strlen($new) < 8 || $new !== $confirm) {
            $this->redirect('/profile', 'New password too short or does not match.', 'error');
        }
        $this->db->update('users', [
            'password_hash' => password_hash($new, PASSWORD_BCRYPT),
        ], 'id = :_id', ['_id' => $u['id']]);
        Security::logEvent((int)$u['id'], 'password_change', 'User changed password');
        $this->redirect('/profile', 'Password updated.');
    }

    public function transactions(): void
    {
        $page  = max(1, (int)$this->input('page', 1));
        $perPage = 25;
        $u = $this->auth->user();
        $rows  = Transaction::forUser((int)$u['id'], $perPage, ($page - 1) * $perPage);
        $total = (int)$this->db->column('SELECT COUNT(*) FROM transactions WHERE user_id = ?', [$u['id']]);
        $pages = max(1, (int)ceil($total / $perPage));
        $this->render('dashboard/transactions', compact('rows', 'page', 'pages'));
    }

    public function referrals(): void
    {
        $u = $this->auth->user();
        $referrals = $this->db->fetchAll(
            'SELECT id, username, total_earned, created_at FROM users WHERE referred_by = ? ORDER BY id DESC',
            [$u['id']]
        );
        $referralUrl = Helpers::url('/register?ref=' . $u['referral_code']);
        $percent = (int)Setting::get('referral_percent', 10);
        $this->render('dashboard/referrals', compact('u', 'referrals', 'referralUrl', 'percent'));
    }

    public function leaderboard(): void
    {
        $top = $this->db->fetchAll(
            'SELECT u.username, COUNT(r.id) AS refs, SUM(r.total_earned) AS earned
             FROM users u LEFT JOIN users r ON r.referred_by = u.id
             GROUP BY u.id HAVING refs > 0
             ORDER BY refs DESC, earned DESC LIMIT 25'
        );
        $this->render('dashboard/leaderboard', compact('top'));
    }

    public function dailyBonus(): void
    {
        $u = $this->auth->user();
        $today = date('Y-m-d');
        $exists = $this->db->column(
            'SELECT 1 FROM daily_bonuses WHERE user_id = ? AND claimed_on = ?',
            [$u['id'], $today]
        );
        if ($exists) {
            $this->json(['ok' => false, 'error' => 'already_claimed']);
            return;
        }
        // Determine streak (consecutive prior day).
        $prior = $this->db->fetch(
            'SELECT streak FROM daily_bonuses WHERE user_id = ? AND claimed_on = DATE_SUB(?, INTERVAL 1 DAY)',
            [$u['id'], $today]
        );
        $streak = $prior ? min(7, (int)$prior['streak'] + 1) : 1;

        $coin = Coin::defaultCoin();
        $base = (float)$coin['min_reward'] * 5;
        $amount = $base * (1 + ($streak - 1) * 0.2);

        $this->db->insert('daily_bonuses', [
            'user_id'    => $u['id'],
            'streak'     => $streak,
            'amount'     => number_format($amount, 8, '.', ''),
            'coin_id'    => $coin['id'],
            'claimed_on' => $today,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $balance = User::credit((int)$u['id'], 'bonus', $amount, (int)$coin['id'], ['streak' => $streak]);
        $this->json(['ok' => true, 'amount' => $amount, 'streak' => $streak, 'balance' => $balance]);
    }
}
