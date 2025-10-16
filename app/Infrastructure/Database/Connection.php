<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use App\Support\Env;

class Connection
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        Env::load();
        $dsn = Env::get('DB_DSN', 'mysql:host=127.0.0.1;port=3306;dbname=postra;charset=utf8mb4');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: text/plain');
            echo "Database connection failed: " . $e->getMessage();
            exit(1);
        }

        self::$pdo = $pdo;
        return self::$pdo;
    }
}

