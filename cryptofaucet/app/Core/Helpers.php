<?php
declare(strict_types=1);

namespace App\Core;

final class Helpers
{
    public static function formatCrypto(float|string $amount, int $decimals = 8): string
    {
        return rtrim(rtrim(number_format((float)$amount, $decimals, '.', ''), '0'), '.') ?: '0';
    }

    public static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)        return $diff . 's ago';
        if ($diff < 3600)      return floor($diff / 60) . 'm ago';
        if ($diff < 86400)     return floor($diff / 3600) . 'h ago';
        if ($diff < 30 * 86400) return floor($diff / 86400) . 'd ago';
        return date('Y-m-d', strtotime($datetime));
    }

    public static function randomCode(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $out = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    public static function url(string $path = ''): string
    {
        $base = (string)Setting::get('site_url', '');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}
