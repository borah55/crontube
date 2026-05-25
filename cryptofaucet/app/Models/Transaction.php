<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Application;
use App\Core\Model;

final class Transaction extends Model
{
    protected static string $table = 'transactions';

    public static function forUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return Application::$db->fetchAll(
            'SELECT t.*, c.code AS coin_code FROM transactions t
             LEFT JOIN coins c ON c.id = t.coin_id
             WHERE t.user_id = ? ORDER BY t.id DESC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    public static function dailySumForUser(int $userId): float
    {
        return (float)Application::$db->column(
            "SELECT COALESCE(SUM(amount),0) FROM transactions
             WHERE user_id = ? AND amount > 0 AND DATE(created_at) = CURDATE()",
            [$userId]
        );
    }
}
