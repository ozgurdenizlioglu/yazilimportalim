<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Tevkifat
{
    // List all tevkifat records
    public static function all(PDO $pdo, $project = null): array
    {
        $sql = 'SELECT * FROM tevkifat';
        $params = [];

        if ($project && $project !== '*') {
            $sql .= ' WHERE proje = :proje';
            $params[':proje'] = $project;
        }

        $sql .= ' ORDER BY tarih DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Find single record by id
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM tevkifat WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Create new record
    public static function create(PDO $pdo, array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($i) => "?", range(0, count($fields) - 1));

        $sql = 'INSERT INTO tevkifat (' . implode(', ', $fields) . ') 
                VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $pdo->prepare($sql);

        // Prepare params with proper type casting
        $params = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['vergi_matrahı', 'kdv_orani', 'tevkifat', 'tevkifat_orani', 'toplam', 'kdv_dahil', 'tevkifat_usd'])) {
                $params[] = $value !== '' ? (float)$value : null;
            } elseif ($key === 'tarih') {
                $params[] = $value !== '' ? $value : null;
            } else {
                $params[] = $value;
            }
        }

        $stmt->execute($params);
        return (int)$pdo->lastInsertId();
    }

    // Update record
    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $keys = array_keys($data);
        $setClauses = array_map(fn($key) => "$key = ?", $keys);

        $sql = 'UPDATE tevkifat SET ' . implode(', ', $setClauses) . ' WHERE id = ?';

        $stmt = $pdo->prepare($sql);

        $params = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['vergi_matrahı', 'kdv_orani', 'tevkifat', 'tevkifat_orani', 'toplam', 'kdv_dahil', 'tevkifat_usd'])) {
                $params[] = $value !== '' ? (float)$value : null;
            } elseif ($key === 'tarih') {
                $params[] = $value !== '' ? $value : null;
            } else {
                $params[] = $value;
            }
        }
        $params[] = $id; // Add id as last parameter

        return $stmt->execute($params);
    }

    // Delete record
    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM tevkifat WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Delete all records
    public static function deleteAll(PDO $pdo): bool
    {
        return $pdo->exec('DELETE FROM tevkifat') !== false;
    }

    // Bulk insert
    public static function bulkInsert(PDO $pdo, array $records): bool
    {
        $pdo->beginTransaction();
        try {
            foreach ($records as $record) {
                self::create($pdo, $record);
            }
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
