<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cached key/value settings store. One round-trip per request.
 */
final class Setting
{
    private static array $cache = [];
    private static bool  $loaded = false;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::loadAll();
        Application::$db->run(
            'INSERT INTO settings (key_name, value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)',
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    /** @return array<string,string|null> */
    public static function all(): array
    {
        self::loadAll();
        return self::$cache;
    }

    private static function loadAll(): void
    {
        if (self::$loaded) return;
        $rows = Application::$db->fetchAll('SELECT key_name, value FROM settings');
        foreach ($rows as $r) {
            self::$cache[$r['key_name']] = $r['value'];
        }
        self::$loaded = true;
    }
}
