<?php

namespace App\Http\Controllers;

use App\Infrastructure\Database\Connection;

class AppController
{
    public function dashboard(): void
    {
        \App\Support\Session::start();
        if (empty($_SESSION['user'])) {
            header('Location: /app/login', true, 303);
            return;
        }
        $dbStatus = 'OK';
        try {
            $pdo = Connection::pdo();
            $pdo->query('SELECT 1')->fetchColumn();
        } catch (\Throwable $e) {
            $dbStatus = 'Error: ' . $e->getMessage();
        }
        \App\Http\View::render('app/dashboard', [
            'dbStatus' => $dbStatus,
            'title' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => '/app'],
            ],
        ]);
    }
}
