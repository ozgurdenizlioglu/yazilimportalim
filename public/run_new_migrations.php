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

$migrations = [
    '025_create_convertCariHesapIsmi.sql',
    '026_create_costcodeassignment.sql',
    '027_create_units.sql',
    '028_create_tutanak.sql'
];

try {
    $pdo = Database::pdo();

    foreach ($migrations as $migrationFileName) {
        echo "<h2>Running Migration: " . htmlspecialchars($migrationFileName) . "</h2>\n";

        $migrationFile = $root . '/database/migrations/' . $migrationFileName;

        if (!file_exists($migrationFile)) {
            echo "<p style='color:red'>Migration file not found: $migrationFile</p>\n";
            continue;
        }

        // Read the migration file
        $sql = file_get_contents($migrationFile);

        // Split by semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)), function ($stmt) {
            return !empty($stmt);
        });

        foreach ($statements as $statement) {
            $pdo->exec($statement . ';');
        }

        echo "<p style='color:green'><strong>✓ Migration " . htmlspecialchars($migrationFileName) . " executed successfully!</strong></p>\n";
    }

    echo "<hr style='margin: 30px 0;'>\n";
    echo "<p style='color:green; font-weight: bold; font-size: 18px;'><strong>✓ All 4 new migrations executed successfully!</strong></p>\n";
    echo "<p><a href='/tutanak' style='font-size: 16px; color: #007bff;'><strong>Go to Tutanak Module</strong></a></p>\n";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
