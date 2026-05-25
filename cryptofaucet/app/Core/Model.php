<?php
declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected static string $table;

    protected static function db(): Database
    {
        return Application::$db;
    }

    public static function find(int $id): ?array
    {
        return self::db()->fetch('SELECT * FROM `' . static::$table . '` WHERE id = ?', [$id]);
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        return self::db()->fetch(
            'SELECT * FROM `' . static::$table . '` WHERE `' . $column . '` = ? LIMIT 1',
            [$value]
        );
    }

    public static function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        if (Schema::hasColumn(static::$table, 'updated_at')) {
            $data['updated_at'] = $data['updated_at'] ?? $now;
        }
        return self::db()->insert(static::$table, $data);
    }

    public static function update(int $id, array $data): int
    {
        if (Schema::hasColumn(static::$table, 'updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return self::db()->update(static::$table, $data, 'id = :_id', ['_id' => $id]);
    }

    public static function delete(int $id): int
    {
        return self::db()->run('DELETE FROM `' . static::$table . '` WHERE id = ?', [$id])->rowCount();
    }
}
