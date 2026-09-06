<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $viewPath = __DIR__ . '/../Views/';

    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Always inject basePath
        if (!isset($data['basePath'])) {
            $data['basePath'] = ViewConfig::getBasePath();
        }

        // Extract data to local scope
        extract($data, EXTR_SKIP);

        // Capture view content
        ob_start();
        $viewFile = self::$viewPath . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }
        require $viewFile;
        $content = ob_get_clean();

        // Render layout
        $layoutFile = self::$viewPath . 'layouts/' . $layout . '.php';
        if ($layout && file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
