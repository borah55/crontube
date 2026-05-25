<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base controller offering view rendering and JSON helpers.
 */
abstract class Controller
{
    protected View $view;
    protected ?Database $db;
    protected ?Auth $auth;

    public function __construct()
    {
        $this->view = new View();
        $this->db   = Application::$db;
        $this->auth = Application::$auth;
    }

    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        echo $this->view->render($view, $data, $layout);
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $url, ?string $flash = null, string $type = 'success'): void
    {
        if ($flash !== null) {
            Session::flash($type, $flash);
        }
        // Prefix root-absolute URLs with the detected base path so subdirectory
        // installs redirect to the right place.  External URLs (https://...,
        // //host/...) and already-prefixed paths pass through unchanged.
        $base = rtrim(Application::$basePath, '/');
        if ($base !== ''
            && str_starts_with($url, '/')
            && !str_starts_with($url, '//')
            && !str_starts_with($url, $base . '/')
            && $url !== $base) {
            $url = $base . $url;
        }
        header('Location: ' . $url);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function wantsJson(): bool
    {
        return Middleware::wantsJson();
    }

    protected function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => $message ?: 'error']);
        } else {
            echo $this->view->error($status, $message);
        }
        exit;
    }
}
