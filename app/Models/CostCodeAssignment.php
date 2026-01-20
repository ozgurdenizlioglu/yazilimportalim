<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class CostCodeAssignment
{
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM costcodeassignment ORDER BY KeyText ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM costcodeassignment WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByKeyText(PDO $pdo, string $keyText): array
    {
        $stmt = $pdo->prepare("SELECT * FROM costcodeassignment WHERE KeyText ILIKE :key ORDER BY KeyText ASC");
        $stmt->execute([':key' => '%' . $keyText . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO costcodeassignment (KeyText, CostCode, Notes)
            VALUES (:key, :code, :notes)
            RETURNING id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':key' => $data['KeyText'] ?? '',
            ':code' => $data['CostCode'] ?? '',
            ':notes' => $data['Notes'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "
            UPDATE costcodeassignment 
            SET KeyText = :key,
                CostCode = :code,
                Notes = :notes,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':key' => $data['KeyText'] ?? '',
            ':code' => $data['CostCode'] ?? '',
            ':notes' => $data['Notes'] ?? null,
        ]);
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM costcodeassignment WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function assignCostCode(PDO $pdo, string $text, ?string $currentCode): ?string
    {
        // Only assign if current code is blank or contains "X"
        if (!empty($currentCode) && strpos($currentCode, 'X') === false) {
            return $currentCode;
        }

        // Search for matching KeyText
        $stmt = $pdo->prepare("
            SELECT CostCode FROM costcodeassignment 
            WHERE UPPER(:text) LIKE '%' || UPPER(KeyText) || '%'
            LIMIT 1
        ");
        $stmt->execute([':text' => $text]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['CostCode'] : $currentCode;
    }
}
