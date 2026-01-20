<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class CostCode
{
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM costcodes ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(PDO $pdo): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM costcodes";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['cnt'] ?? 0);
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $sql = "SELECT * FROM costcodes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function findByCostCode(PDO $pdo, string $costCode): ?array
    {
        $sql = "SELECT * FROM costcodes WHERE cost_code = :cost_code LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cost_code', $costCode);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function create(PDO $pdo, array $data): ?int
    {
        $sql = "INSERT INTO costcodes (level, ust_baslik_veri, ortalama_gider, cost_code, direct_indirect, muhasebe_kodu_aciklama, cost_code_description, created_at, updated_at)
                VALUES (:level, :ust_baslik_veri, :ortalama_gider, :cost_code, :direct_indirect, :muhasebe_kodu_aciklama, :cost_code_description, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':level', $data['level'] ?? null);
        $stmt->bindValue(':ust_baslik_veri', $data['ust_baslik_veri'] ?? null);
        $stmt->bindValue(':ortalama_gider', !empty($data['ortalama_gider']) ? (float)$data['ortalama_gider'] : null);
        $stmt->bindValue(':cost_code', $data['cost_code'] ?? null);
        $stmt->bindValue(':direct_indirect', $data['direct_indirect'] ?? null);
        $stmt->bindValue(':muhasebe_kodu_aciklama', $data['muhasebe_kodu_aciklama'] ?? null);
        $stmt->bindValue(':cost_code_description', $data['cost_code_description'] ?? null);

        $stmt->execute();
        return (int)$pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "UPDATE costcodes SET
                level = :level,
                ust_baslik_veri = :ust_baslik_veri,
                ortalama_gider = :ortalama_gider,
                cost_code = :cost_code,
                direct_indirect = :direct_indirect,
                muhasebe_kodu_aciklama = :muhasebe_kodu_aciklama,
                cost_code_description = :cost_code_description,
                updated_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':level', $data['level'] ?? null);
        $stmt->bindValue(':ust_baslik_veri', $data['ust_baslik_veri'] ?? null);
        $stmt->bindValue(':ortalama_gider', !empty($data['ortalama_gider']) ? (float)$data['ortalama_gider'] : null);
        $stmt->bindValue(':cost_code', $data['cost_code'] ?? null);
        $stmt->bindValue(':direct_indirect', $data['direct_indirect'] ?? null);
        $stmt->bindValue(':muhasebe_kodu_aciklama', $data['muhasebe_kodu_aciklama'] ?? null);
        $stmt->bindValue(':cost_code_description', $data['cost_code_description'] ?? null);

        return $stmt->execute();
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $sql = "DELETE FROM costcodes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function removeDuplicates(PDO $pdo): array
    {
        // Count before
        $stmtBefore = $pdo->query("SELECT COUNT(*) as total FROM costcodes");
        $beforeTotal = $stmtBefore->fetch(PDO::FETCH_ASSOC)['total'];

        $stmtBefore = $pdo->query("SELECT COUNT(DISTINCT cost_code) as unique_codes FROM costcodes WHERE cost_code IS NOT NULL AND cost_code != ''");
        $beforeUnique = $stmtBefore->fetch(PDO::FETCH_ASSOC)['unique_codes'];

        // Remove duplicates - keep the first occurrence of each cost_code
        $sql = "DELETE FROM costcodes
                WHERE id NOT IN (
                    SELECT MIN(id) as id
                    FROM (
                        SELECT MIN(id) as id
                        FROM costcodes
                        WHERE cost_code IS NOT NULL AND cost_code != ''
                        GROUP BY cost_code
                    ) AS keep
                ) AND cost_code IS NOT NULL AND cost_code != ''";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $deleted = $stmt->rowCount();

        // Count after
        $stmtAfter = $pdo->query("SELECT COUNT(*) as total FROM costcodes");
        $afterTotal = $stmtAfter->fetch(PDO::FETCH_ASSOC)['total'];

        $stmtAfter = $pdo->query("SELECT COUNT(DISTINCT cost_code) as unique_codes FROM costcodes WHERE cost_code IS NOT NULL AND cost_code != ''");
        $afterUnique = $stmtAfter->fetch(PDO::FETCH_ASSOC)['unique_codes'];

        return [
            'before_total' => $beforeTotal,
            'before_unique' => $beforeUnique,
            'after_total' => $afterTotal,
            'after_unique' => $afterUnique,
            'deleted' => $deleted,
        ];
    }
}
