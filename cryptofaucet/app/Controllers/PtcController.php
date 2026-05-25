<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Models\User;

final class PtcController extends Controller
{
    public function index(): void
    {
        $u = $this->auth->user();
        $ads = $this->db->fetchAll(
            "SELECT a.*, c.code AS coin_code,
                    (SELECT COUNT(*) FROM ptc_views v
                       WHERE v.ad_id = a.id AND v.user_id = ? AND DATE(v.viewed_at) = CURDATE()) AS today_views
             FROM ptc_ads a
             LEFT JOIN coins c ON c.id = a.coin_id
             WHERE a.status = 'active'
               AND (a.max_views = 0 OR a.views_count < a.max_views)
             ORDER BY a.id DESC",
            [$u['id']]
        );
        $this->render('ptc/index', compact('ads'));
    }

    public function view(string $id): void
    {
        $u = $this->auth->user();
        $ad = $this->db->fetch('SELECT * FROM ptc_ads WHERE id = ? AND status = ?', [(int)$id, 'active']);
        if (!$ad) {
            $this->abort(404, 'Ad not found');
        }

        // Daily-limit check (per-user-per-ad).
        if ((int)$ad['daily_limit'] > 0) {
            $today = (int)$this->db->column(
                'SELECT COUNT(*) FROM ptc_views WHERE user_id = ? AND ad_id = ? AND DATE(viewed_at) = CURDATE()',
                [$u['id'], $ad['id']]
            );
            if ($today >= (int)$ad['daily_limit']) {
                $this->redirect('/ptc', 'You have reached the daily view limit for this ad.', 'error');
            }
        }

        // Anti-fake-click token: valid for {duration} seconds.
        $token = bin2hex(random_bytes(16));
        Session::set('ptc_token_' . $ad['id'], [
            'token'      => $token,
            'issued_at'  => time(),
            'duration'   => (int)$ad['duration'],
        ]);

        $this->render('ptc/view', ['ad' => $ad, 'token' => $token], 'app');
    }

    public function claim(string $id): void
    {
        $u = $this->auth->user();
        $adId = (int)$id;
        $ip = Security::clientIp();

        if (!RateLimiter::hit('ptc:user:' . $u['id'], 30, 60)) {
            $this->json(['ok' => false, 'error' => 'rate_limited'], 429);
            return;
        }

        $ad = $this->db->fetch('SELECT * FROM ptc_ads WHERE id = ? AND status = ?', [$adId, 'active']);
        if (!$ad) {
            $this->json(['ok' => false, 'error' => 'not_found'], 404);
            return;
        }

        $session = Session::get('ptc_token_' . $adId);
        $token   = (string)$this->input('token', '');
        if (!is_array($session) || !hash_equals($session['token'], $token)) {
            $this->json(['ok' => false, 'error' => 'token_mismatch'], 400);
            return;
        }
        $elapsed = time() - (int)$session['issued_at'];
        if ($elapsed < (int)$session['duration']) {
            $this->json(['ok' => false, 'error' => 'too_fast', 'wait' => (int)$session['duration'] - $elapsed], 400);
            return;
        }
        Session::forget('ptc_token_' . $adId);

        // Daily limit.
        if ((int)$ad['daily_limit'] > 0) {
            $today = (int)$this->db->column(
                'SELECT COUNT(*) FROM ptc_views WHERE user_id = ? AND ad_id = ? AND DATE(viewed_at) = CURDATE()',
                [$u['id'], $adId]
            );
            if ($today >= (int)$ad['daily_limit']) {
                $this->json(['ok' => false, 'error' => 'daily_limit'], 429);
                return;
            }
        }

        // Multi-account guard: same IP+device used by other users today on this ad?
        $fp = Security::deviceFingerprint();
        $other = (int)$this->db->column(
            'SELECT COUNT(DISTINCT user_id) FROM ptc_views WHERE ip_address = ? AND device_fp = ?
             AND ad_id = ? AND DATE(viewed_at) = CURDATE() AND user_id <> ?',
            [$ip, $fp, $adId, $u['id']]
        );
        if ($other > 0) {
            Security::logEvent((int)$u['id'], 'ptc_multi_account', "Same IP/device for ad $adId", 'warning');
            $this->json(['ok' => false, 'error' => 'multi_account'], 403);
            return;
        }

        // Record view + increment counters atomically.
        $this->db->transaction(function ($db) use ($u, $adId, $ip, $fp, $ad) {
            $db->insert('ptc_views', [
                'user_id'    => $u['id'],
                'ad_id'      => $adId,
                'ip_address' => $ip,
                'device_fp'  => $fp,
                'viewed_at'  => date('Y-m-d H:i:s'),
            ]);
            $db->run('UPDATE ptc_ads SET views_count = views_count + 1 WHERE id = ?', [$adId]);
        });

        // Stop ad once max_views reached.
        $this->db->run(
            "UPDATE ptc_ads SET status = 'ended' WHERE id = ? AND max_views > 0 AND views_count >= max_views",
            [$adId]
        );

        $balance = User::credit((int)$u['id'], 'ptc', (float)$ad['reward'], (int)$ad['coin_id'], ['ad_id' => $adId]);
        $this->json(['ok' => true, 'amount' => (float)$ad['reward'], 'balance' => $balance]);
    }
}
