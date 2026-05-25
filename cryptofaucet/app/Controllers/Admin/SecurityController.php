<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Security;

final class SecurityController extends Controller
{
    public function index(): void
    {
        $logs = $this->db->fetchAll(
            'SELECT s.*, u.username FROM security_logs s
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.id DESC LIMIT 200'
        );
        $blacklist = $this->db->fetchAll('SELECT * FROM ip_blacklist ORDER BY id DESC LIMIT 200');
        $this->render('admin/security', compact('logs','blacklist'), 'admin');
    }

    public function blacklist(): void
    {
        $ip = trim((string)$this->input('ip_address', ''));
        $reason = trim((string)$this->input('reason', 'Manual ban'));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->redirect('/admin/security', 'Invalid IP.', 'error');
        }
        $this->db->run(
            'INSERT IGNORE INTO ip_blacklist (ip_address, reason, created_at) VALUES (?, ?, ?)',
            [$ip, $reason, date('Y-m-d H:i:s')]
        );
        Security::logEvent($this->auth->id(), 'ip_blacklist', "Banned $ip: $reason", 'warning');
        $this->redirect('/admin/security', 'IP banned.');
    }

    public function whitelist(): void
    {
        $ip = trim((string)$this->input('ip_address', ''));
        $this->db->run('DELETE FROM ip_blacklist WHERE ip_address = ?', [$ip]);
        Security::logEvent($this->auth->id(), 'ip_whitelist', "Removed $ip from blacklist");
        $this->redirect('/admin/security', 'IP removed.');
    }
}
