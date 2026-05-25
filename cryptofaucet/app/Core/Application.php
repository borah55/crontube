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

        $router = new Router();
        require self::$rootPath . '/config/routes.php';
        $router->dispatch();
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
                header('Location: install.php');
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

        http_response_code(500);
        if (self::$config['app']['debug'] ?? false) {
            echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</pre>';
        } else {
            echo '<h1>Something went wrong</h1><p>Please try again later.</p>';
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
