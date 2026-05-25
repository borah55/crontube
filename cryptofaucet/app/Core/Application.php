<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application bootstrap: loads config, registers autoloader,
 * initialises session/db, dispatches the request.
 */
final class Application
{
    public static string $rootPath;
    public static string $basePath = ''; // URL path prefix when installed in a subdir.
    public static array  $config = [];
    public static ?Database $db = null;
    public static ?Auth $auth = null;
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(string $rootPath): void
    {
        self::$rootPath = rtrim($rootPath, '/');
        self::$basePath = self::detectBasePath();

        $this->registerAutoloader();
        $this->loadConfig();
        $this->configureErrors();
        $this->configureTimezone();
        Session::start();
        self::$db   = Database::instance();
        self::$auth = new Auth(self::$db);
    }

    public function dispatch(): void
    {
        if ($this->isMaintenance() && !self::$auth->isAdmin()) {
            $this->renderMaintenance();
            return;
        }

        // Buffer output so we can rewrite root-absolute href/action/src URLs
        // when the project is installed in a subdirectory.
        ob_start([self::class, 'rewriteHtmlPaths']);

        $router = new Router();
        require self::$rootPath . '/config/routes.php';
        $router->dispatch();

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Auto-detect the URL path prefix the project is installed under, e.g.
     *   /index.php           -> ""
     *   /faucet/index.php    -> "/faucet"
     *   /apps/cf/index.php   -> "/apps/cf"
     */
    private static function detectBasePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        $dir = rtrim($dir, '/');
        return ($dir === '' || $dir === '/') ? '' : $dir;
    }

    /**
     * Output filter: prefix every root-absolute href/action/src with the
     * detected basePath. Skips JSON, SQL backups, and other non-HTML responses.
     * Skips protocol-relative (//host) and already-prefixed URLs.
     */
    public static function rewriteHtmlPaths(string $html): string
    {
        $base = self::$basePath;
        if ($base === '' || $html === '') {
            return $html;
        }
        // Only touch HTML responses.
        if (!preg_match('/^\s*<(?:!doctype|html|!--|head|body|div|main|h\d|p|section|article|table|form)/i', $html)) {
            return $html;
        }
        $base = rtrim($base, '/');
        return preg_replace_callback(
            '#(\s)(href|action|src)\s*=\s*(["\'])(/(?!/)[^"\']*)\3#i',
            static function (array $m) use ($base): string {
                $url = $m[4];
                if ($url === $base || str_starts_with($url, $base . '/')) {
                    return $m[0];
                }
                return $m[1] . $m[2] . '=' . $m[3] . $base . $url . $m[3];
            },
            $html
        ) ?? $html;
    }

    private function registerAutoloader(): void
    {
        spl_autoload_register(static function (string $class): void {
            // Map App\* namespaces to /app/* directories.
            if (!str_starts_with($class, 'App\\')) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, 4));
            $file     = self::$rootPath . '/app/' . $relative . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    private function loadConfig(): void
    {
        $configFile = self::$rootPath . '/config/config.php';
        if (!is_file($configFile)) {
            // Redirect to installer if present, otherwise show a friendly error.
            if (is_file(self::$rootPath . '/install.php') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'install.php')) {
                header('Location: ' . rtrim(self::$basePath, '/') . '/install.php');
                exit;
            }
            http_response_code(500);
            echo 'Configuration missing. Run install.php to set up.';
            exit;
        }
        self::$config = require $configFile;
    }

    private function configureErrors(): void
    {
        $debug = (bool)(self::$config['app']['debug'] ?? false);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$rootPath . '/storage/logs/php-error.log');
        error_reporting(E_ALL);

        set_exception_handler([$this, 'handleException']);
    }

    public function handleException(\Throwable $e): void
    {
        $msg = '[' . date('Y-m-d H:i:s') . '] ' . $e::class . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
            . $e->getTraceAsString() . PHP_EOL;
        @file_put_contents(self::$rootPath . '/storage/logs/exception.log', $msg, FILE_APPEND);

        // Discard any partially-rendered output so the error page is clean.
        while (ob_get_level() > 0) { @ob_end_clean(); }

        http_response_code(500);

        // Always show details to admins (so they can self-diagnose without
        // flipping debug mode in config.php) and when debug is enabled.
        $isAdmin = false;
        try {
            $isAdmin = self::$auth?->isAdmin() ?? false;
        } catch (\Throwable) {
            // Auth may not be initialised yet.
        }
        $debug = (bool)(self::$config['app']['debug'] ?? false);

        if ($debug || $isAdmin) {
            echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
                . '<div style="font-family:system-ui;max-width:900px;margin:2rem auto;padding:1.5rem;'
                . 'background:#fff;border-left:6px solid #e11d48;border-radius:.5rem;'
                . 'box-shadow:0 4px 16px rgba(0,0,0,.08);color:#0f172a">'
                . '<h1 style="margin:0 0 .5rem">Something went wrong</h1>'
                . '<p style="color:#64748b;margin:0 0 1rem">'
                . ($isAdmin && !$debug ? 'You are seeing this trace because you are signed in as admin.' : 'Debug mode is on.')
                . '</p><pre style="white-space:pre-wrap;background:#0f172a;color:#f8fafc;padding:1rem;'
                . 'border-radius:.5rem;font-size:.85em;overflow:auto">'
                . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</pre><p style="color:#64748b;font-size:.85em;margin-top:1rem">'
                . 'Logged to <code>storage/logs/exception.log</code>.</p></div>';
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
                . '<div style="font-family:system-ui;text-align:center;padding:4rem">'
                . '<h1>Something went wrong</h1>'
                . '<p>Please try again later. If the problem persists, sign in as admin to see the trace.</p>'
                . '</div>';
        }
    }

    private function configureTimezone(): void
    {
        date_default_timezone_set(self::$config['app']['timezone'] ?? 'UTC');
    }

    private function isMaintenance(): bool
    {
        try {
            return Setting::get('maintenance_mode') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private function renderMaintenance(): void
    {
        $msg = htmlspecialchars(Setting::get('maintenance_message', 'Maintenance in progress'), ENT_QUOTES, 'UTF-8');
        http_response_code(503);
        header('Retry-After: 600');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Maintenance</title></head>'
           . '<body style="font-family:system-ui;text-align:center;padding:4rem">'
           . '<h1>We will be right back</h1><p>' . $msg . '</p></body></html>';
    }
}
