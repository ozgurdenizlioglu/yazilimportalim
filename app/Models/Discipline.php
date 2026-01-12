<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Core\Helpers;

class Discipline
{
    // Get all active disciplines
    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT id, name_en, name_tr 
            FROM discipline 
            ORDER BY name_tr ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Get single discipline
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT id, name_en, name_tr 
            FROM discipline 
            WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Create new discipline
    public static function create(PDO $pdo, array $data): int
    {
        $nameEn = trim((string)($data['name_en'] ?? $data['name'] ?? ''));
        $nameTr = trim((string)($data['name_tr'] ?? $data['name'] ?? ''));

        if ($nameEn === '' && $nameTr === '') {
            throw new \InvalidArgumentException('name zorunludur.');
        }

        if ($nameEn === '') $nameEn = $nameTr;
        if ($nameTr === '') $nameTr = $nameEn;

        // Convert to uppercase ASCII
        $nameEn = Helpers::toAsciiUppercase($nameEn);
        $nameTr = Helpers::toAsciiUppercase($nameTr);

        $stmt = $pdo->prepare("
            INSERT INTO discipline (name_en, name_tr)
            VALUES (:name_en, :name_tr)
            RETURNING id
        ");
        $stmt->execute([
            ':name_en' => $nameEn,
            ':name_tr' => $nameTr,
        ]);
        return (int)$stmt->fetchColumn();
    }

    // Update discipline
    public static function update(PDO $pdo, int $id, array $data): void
    {
        $nameEn = trim((string)($data['name_en'] ?? $data['name'] ?? ''));
        $nameTr = trim((string)($data['name_tr'] ?? $data['name'] ?? ''));

        if ($nameEn === '' && $nameTr === '') {
            throw new \InvalidArgumentException('name zorunludur.');
        }

        if ($nameEn === '') $nameEn = $nameTr;
        if ($nameTr === '') $nameTr = $nameEn;

        // Convert to uppercase ASCII
        $nameEn = Helpers::toAsciiUppercase($nameEn);
        $nameTr = Helpers::toAsciiUppercase($nameTr);

        $stmt = $pdo->prepare("
            UPDATE discipline 
            SET name_en = :name_en, name_tr = :name_tr
            WHERE id = :id
        ");
        $stmt->execute([
            ':name_en' => $nameEn,
            ':name_tr' => $nameTr,
            ':id' => $id,
        ]);
    }

    // Delete discipline
    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare("DELETE FROM discipline WHERE id = :id")->execute([':id' => $id]);
    }
}
