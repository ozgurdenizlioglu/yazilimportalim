<?php

//C:\Users\ozgur\myapp\app\Core\Router.php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->routes['PATCH'][$path] = $handler;
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                http_response_code(500);
                echo 'Controller not found';
                return;
            }
            $controller = new $class();
            if (!method_exists($controller, $action)) {
                http_response_code(500);
                echo 'Action not found';
                return;
            }
            $controller->$action();
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        http_response_code(500);
        echo 'Invalid route handler';
    }
}
