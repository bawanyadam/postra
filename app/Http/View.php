<?php

namespace App\Http;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = __DIR__ . '/Views/' . ltrim($template, '/');
        if (!str_ends_with($viewPath, '.php')) {
            $viewPath .= '.php';
        }
        if (!file_exists($viewPath)) {
            http_response_code(500);
            header('Content-Type: text/plain');
            echo "View not found: {$template}";
            return;
        }
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        include __DIR__ . '/Views/layout.php';
    }
}

