<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Barter
{
    // List all barter records
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM barter ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Count all records
    public static function count(PDO $pdo): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM barter";
        $result = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        return (int)($result['cnt'] ?? 0);
    }

    // Find single record by id
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM barter WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Create new record
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "INSERT INTO barter (
                    proje, cost_code, aciklama, barter_tutari, barter_currency,
                    barter_gerceklesen, barter_planlanan_oran, barter_planlanan_tutar,
                    sozlesme_tarihi, kur, usd_karsiligi, tutar_try,
                    not_field, path, yuklenici, karsi_hesap_ismi
                ) VALUES (
                    :proje, :cost_code, :aciklama, :barter_tutari, :barter_currency,
                    :barter_gerceklesen, :barter_planlanan_oran, :barter_planlanan_tutar,
                    :sozlesme_tarihi, :kur, :usd_karsiligi, :tutar_try,
                    :not_field, :path, :yuklenici, :karsi_hesap_ismi
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':proje' => $data['proje'] ?? null,
            ':cost_code' => $data['cost_code'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':barter_tutari' => !empty($data['barter_tutari']) ? (float)$data['barter_tutari'] : null,
            ':barter_currency' => $data['barter_currency'] ?? null,
            ':barter_gerceklesen' => !empty($data['barter_gerceklesen']) ? (float)$data['barter_gerceklesen'] : null,
            ':barter_planlanan_oran' => $data['barter_planlanan_oran'] ?? null,
            ':barter_planlanan_tutar' => !empty($data['barter_planlanan_tutar']) ? (float)$data['barter_planlanan_tutar'] : null,
            ':sozlesme_tarihi' => !empty($data['sozlesme_tarihi']) ? $data['sozlesme_tarihi'] : null,
            ':kur' => !empty($data['kur']) ? (float)$data['kur'] : null,
            ':usd_karsiligi' => !empty($data['usd_karsiligi']) ? (float)$data['usd_karsiligi'] : null,
            ':tutar_try' => !empty($data['tutar_try']) ? (float)$data['tutar_try'] : null,
            ':not_field' => $data['not_field'] ?? null,
            ':path' => $data['path'] ?? null,
            ':yuklenici' => $data['yuklenici'] ?? null,
            ':karsi_hesap_ismi' => $data['karsi_hesap_ismi'] ?? null
        ]);

        return (int)$pdo->lastInsertId();
    }

    // Update record
    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "UPDATE barter SET
                    proje = :proje,
                    cost_code = :cost_code,
                    aciklama = :aciklama,
                    barter_tutari = :barter_tutari,
                    barter_currency = :barter_currency,
                    barter_gerceklesen = :barter_gerceklesen,
                    barter_planlanan_oran = :barter_planlanan_oran,
                    barter_planlanan_tutar = :barter_planlanan_tutar,
                    sozlesme_tarihi = :sozlesme_tarihi,
                    kur = :kur,
                    usd_karsiligi = :usd_karsiligi,
                    tutar_try = :tutar_try,
                    not_field = :not_field,
                    path = :path,
                    yuklenici = :yuklenici,
                    karsi_hesap_ismi = :karsi_hesap_ismi,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':proje' => $data['proje'] ?? null,
            ':cost_code' => $data['cost_code'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':barter_tutari' => !empty($data['barter_tutari']) ? (float)$data['barter_tutari'] : null,
            ':barter_currency' => $data['barter_currency'] ?? null,
            ':barter_gerceklesen' => !empty($data['barter_gerceklesen']) ? (float)$data['barter_gerceklesen'] : null,
            ':barter_planlanan_oran' => $data['barter_planlanan_oran'] ?? null,
            ':barter_planlanan_tutar' => !empty($data['barter_planlanan_tutar']) ? (float)$data['barter_planlanan_tutar'] : null,
            ':sozlesme_tarihi' => !empty($data['sozlesme_tarihi']) ? $data['sozlesme_tarihi'] : null,
            ':kur' => !empty($data['kur']) ? (float)$data['kur'] : null,
            ':usd_karsiligi' => !empty($data['usd_karsiligi']) ? (float)$data['usd_karsiligi'] : null,
            ':tutar_try' => !empty($data['tutar_try']) ? (float)$data['tutar_try'] : null,
            ':not_field' => $data['not_field'] ?? null,
            ':path' => $data['path'] ?? null,
            ':yuklenici' => $data['yuklenici'] ?? null,
            ':karsi_hesap_ismi' => $data['karsi_hesap_ismi'] ?? null
        ]);
    }

    // Delete record
    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM barter WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
