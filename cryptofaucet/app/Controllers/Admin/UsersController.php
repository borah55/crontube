<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Security;
use App\Models\User;

final class UsersController extends Controller
{
    public function index(): void
    {
        $q = trim((string)$this->input('q', ''));
        $page = max(1, (int)$this->input('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (username LIKE ? OR email LIKE ? OR id = ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = ctype_digit($q) ? (int)$q : 0;
        }
        $rows = $this->db->fetchAll(
            "SELECT id, username, email, role, status, balance, total_earned, total_withdrawn, created_at
             FROM users WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $total = (int)$this->db->column("SELECT COUNT(*) FROM users WHERE $where", $params);
        $pages = max(1, (int)ceil($total / $perPage));
        $this->render('admin/users', compact('rows','q','page','pages','total'), 'admin');
    }

    public function ban(string $id): void
    {
        $reason = trim((string)$this->input('reason', 'Banned by admin'));
        $this->db->update('users', ['status' => 'banned'], 'id = :_id', ['_id' => (int)$id]);
        Security::logEvent($this->auth->id(), 'admin_ban', "Banned user $id: $reason", 'warning');
        $this->redirect('/admin/users', 'User banned.');
    }

    public function unban(string $id): void
    {
        $this->db->update('users', ['status' => 'active'], 'id = :_id', ['_id' => (int)$id]);
        Security::logEvent($this->auth->id(), 'admin_unban', "Unbanned user $id");
        $this->redirect('/admin/users', 'User unbanned.');
    }

    public function credit(string $id): void
    {
        $amount = (float)$this->input('amount', 0);
        $type   = $amount >= 0 ? 'admin_credit' : 'admin_debit';
        if ($amount === 0.0) {
            $this->redirect('/admin/users', 'Amount cannot be zero.', 'error');
        }
        $coinId = (int)$this->input('coin_id', 0) ?: null;
        if ($amount > 0) {
            User::credit((int)$id, $type, $amount, $coinId, ['by' => $this->auth->id()]);
        } else {
            User::debit((int)$id, $type, abs($amount), $coinId, ['by' => $this->auth->id()]);
        }
        Security::logEvent($this->auth->id(), 'admin_balance_change', "User $id amount $amount", 'info');
        $this->redirect('/admin/users', 'Balance adjusted.');
    }
}
