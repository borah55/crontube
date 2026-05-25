<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\FaucetPay;
use App\Core\Security;
use App\Models\User;

final class WithdrawalsController extends Controller
{
    public function index(): void
    {
        $status = (string)$this->input('status', 'pending');
        $where = $status !== '' ? 'WHERE w.status = ?' : '';
        $params = $status !== '' ? [$status] : [];
        $rows = $this->db->fetchAll(
            "SELECT w.*, u.username, c.code AS coin_code, c.faucetpay_token
             FROM withdrawals w
             LEFT JOIN users u ON u.id = w.user_id
             LEFT JOIN coins c ON c.id = w.coin_id
             $where
             ORDER BY w.id DESC LIMIT 100",
            $params
        );
        $this->render('admin/withdrawals', compact('rows','status'), 'admin');
    }

    public function approve(string $id): void
    {
        $w = $this->db->fetch(
            'SELECT w.*, c.faucetpay_token FROM withdrawals w
             LEFT JOIN coins c ON c.id = w.coin_id WHERE w.id = ?',
            [(int)$id]
        );
        if (!$w || $w['status'] !== 'pending') {
            $this->redirect('/admin/withdrawals', 'Withdrawal not pending.', 'error');
        }
        $this->db->update('withdrawals', ['status' => 'processing'], 'id = :_id', ['_id' => $w['id']]);
        $fp = new FaucetPay();
        if (!$fp->isConfigured()) {
            // Just mark as paid manually if no API.
            $this->db->update('withdrawals', [
                'status'       => 'paid',
                'processed_at' => date('Y-m-d H:i:s'),
                'faucetpay_tx' => 'manual',
            ], 'id = :_id', ['_id' => $w['id']]);
            $this->db->run('UPDATE users SET total_withdrawn = total_withdrawn + ? WHERE id = ?', [$w['amount'], $w['user_id']]);
            $this->redirect('/admin/withdrawals', 'Marked paid (manual; no FaucetPay key).');
        }
        $resp = $fp->send($w['wallet_address'], (float)$w['net_amount'], (string)$w['faucetpay_token'], $w['ip_address']);
        $okStatus = (int)($resp['status'] ?? 0);
        if ($okStatus === 200) {
            $payoutId = (string)($resp['payout_id'] ?? '');
            $this->db->update('withdrawals', [
                'status'       => 'paid',
                'processed_at' => date('Y-m-d H:i:s'),
                'faucetpay_tx' => $payoutId,
                'payout_id'    => $payoutId,
            ], 'id = :_id', ['_id' => $w['id']]);
            $this->db->run('UPDATE users SET total_withdrawn = total_withdrawn + ? WHERE id = ?', [$w['amount'], $w['user_id']]);
            Security::logEvent($this->auth->id(), 'withdraw_paid', "Withdrawal {$w['id']} paid via FaucetPay");
            $this->redirect('/admin/withdrawals', 'Withdrawal sent.');
        }
        // Failure: refund and mark failed.
        $this->db->update('withdrawals', [
            'status'        => 'failed',
            'error_message' => mb_substr((string)($resp['message'] ?? 'unknown'), 0, 255),
            'processed_at'  => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => $w['id']]);
        User::credit((int)$w['user_id'], 'admin_credit', (float)$w['amount'], (int)$w['coin_id'], ['refund_for_withdrawal' => $w['id']]);
        Security::logEvent($this->auth->id(), 'withdraw_failed', "Withdrawal {$w['id']}: " . ($resp['message'] ?? ''), 'warning');
        $this->redirect('/admin/withdrawals', 'FaucetPay error: ' . ($resp['message'] ?? 'unknown') . '. User refunded.', 'error');
    }

    public function reject(string $id): void
    {
        $w = $this->db->fetch('SELECT * FROM withdrawals WHERE id = ?', [(int)$id]);
        if (!$w || $w['status'] !== 'pending') {
            $this->redirect('/admin/withdrawals', 'Withdrawal not pending.', 'error');
        }
        $this->db->update('withdrawals', [
            'status' => 'cancelled',
            'processed_at' => date('Y-m-d H:i:s'),
            'error_message' => 'Cancelled by admin',
        ], 'id = :_id', ['_id' => $w['id']]);
        // Refund.
        User::credit((int)$w['user_id'], 'admin_credit', (float)$w['amount'], (int)$w['coin_id'], ['refund_for_withdrawal' => $w['id']]);
        Security::logEvent($this->auth->id(), 'withdraw_cancelled', "Withdrawal {$w['id']} cancelled");
        $this->redirect('/admin/withdrawals', 'Withdrawal cancelled and refunded.');
    }
}
