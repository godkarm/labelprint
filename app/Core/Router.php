<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $controller, string $method): void
    {
        $this->routes['GET'][$path] = [$controller, $method];
    }

    public function post(string $path, string $controller, string $method): void
    {
        $this->routes['POST'][$path] = [$controller, $method];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Strip query string
        $path = parse_url($uri, PHP_URL_PATH);

        // Remove base path if running in subdirectory
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        $routes = $this->routes[$method] ?? [];

        // Exact match
        if (isset($routes[$path])) {
            [$controllerClass, $action] = $routes[$path];
            $this->run($controllerClass, $action, []);
            return;
        }

        // Parametric match
        foreach ($routes as $route => $handler) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                [$controllerClass, $action] = $handler;
                $this->run($controllerClass, $action, $matches);
                return;
            }
        }

        // 404
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }

    private function run(string $controllerClass, string $action, array $params): void
    {
        $fullClass = 'App\\Controllers\\' . $controllerClass;
        if (!class_exists($fullClass)) {
            http_response_code(500);
            error_log("Controller not found: $fullClass");
            echo 'Error del servidor.';
            return;
        }
        $controller = new $fullClass();
        call_user_func_array([$controller, $action], $params);
    }
}
