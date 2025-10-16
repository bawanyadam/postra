<?php

namespace App\Http;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [strtoupper($method), $this->compile($pattern), $handler, $pattern];
    }

    public function dispatch(string $method, string $uri)
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        foreach ($this->routes as [$m, $regex, $handler]) {
            if ($m !== strtoupper($method)) continue;
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                return $handler($matches);
            }
        }
        http_response_code(404);
        header('Content-Type: text/plain');
        echo "Not Found";
        return null;
    }

    private function compile(string $pattern): string
    {
        // Convert patterns like /form/{id} to regex
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '([^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}

