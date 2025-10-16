<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;

$pdo = Connection::pdo();
$sqlFile = __DIR__ . '/../migrations/001_init.sql';
if (!file_exists($sqlFile)) {
    fwrite(STDERR, "Migration file not found: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Failed to read migration file.\n");
    exit(1);
}

try {
    $pdo->exec($sql);
    echo "Migration applied.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

