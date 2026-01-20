<?php
// Quick script to check and create boq table
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

try {
    $pdo = new PDO(
        $_ENV['DB_DSN'] ?? 'pgsql:host=host.docker.internal;port=5432;dbname=myapp',
        $_ENV['DB_USER'] ?? 'postgres',
        $_ENV['DB_PASS'] ?? 'Ozgur16betul',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Connected to database.\n\n";

    // Check if table exists
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'boq' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($columns)) {
        echo "Table 'boq' does not exist. Creating it now...\n\n";

        // Read and execute migration
        $sql = file_get_contents(__DIR__ . '/../database/migrations/024_create_boq.sql');
        $pdo->exec($sql);

        echo "✓ Table 'boq' created successfully!\n";
    } else {
        echo "Table 'boq' exists with columns:\n";
        foreach ($columns as $col) {
            echo "  - $col\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
