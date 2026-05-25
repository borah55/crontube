<?php
/**
 * cron.php - scheduled maintenance entry point.
 *
 * Add as a cron job in cPanel:
 *   Every 5 minutes: php /home/<user>/public_html/cryptofaucet/cron.php >/dev/null 2>&1
 *
 * Tasks:
 *   - Auto-process pending withdrawals via FaucetPay (if configured)
 *   - Prune expired rate-limit and password-reset rows
 *   - Prune security_logs older than 90 days
 *   - Mark long-stuck "processing" withdrawals as failed (auto-refund)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !isset($_GET['cron_token'])) {
    http_response_code(403);
    exit('cron only');
}

require __DIR__ . '/app/Core/Application.php';

\App\Core\Application::instance()->boot(__DIR__);

use App\Core\Application;
use App\Core\FaucetPay;
use App\Core\Security;
use App\Core\Setting;
use App\Models\User;

$db = Application::$db;
$logFile = __DIR__ . '/storage/logs/cron.log';

$log = static function (string $msg) use ($logFile) {
    @file_put_contents($logFile, date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND);
};

$log('cron start');

// 1. Garbage collection ----------------------------------------------
$db->run('DELETE FROM rate_limits WHERE expires_at < NOW()');
$db->run('DELETE FROM password_resets WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
$db->run('DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');

// 2. Auto-fail stuck withdrawals (>1 hour processing) ----------------
$stuck = $db->fetchAll(
    "SELECT * FROM withdrawals WHERE status='processing'
       AND processed_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
foreach ($stuck as $w) {
    $db->update('withdrawals', [
        'status' => 'failed',
        'error_message' => 'Stuck in processing > 1h, auto-failed',
    ], 'id = :_id', ['_id' => $w['id']]);
    User::credit((int)$w['user_id'], 'admin_credit', (float)$w['amount'], (int)$w['coin_id'], ['cron_refund' => $w['id']]);
    Security::logEvent(null, 'withdraw_auto_failed', "Withdrawal {$w['id']} stuck", 'warning');
    $log("auto-failed withdrawal {$w['id']}");
}

// 3. Auto-process pending withdrawals via FaucetPay -------------------
$fp = new FaucetPay();
if ($fp->isConfigured()) {
    $pending = $db->fetchAll(
        "SELECT w.*, c.faucetpay_token FROM withdrawals w
         LEFT JOIN coins c ON c.id = w.coin_id
         WHERE w.status='pending' ORDER BY w.id ASC LIMIT 25"
    );
    foreach ($pending as $w) {
        $db->update('withdrawals', ['status' => 'processing'], 'id = :_id', ['_id' => $w['id']]);
        $resp = $fp->send($w['wallet_address'], (float)$w['net_amount'], (string)$w['faucetpay_token'], $w['ip_address']);
        $statusCode = (int)($resp['status'] ?? 0);
        if ($statusCode === 200) {
            $payoutId = (string)($resp['payout_id'] ?? '');
            $db->update('withdrawals', [
                'status'       => 'paid',
                'processed_at' => date('Y-m-d H:i:s'),
                'faucetpay_tx' => $payoutId,
                'payout_id'    => $payoutId,
            ], 'id = :_id', ['_id' => $w['id']]);
            $db->run('UPDATE users SET total_withdrawn = total_withdrawn + ? WHERE id = ?', [$w['amount'], $w['user_id']]);
            $log("paid withdrawal {$w['id']} ({$w['net_amount']} {$w['faucetpay_token']}) -> $payoutId");
        } else {
            $db->update('withdrawals', [
                'status'        => 'failed',
                'error_message' => mb_substr((string)($resp['message'] ?? 'unknown'), 0, 255),
                'processed_at'  => date('Y-m-d H:i:s'),
            ], 'id = :_id', ['_id' => $w['id']]);
            User::credit((int)$w['user_id'], 'admin_credit', (float)$w['amount'], (int)$w['coin_id'], ['cron_refund' => $w['id']]);
            Security::logEvent(null, 'withdraw_failed', "cron wd {$w['id']}: " . ($resp['message'] ?? ''), 'warning');
            $log("failed withdrawal {$w['id']}: " . ($resp['message'] ?? ''));
        }
        usleep(500_000); // gentle pacing on the FaucetPay API
    }
}

// 4. Auto-end PTC ads that have hit max_views -----------------------
$db->run("UPDATE ptc_ads SET status='ended' WHERE max_views > 0 AND views_count >= max_views AND status='active'");

// 5. Cleanup expired shortlink tokens (>15 minutes & not redeemed) -
$db->run("UPDATE shortlink_completions SET status='expired'
          WHERE status='issued' AND created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");

$log('cron done');
