<?php
// Execute migration step by step
try {
    $pdo = new PDO('pgsql:host=host.docker.internal;port=5432;dbname=myapp;user=postgres;password=Ozgur16betul');

    echo "Before migration:\n";
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'contract'
        AND column_name IN ('employer_company_id', 'contractor_company_id', 'subcontractor_company_id')
        ORDER BY ordinal_position
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  - " . $row['column_name'] . "\n";
    }

    echo "\nExecuting: ALTER TABLE public.contract RENAME COLUMN employer_company_id TO contractor_company_id;\n";
    $pdo->exec("ALTER TABLE public.contract RENAME COLUMN employer_company_id TO contractor_company_id");
    echo "✓ Column renamed successfully\n";

    echo "\nAfter migration:\n";
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'contract'
        AND column_name IN ('employer_company_id', 'contractor_company_id', 'subcontractor_company_id')
        ORDER BY ordinal_position
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  - " . $row['column_name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
