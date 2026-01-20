<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

// Load env
$root = dirname(__DIR__);
if (file_exists($root . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

// Simple auth check (not secure, only for dev)
$token = $_GET['token'] ?? '';
if ($token !== 'run_migrations') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

echo "<h2>Running Migration: 021_create_costcodes</h2>\n";

try {
    $pdo = Database::pdo();

    $migrationFile = $root . '/database/migrations/021_create_costcodes.sql';

    if (!file_exists($migrationFile)) {
        echo "<p style='color:red'>Migration file not found: $migrationFile</p>\n";
        exit(1);
    }

    // Read the migration file
    $sql = file_get_contents($migrationFile);

    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)), function ($stmt) {
        return !empty($stmt);
    });

    foreach ($statements as $statement) {
        echo "<p>Executing statement...</p>\n";
        $pdo->exec($statement . ';');
    }

    echo "<p style='color:green'><strong>✓ Migration 021_create_costcodes executed successfully!</strong></p>\n";
    echo "<p><a href='/costcodes'>Go to Cost Codes</a></p>\n";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit(1);
}
