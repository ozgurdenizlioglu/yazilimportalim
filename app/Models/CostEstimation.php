<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class CostEstimation
{
    // List all cost estimation records
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM costestimation ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Find single record by id
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM costestimation WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Create new record
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "INSERT INTO costestimation (
                    proje, cost_code, aciklama, tur, birim_maliyet,
                    currency, date, kur, birim, kapsam,
                    tutar_try_kdv_haric, kdv_orani, tutar_try_kdv_dahil,
                    not_field, path, yuklenici, karsi_hesap_ismi, sozlesme_durumu
                ) VALUES (
                    :proje, :cost_code, :aciklama, :tur, :birim_maliyet,
                    :currency, :date, :kur, :birim, :kapsam,
                    :tutar_try_kdv_haric, :kdv_orani, :tutar_try_kdv_dahil,
                    :not_field, :path, :yuklenici, :karsi_hesap_ismi, :sozlesme_durumu
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':proje' => $data['proje'] ?? null,
            ':cost_code' => $data['cost_code'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':tur' => $data['tur'] ?? null,
            ':birim_maliyet' => $data['birim_maliyet'] ?? null,
            ':currency' => $data['currency'] ?? null,
            ':date' => $data['date'] ?? null,
            ':kur' => $data['kur'] ?? null,
            ':birim' => $data['birim'] ?? null,
            ':kapsam' => $data['kapsam'] ?? null,
            ':tutar_try_kdv_haric' => $data['tutar_try_kdv_haric'] ?? null,
            ':kdv_orani' => $data['kdv_orani'] ?? null,
            ':tutar_try_kdv_dahil' => $data['tutar_try_kdv_dahil'] ?? null,
            ':not_field' => $data['not_field'] ?? null,
            ':path' => $data['path'] ?? null,
            ':yuklenici' => $data['yuklenici'] ?? null,
            ':karsi_hesap_ismi' => $data['karsi_hesap_ismi'] ?? null,
            ':sozlesme_durumu' => $data['sozlesme_durumu'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    // Update record
    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "UPDATE costestimation SET
                    proje = :proje,
                    cost_code = :cost_code,
                    aciklama = :aciklama,
                    tur = :tur,
                    birim_maliyet = :birim_maliyet,
                    currency = :currency,
                    date = :date,
                    kur = :kur,
                    birim = :birim,
                    kapsam = :kapsam,
                    tutar_try_kdv_haric = :tutar_try_kdv_haric,
                    kdv_orani = :kdv_orani,
                    tutar_try_kdv_dahil = :tutar_try_kdv_dahil,
                    not_field = :not_field,
                    path = :path,
                    yuklenici = :yuklenici,
                    karsi_hesap_ismi = :karsi_hesap_ismi,
                    sozlesme_durumu = :sozlesme_durumu,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':proje' => $data['proje'] ?? null,
            ':cost_code' => $data['cost_code'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':tur' => $data['tur'] ?? null,
            ':birim_maliyet' => $data['birim_maliyet'] ?? null,
            ':currency' => $data['currency'] ?? null,
            ':date' => $data['date'] ?? null,
            ':kur' => $data['kur'] ?? null,
            ':birim' => $data['birim'] ?? null,
            ':kapsam' => $data['kapsam'] ?? null,
            ':tutar_try_kdv_haric' => $data['tutar_try_kdv_haric'] ?? null,
            ':kdv_orani' => $data['kdv_orani'] ?? null,
            ':tutar_try_kdv_dahil' => $data['tutar_try_kdv_dahil'] ?? null,
            ':not_field' => $data['not_field'] ?? null,
            ':path' => $data['path'] ?? null,
            ':yuklenici' => $data['yuklenici'] ?? null,
            ':karsi_hesap_ismi' => $data['karsi_hesap_ismi'] ?? null,
            ':sozlesme_durumu' => $data['sozlesme_durumu'] ?? null,
        ]);
    }

    // Delete record
    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM costestimation WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
