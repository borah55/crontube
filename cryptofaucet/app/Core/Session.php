<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Hardened session wrapper.
 * - HTTP-only, Lax SameSite, secure when over HTTPS.
 * - Strict mode prevents uninitialized session IDs.
 * - Periodic regeneration on privilege change.
 */
final class Session
{
    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name('CFSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        // Soft fingerprint to reduce session hijacking impact.
        $fp = self::fingerprint();
        if (!isset($_SESSION['_fp'])) {
            $_SESSION['_fp'] = $fp;
        } elseif (!hash_equals($_SESSION['_fp'], $fp)) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['_fp'] = self::fingerprint();
        }
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_fp'] = self::fingerprint();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /** @return array<string, array<int,string>> */
    public static function pullFlash(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }

    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return hash('sha256', $ua . '|' . $al);
    }
}
