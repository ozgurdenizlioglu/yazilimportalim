<?php
// Check current column names
try {
    $pdo = new PDO('pgsql:host=host.docker.internal;port=5432;dbname=myapp;user=postgres;password=Ozgur16betul');

    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'contract'
        ORDER BY ordinal_position
    ");

    echo "Current columns in contract table:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  - " . $row['column_name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
