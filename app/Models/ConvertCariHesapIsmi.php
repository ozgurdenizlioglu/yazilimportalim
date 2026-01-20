<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class ConvertCariHesapIsmi
{
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM convertCariHesapIsmi ORDER BY from_CariHesapIsmi ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM convertCariHesapIsmi WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByFrom(PDO $pdo, string $from): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM convertCariHesapIsmi WHERE from_CariHesapIsmi = :from LIMIT 1");
        $stmt->execute([':from' => $from]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO convertCariHesapIsmi (from_CariHesapIsmi, to_CariHesapIsmi)
            VALUES (:from, :to)
            RETURNING id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':from' => $data['from_CariHesapIsmi'] ?? '',
            ':to' => $data['to_CariHesapIsmi'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "
            UPDATE convertCariHesapIsmi 
            SET from_CariHesapIsmi = :from,
                to_CariHesapIsmi = :to,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':from' => $data['from_CariHesapIsmi'] ?? '',
            ':to' => $data['to_CariHesapIsmi'] ?? null,
        ]);
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM convertCariHesapIsmi WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function convert(PDO $pdo, string $value): string
    {
        // Check if there's a mapping for this value
        $record = self::findByFrom($pdo, strtoupper($value));

        if ($record && !empty($record['to_CariHesapIsmi'])) {
            return $record['to_CariHesapIsmi'];
        }

        // If no mapping or to_CariHesapIsmi is empty, return original value
        return $value;
    }
}
