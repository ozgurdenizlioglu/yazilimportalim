<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Units
{
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM public.units ORDER BY TR ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM public.units WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByTR(PDO $pdo, string $tr): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM public.units WHERE TR = :tr LIMIT 1");
        $stmt->execute([':tr' => $tr]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByEN(PDO $pdo, string $en): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM public.units WHERE EN = :en LIMIT 1");
        $stmt->execute([':en' => $en]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
