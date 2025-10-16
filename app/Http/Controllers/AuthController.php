<?php

namespace App\Http\Controllers;

use App\Support\Session;
use App\Support\Csrf;
use App\Services\AuthService;

class AuthController
{
    public function showLogin(): void
    {
        Session::start();
        \App\Http\View::render('auth/login');
    }

    public function login(): void
    {
        Session::start();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $auth = new AuthService();
        if ($auth->verify($username, $password)) {
            $_SESSION['user'] = $username;
            header('Location: /app', true, 303);
        } else {
            $_SESSION['flash'] = 'Invalid credentials';
            header('Location: /app/login', true, 303);
        }
    }

    public function logout(): void
    {
        Session::start();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        session_destroy();
        header('Location: /app/login', true, 303);
    }
}
