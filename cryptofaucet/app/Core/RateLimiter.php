<?php
declare(strict_types=1);

namespace App\Core;

/**
 * DB-backed sliding-window rate limiter.  Bucket key is hashed so it's
 * always 32 chars, and TTL is enforced via expires_at.
 */
final class RateLimiter
{
    public static function hit(string $key, int $maxHits, int $windowSeconds): bool
    {
        $bucket = self::bucketKey($key, $windowSeconds);
        $db     = Application::$db;
        $now    = date('Y-m-d H:i:s');
        $exp    = date('Y-m-d H:i:s', time() + $windowSeconds);

        // garbage collect ~1% of the time
        if (random_int(0, 99) === 0) {
            $db->run('DELETE FROM rate_limits WHERE expires_at < ?', [$now]);
        }

        $db->run(
            'INSERT INTO rate_limits (bucket, hits, expires_at) VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE hits = hits + 1',
            [$bucket, $exp]
        );
        $hits = (int)$db->column('SELECT hits FROM rate_limits WHERE bucket = ?', [$bucket]);
        return $hits <= $maxHits;
    }

    public static function reset(string $key, int $windowSeconds = 60): void
    {
        Application::$db->run(
            'DELETE FROM rate_limits WHERE bucket = ?',
            [self::bucketKey($key, $windowSeconds)]
        );
    }

    private static function bucketKey(string $key, int $window): string
    {
        // Fold timestamp into window so same key+window collapses naturally.
        $slot = floor(time() / max(1, $window));
        return substr(hash('sha256', $key . '|' . $slot), 0, 64);
    }
}
