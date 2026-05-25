<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Application;
use App\Core\Model;

final class Coin extends Model
{
    protected static string $table = 'coins';

    /** @return list<array<string,mixed>> */
    public static function active(): array
    {
        return Application::$db->fetchAll(
            'SELECT * FROM coins WHERE is_active = 1 ORDER BY display_order, code'
        );
    }

    public static function byCode(string $code): ?array
    {
        return self::findBy('code', strtoupper($code));
    }

    public static function defaultCoin(): ?array
    {
        $code = (string)\App\Core\Setting::get('default_coin_code', 'LTC');
        return self::byCode($code) ?? (self::active()[0] ?? null);
    }
}
