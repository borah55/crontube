<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Models\User;

final class ShortlinkController extends Controller
{
    public function index(): void
    {
        $u = $this->auth->user();
        $links = $this->db->fetchAll(
            "SELECT s.*, c.code AS coin_code,
                    (SELECT COUNT(*) FROM shortlink_completions sc
                       WHERE sc.shortlink_id = s.id AND sc.user_id = ?
                         AND sc.status = 'redeemed'
                         AND DATE(sc.redeemed_at) = CURDATE()) AS today_done
             FROM shortlinks s
             LEFT JOIN coins c ON c.id = s.coin_id
             WHERE s.is_active = 1 ORDER BY s.id DESC",
            [$u['id']]
        );
        $this->render('shortlink/index', compact('links'));
    }

    public function start(string $id): void
    {
        $u = $this->auth->user();
        $sl = $this->db->fetch('SELECT * FROM shortlinks WHERE id = ? AND is_active = 1', [(int)$id]);
        if (!$sl) {
            $this->abort(404, 'Shortlink not found');
        }
        if ((int)$sl['daily_limit'] > 0) {
            $today = (int)$this->db->column(
                "SELECT COUNT(*) FROM shortlink_completions
                 WHERE user_id = ? AND shortlink_id = ? AND DATE(created_at) = CURDATE()",
                [$u['id'], $sl['id']]
            );
            if ($today >= (int)$sl['daily_limit']) {
                $this->redirect('/shortlinks', 'Daily limit reached for this link.', 'error');
            }
        }
        $token = bin2hex(random_bytes(32));
        $this->db->insert('shortlink_completions', [
            'user_id'      => $u['id'],
            'shortlink_id' => $sl['id'],
            'token'        => $token,
            'status'       => 'issued',
            'ip_address'   => Security::clientIp(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        $returnUrl = \App\Core\Helpers::url('/shortlinks/return/' . $token);
        // Redirect to the target URL.  Admins compose target URL with the
        // {RETURN_URL} placeholder so the shortener can bring the user back.
        $target = str_replace('{RETURN_URL}', urlencode($returnUrl), $sl['target_url']);
        header('Location: ' . $target);
        exit;
    }

    public function complete(string $token): void
    {
        $u = $this->auth->user();
        if (!RateLimiter::hit('sl:' . $u['id'], 30, 60)) {
            $this->redirect('/shortlinks', 'Too many requests. Slow down.', 'error');
        }
        $row = $this->db->fetch(
            'SELECT * FROM shortlink_completions WHERE token = ? AND user_id = ?',
            [$token, $u['id']]
        );
        if (!$row || $row['status'] !== 'issued') {
            $this->redirect('/shortlinks', 'Invalid or already-used token.', 'error');
        }
        // Require at least 10 seconds between issue and return to prevent instant skips.
        if (time() - strtotime($row['created_at']) < 10) {
            $this->redirect('/shortlinks', 'Please complete the shortlink first.', 'error');
        }
        $sl = $this->db->fetch('SELECT * FROM shortlinks WHERE id = ?', [$row['shortlink_id']]);
        if (!$sl) {
            $this->redirect('/shortlinks', 'Shortlink no longer exists.', 'error');
        }
        $this->db->run(
            "UPDATE shortlink_completions SET status='redeemed', redeemed_at=NOW() WHERE id = ?",
            [$row['id']]
        );
        User::credit((int)$u['id'], 'shortlink', (float)$sl['reward'], (int)$sl['coin_id'], ['shortlink_id' => $sl['id']]);
        $this->redirect('/shortlinks', "Reward of {$sl['reward']} credited.");
    }
}
