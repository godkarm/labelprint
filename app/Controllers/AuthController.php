<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Core\ViewConfig;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
            return;
        }
        View::render('auth.login', [], 'minimal');
    }

    public function login(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Error de seguridad. Intente nuevamente.');
            $this->redirect('/login');
            return;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            Session::flash('error', 'Ingrese correo y contraseña.');
            $this->redirect('/login');
            return;
        }

        $user = Database::fetchOne(
            "SELECT * FROM usuarios WHERE email = ? AND activo = 1",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            // Timing-safe: no revelar si el email existe
            password_verify('dummy', '$2y$12$invalid');
            Session::flash('error', 'Credenciales incorrectas.');
            $this->redirect('/login');
            return;
        }

        Session::set('user_id', $user['id']);
        Session::set('user_nombre', $user['nombre']);
        Session::set('user_rol', $user['rol']);
        Session::set('user_email', $user['email']);

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
