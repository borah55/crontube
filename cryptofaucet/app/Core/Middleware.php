<?php
declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public static function auth(): bool
    {
        if (!Application::$auth->check()) {
            self::redirect('/login');
            return false;
        }
        return true;
    }

    public static function guest(): bool
    {
        if (Application::$auth->check()) {
            self::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public static function admin(): bool
    {
        if (!Application::$auth->isAdmin()) {
            http_response_code(403);
            $view = new View();
            echo $view->error(403, 'Forbidden');
            return false;
        }
        return true;
    }

    public static function csrf(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!Csrf::verify((string)$token)) {
                http_response_code(419);
                if (self::wantsJson()) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => 'csrf_invalid']);
                } else {
                    $view = new View();
                    echo $view->error(419, 'CSRF token expired. Please retry.');
                }
                return false;
            }
        }
        return true;
    }

    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json') || strtolower($xrw) === 'xmlhttprequest';
    }

    private static function redirect(string $path): void
    {
        $base = rtrim(Application::$basePath, '/');
        if ($base !== ''
            && str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_starts_with($path, $base . '/')
            && $path !== $base) {
            $path = $base . $path;
        }
        header('Location: ' . $path);
    }
}
