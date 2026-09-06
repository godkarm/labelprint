<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Core\ViewConfig;

abstract class BaseController
{
    /**
     * Obtiene el basePath dinámicamente (compatible con XAMPP/subcarpeta).
     */
    protected function basePath(): string
    {
        return ViewConfig::getBasePath();
    }

    protected function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            // Usa basePath para que funcione en subcarpeta (XAMPP)
            View::redirect($this->basePath() . '/login');
        }
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($token)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Token de seguridad inválido.']));
        }
    }

    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    protected function jsonSuccess(string $message, array $data = []): void
    {
        View::json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function jsonError(string $message, int $status = 200): void
    {
        View::json(['success' => false, 'message' => $message], $status);
    }

    protected function redirect(string $path): void
    {
        View::redirect($this->basePath() . $path);
    }

    protected function flashAndRedirect(string $type, string $message, string $path): void
    {
        Session::flash($type, $message);
        $this->redirect($path);
    }
}
