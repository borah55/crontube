<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Setting;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        $stats = [
            'users'            => (int)$this->db->column('SELECT COUNT(*) FROM users'),
            'users_banned'     => (int)$this->db->column("SELECT COUNT(*) FROM users WHERE status='banned'"),
            'users_today'      => (int)$this->db->column('SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()'),
            'claims_today'     => (int)$this->db->column('SELECT COUNT(*) FROM claims WHERE DATE(claimed_at)=CURDATE()'),
            'claims_total'     => (int)$this->db->column('SELECT COUNT(*) FROM claims'),
            'pending_wd'       => (int)$this->db->column("SELECT COUNT(*) FROM withdrawals WHERE status='pending'"),
            'paid_wd'          => (float)$this->db->column("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='paid'"),
            'paid_wd_count'    => (int)$this->db->column("SELECT COUNT(*) FROM withdrawals WHERE status='paid'"),
            'hilo_volume'      => (float)$this->db->column('SELECT COALESCE(SUM(bet_amount),0) FROM hilo_games'),
            'hilo_pnl'         => (float)$this->db->column('SELECT COALESCE(SUM(bet_amount - payout),0) FROM hilo_games'),
            'ptc_views'        => (int)$this->db->column('SELECT COUNT(*) FROM ptc_views'),
            'maintenance'      => Setting::get('maintenance_mode') === '1',
            'online_users'     => (int)$this->db->column(
                'SELECT COUNT(DISTINCT user_id) FROM transactions WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
                [(int)Setting::get('online_window_minutes', 5)]
            ),
        ];
        $recentSecurity = $this->db->fetchAll(
            'SELECT s.*, u.username FROM security_logs s LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.id DESC LIMIT 15'
        );
        $recentWd = $this->db->fetchAll(
            'SELECT w.*, u.username, c.code AS coin_code FROM withdrawals w
             LEFT JOIN users u ON u.id = w.user_id
             LEFT JOIN coins c ON c.id = w.coin_id
             ORDER BY w.id DESC LIMIT 10'
        );
        $this->render('admin/dashboard', compact('stats', 'recentSecurity', 'recentWd'), 'admin');
    }

    public function toggleMaintenance(): void
    {
        $on = Setting::get('maintenance_mode') === '1';
        Setting::set('maintenance_mode', $on ? '0' : '1');
        $this->redirect('/admin', 'Maintenance ' . ($on ? 'disabled' : 'enabled') . '.');
    }

    /**
     * Streams a SQL dump of all tables to the browser.  This is a minimal
     * mysqldump-equivalent that works on PHP-only shared hosting.
     */
    public function backup(): void
    {
        if (!$this->auth->isAdmin()) {
            $this->abort(403, 'Forbidden');
        }
        $tables = $this->db->fetchAll('SHOW TABLES');
        $key    = array_key_first($tables[0] ?? []) ?? 'Tables_in_db';
        $name   = 'backup-' . date('Y-m-d-His') . '.sql';

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $name . '"');

        echo "-- Crypto Faucet backup " . date('c') . PHP_EOL;
        echo "SET FOREIGN_KEY_CHECKS=0;" . PHP_EOL . PHP_EOL;
        foreach ($tables as $row) {
            $table = $row[$key];
            $create = $this->db->fetch('SHOW CREATE TABLE `' . $table . '`');
            echo "DROP TABLE IF EXISTS `$table`;" . PHP_EOL;
            echo $create['Create Table'] . ";" . PHP_EOL;

            $rows = $this->db->fetchAll('SELECT * FROM `' . $table . '`');
            foreach ($rows as $r) {
                $cols = '`' . implode('`,`', array_keys($r)) . '`';
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : $this->db->pdo()->quote((string)$v),
                    array_values($r)
                );
                echo "INSERT INTO `$table` ($cols) VALUES (" . implode(',', $vals) . ");" . PHP_EOL;
            }
            echo PHP_EOL;
        }
        echo "SET FOREIGN_KEY_CHECKS=1;" . PHP_EOL;
        exit;
    }
}
