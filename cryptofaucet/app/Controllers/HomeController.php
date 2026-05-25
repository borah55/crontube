<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Models\Coin;

final class HomeController extends Controller
{
    public function index(): void
    {
        $stats = [
            'users'      => (int)$this->db->column('SELECT COUNT(*) FROM users'),
            'paid_count' => (int)$this->db->column("SELECT COUNT(*) FROM withdrawals WHERE status='paid'"),
            'paid_amount'=> (float)$this->db->column("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='paid'"),
            'claims'     => (int)$this->db->column('SELECT COUNT(*) FROM claims'),
        ];
        $announcements = $this->db->fetchAll(
            'SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 3'
        );
        $coins = Coin::active();

        $this->render('home/index', compact('stats', 'announcements', 'coins'));
    }
}
