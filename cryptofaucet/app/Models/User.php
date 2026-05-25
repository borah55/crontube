<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Application;
use App\Core\Helpers;
use App\Core\Model;
use App\Core\Security;

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByLogin(string $login): ?array
    {
        return Application::$db->fetch(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        );
    }

    public static function uniqueReferralCode(): string
    {
        do {
            $code = Helpers::randomCode(8);
            $exists = Application::$db->column(
                'SELECT 1 FROM users WHERE referral_code = ?',
                [$code]
            );
        } while ($exists);
        return $code;
    }

    /**
     * Credit a user's balance, log a transaction, return the new balance.
     */
    public static function credit(
        int $userId,
        string $type,
        float $amount,
        ?int $coinId = null,
        array $meta = []
    ): float {
        $db = Application::$db;
        return $db->transaction(function ($db) use ($userId, $type, $amount, $coinId, $meta) {
            $db->run(
                'UPDATE users SET balance = balance + ?, total_earned = total_earned + ? WHERE id = ?',
                [number_format($amount, 8, '.', ''), number_format($amount, 8, '.', ''), $userId]
            );
            $balance = (float)$db->column('SELECT balance FROM users WHERE id = ?', [$userId]);
            $db->insert('transactions', [
                'user_id'       => $userId,
                'coin_id'       => $coinId,
                'type'          => $type,
                'amount'        => number_format($amount, 8, '.', ''),
                'balance_after' => number_format($balance, 8, '.', ''),
                'meta'          => $meta ? json_encode($meta) : null,
                'ip_address'    => Security::clientIp(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            return $balance;
        });
    }

    /**
     * Debit a user's balance. Returns false if insufficient funds.
     */
    public static function debit(
        int $userId,
        string $type,
        float $amount,
        ?int $coinId = null,
        array $meta = []
    ): float|false {
        $db = Application::$db;
        return $db->transaction(function ($db) use ($userId, $type, $amount, $coinId, $meta) {
            $current = (float)$db->column('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$userId]);
            if ($current < $amount) return false;
            $db->run('UPDATE users SET balance = balance - ? WHERE id = ?',
                [number_format($amount, 8, '.', ''), $userId]);
            $balance = $current - $amount;
            $db->insert('transactions', [
                'user_id'       => $userId,
                'coin_id'       => $coinId,
                'type'          => $type,
                'amount'        => number_format(-$amount, 8, '.', ''),
                'balance_after' => number_format($balance, 8, '.', ''),
                'meta'          => $meta ? json_encode($meta) : null,
                'ip_address'    => Security::clientIp(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            return $balance;
        });
    }
}
