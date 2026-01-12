<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Core\Helpers;

class DisciplineBranch
{
    // Get all branches for a discipline
    public static function allByDiscipline(PDO $pdo, int $disciplineId): array
    {
        $stmt = $pdo->prepare("
            SELECT id, discipline_id, name_en, name_tr 
            FROM discipline_branch 
            WHERE discipline_id = :discipline_id 
            ORDER BY name_tr ASC
        ");
        $stmt->execute([':discipline_id' => $disciplineId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Get single branch
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT id, discipline_id, name_en, name_tr 
            FROM discipline_branch 
            WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Create new branch
    public static function create(PDO $pdo, array $data): int
    {
        $disciplineId = (int)($data['discipline_id'] ?? 0);
        if ($disciplineId <= 0) {
            throw new \InvalidArgumentException('discipline_id zorunludur.');
        }

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
            INSERT INTO discipline_branch (discipline_id, name_en, name_tr)
            VALUES (:discipline_id, :name_en, :name_tr)
            RETURNING id
        ");
        $stmt->execute([
            ':discipline_id' => $disciplineId,
            ':name_en' => $nameEn,
            ':name_tr' => $nameTr,
        ]);
        return (int)$stmt->fetchColumn();
    }

    // Update branch
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
            UPDATE discipline_branch 
            SET name_en = :name_en, name_tr = :name_tr
            WHERE id = :id
        ");
        $stmt->execute([
            ':name_en' => $nameEn,
            ':name_tr' => $nameTr,
            ':id' => $id,
        ]);
    }

    // Delete branch
    public static function delete(PDO $pdo, int $id): void
    {
        $pdo->prepare("DELETE FROM discipline_branch WHERE id = :id")->execute([':id' => $id]);
    }
}
