<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tiny in-memory cache so models can ask "does table X have updated_at?"
 * without hitting INFORMATION_SCHEMA on every write.
 */
final class Schema
{
    /** @var array<string, array<int,string>> */
    private static array $cache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        if (!isset(self::$cache[$table])) {
            $rows = Application::$db->fetchAll('SHOW COLUMNS FROM `' . $table . '`');
            self::$cache[$table] = array_column($rows, 'Field');
        }
        return in_array($column, self::$cache[$table], true);
    }
}
