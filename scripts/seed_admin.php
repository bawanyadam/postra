<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;

$username = $argv[1] ?? getenv('ADMIN_USER') ?? null;
$password = $argv[2] ?? getenv('ADMIN_PASS') ?? null;
if (!$username || !$password) {
    fwrite(STDERR, "Usage: php scripts/seed_admin.php <username> <password>\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_ARGON2ID);
$pdo = Connection::pdo();
$stmt = $pdo->prepare('INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, 1)');
try {
    $stmt->execute([$username, $hash]);
    echo "Admin user created: {$username}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}

