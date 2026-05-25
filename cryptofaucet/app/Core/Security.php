<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    /** Get the real client IP address, considering trusted proxies. */
    public static function clientIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        foreach ($candidates as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /** Stable-per-browser fingerprint (no JS required). */
    public static function deviceFingerprint(): string
    {
        $parts = [
            $_SERVER['HTTP_USER_AGENT']      ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_COOKIE['fp'] ?? '',
        ];
        return substr(hash('sha256', implode('|', $parts)), 0, 32);
    }

    /** Always escape output via this helper in templates. */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Check if an IP is on the blacklist or matched by VPN/proxy detection. */
    public static function isIpBlocked(string $ip): bool
    {
        $blk = Application::$db->fetch('SELECT id FROM ip_blacklist WHERE ip_address = ?', [$ip]);
        if ($blk) return true;

        if (Setting::get('vpn_block_enabled') === '1') {
            return self::isVpnOrProxy($ip);
        }
        return false;
    }

    /** Best-effort VPN/proxy check. Falls back to false when no API key configured. */
    public static function isVpnOrProxy(string $ip): bool
    {
        $key = Setting::get('proxycheck_api_key', '');
        if ($key === '') {
            return false;
        }
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $url = 'https://proxycheck.io/v2/' . urlencode($ip) . '?key=' . urlencode($key) . '&vpn=1&risk=1';
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) return false;
        $data = json_decode($resp, true) ?: [];
        $info = $data[$ip] ?? [];
        $proxy = strtolower((string)($info['proxy'] ?? 'no')) === 'yes';
        $vpn   = strtolower((string)($info['type']  ?? '')) === 'vpn';
        return $proxy || $vpn;
    }

    public static function logEvent(?int $userId, string $event, string $message, string $severity = 'info'): void
    {
        Application::$db->insert('security_logs', [
            'user_id'    => $userId,
            'event'      => $event,
            'severity'   => $severity,
            'message'    => mb_substr($message, 0, 255),
            'ip_address' => self::clientIp(),
            'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function verifyRecaptcha(?string $response): bool
    {
        $secret = Setting::get('recaptcha_secret', '');
        if ($secret === '') return true; // Disabled when not configured.
        if (!$response) return false;
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret'   => $secret,
                    'response' => $response,
                    'remoteip' => self::clientIp(),
                ]),
                'timeout' => 5,
            ],
        ]);
        $body = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
        if (!$body) return false;
        $data = json_decode($body, true) ?: [];
        return (bool)($data['success'] ?? false);
    }
}
