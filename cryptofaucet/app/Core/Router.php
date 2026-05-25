<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Trivial regex-based router. Routes are added by /config/routes.php.
 * Pattern syntax: /faucet/claim/{coin}  (each {param} matches [^/]+).
 */
final class Router
{
    /** @var array<int, array{0:string,1:string,2:callable|string,3:array<string>}> */
    private array $routes = [];

    public function get(string $pattern, callable|string $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|string $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function any(string $pattern, callable|string $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, callable|string $handler, array $middleware): void
    {
        $this->routes[] = [$method, $pattern, $handler, $middleware];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $url    = $_GET['_url'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $url    = '/' . trim(parse_url($url, PHP_URL_PATH) ?: '/', '/');

        foreach ($this->routes as [$rmethod, $pattern, $handler, $middleware]) {
            if ($rmethod !== $method) continue;
            $regex = $this->compile($pattern);
            if (preg_match($regex, $url, $m)) {
                array_shift($m);
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                foreach ($middleware as $mw) {
                    if (!$this->runMiddleware($mw)) return;
                }
                $this->invoke($handler, $params);
                return;
            }
        }

        http_response_code(404);
        $view = new View();
        echo $view->error(404, 'Page not found');
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace('#\{([a-z_][a-z0-9_]*)\}#i', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    private function runMiddleware(string $name): bool
    {
        return match ($name) {
            'auth'  => Middleware::auth(),
            'guest' => Middleware::guest(),
            'admin' => Middleware::admin(),
            'csrf'  => Middleware::csrf(),
            default => true,
        };
    }

    private function invoke(callable|string $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }
        // "Controller@method" or "Namespace\\Controller@method"
        [$class, $method] = explode('@', $handler);
        if (!str_contains($class, '\\')) {
            $class = 'App\\Controllers\\' . $class;
        }
        $controller = new $class();
        call_user_func_array([$controller, $method], $params);
    }
}
