<?php

namespace App\Services;

use App\Infrastructure\Database\Connection;
use PDO;

class AuthService
{
    public function verify(string $username, string $password): bool
    {
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        return password_verify($password, $row['password_hash']);
    }

    public function userId(string $username): ?int
    {
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }
}

