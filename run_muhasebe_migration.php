<?php
try {
    $pdo = new PDO('pgsql:host=localhost;port=5432;dbname=myapp', 'postgres', 'Ozgur16betul');
    $sql = file_get_contents('database/migrations/020_create_muhasebe.sql');
    $pdo->exec($sql);
    echo "✓ Muhasebe table created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
