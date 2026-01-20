<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Bakiye
{
    // List all bakiye records
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM bakiye ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Find single record by id
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM bakiye WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Create new record
    public static function create(PDO $pdo, array $data): int
    {
        $sql = "INSERT INTO bakiye (
                    proje, tahakkuk_tarihi, vade_tarihi, cek_no, 
                    aciklama, aciklama2, aciklama3, tutar_try, 
                    cari_hesap_ismi, wb, ws, row_col, cost_code, 
                    dikkate_alinmayacaklar, usd_karsiligi, id_text, 
                    id_veriler, id_odeme_plan_satinalma_odeme_onay_listesi, 
                    not_field, not_ool_odeme_plani
                ) VALUES (
                    :proje, :tahakkuk_tarihi, :vade_tarihi, :cek_no,
                    :aciklama, :aciklama2, :aciklama3, :tutar_try,
                    :cari_hesap_ismi, :wb, :ws, :row_col, :cost_code,
                    :dikkate_alinmayacaklar, :usd_karsiligi, :id_text,
                    :id_veriler, :id_odeme_plan_satinalma_odeme_onay_listesi,
                    :not_field, :not_ool_odeme_plani
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':proje' => $data['proje'] ?? null,
            ':tahakkuk_tarihi' => $data['tahakkuk_tarihi'] ?? null,
            ':vade_tarihi' => $data['vade_tarihi'] ?? null,
            ':cek_no' => $data['cek_no'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':aciklama2' => $data['aciklama2'] ?? null,
            ':aciklama3' => $data['aciklama3'] ?? null,
            ':tutar_try' => $data['tutar_try'] ?? null,
            ':cari_hesap_ismi' => $data['cari_hesap_ismi'] ?? null,
            ':wb' => $data['wb'] ?? null,
            ':ws' => $data['ws'] ?? null,
            ':row_col' => $data['row_col'] ?? null,
            ':cost_code' => $data['cost_code'] ?? null,
            ':dikkate_alinmayacaklar' => $data['dikkate_alinmayacaklar'] ?? null,
            ':usd_karsiligi' => $data['usd_karsiligi'] ?? null,
            ':id_text' => $data['id_text'] ?? null,
            ':id_veriler' => $data['id_veriler'] ?? null,
            ':id_odeme_plan_satinalma_odeme_onay_listesi' => $data['id_odeme_plan_satinalma_odeme_onay_listesi'] ?? null,
            ':not_field' => $data['not_field'] ?? null,
            ':not_ool_odeme_plani' => $data['not_ool_odeme_plani'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    // Update record
    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "UPDATE bakiye SET
                    proje = :proje,
                    tahakkuk_tarihi = :tahakkuk_tarihi,
                    vade_tarihi = :vade_tarihi,
                    cek_no = :cek_no,
                    aciklama = :aciklama,
                    aciklama2 = :aciklama2,
                    aciklama3 = :aciklama3,
                    tutar_try = :tutar_try,
                    cari_hesap_ismi = :cari_hesap_ismi,
                    wb = :wb,
                    ws = :ws,
                    row_col = :row_col,
                    cost_code = :cost_code,
                    dikkate_alinmayacaklar = :dikkate_alinmayacaklar,
                    usd_karsiligi = :usd_karsiligi,
                    id_text = :id_text,
                    id_veriler = :id_veriler,
                    id_odeme_plan_satinalma_odeme_onay_listesi = :id_odeme_plan_satinalma_odeme_onay_listesi,
                    not_field = :not_field,
                    not_ool_odeme_plani = :not_ool_odeme_plani,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':proje' => $data['proje'] ?? null,
            ':tahakkuk_tarihi' => $data['tahakkuk_tarihi'] ?? null,
            ':vade_tarihi' => $data['vade_tarihi'] ?? null,
            ':cek_no' => $data['cek_no'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':aciklama2' => $data['aciklama2'] ?? null,
            ':aciklama3' => $data['aciklama3'] ?? null,
            ':tutar_try' => $data['tutar_try'] ?? null,
            ':cari_hesap_ismi' => $data['cari_hesap_ismi'] ?? null,
            ':wb' => $data['wb'] ?? null,
            ':ws' => $data['ws'] ?? null,
            ':row_col' => $data['row_col'] ?? null,
            ':cost_code' => $data['cost_code'] ?? null,
            ':dikkate_alinmayacaklar' => $data['dikkate_alinmayacaklar'] ?? null,
            ':usd_karsiligi' => $data['usd_karsiligi'] ?? null,
            ':id_text' => $data['id_text'] ?? null,
            ':id_veriler' => $data['id_veriler'] ?? null,
            ':id_odeme_plan_satinalma_odeme_onay_listesi' => $data['id_odeme_plan_satinalma_odeme_onay_listesi'] ?? null,
            ':not_field' => $data['not_field'] ?? null,
            ':not_ool_odeme_plani' => $data['not_ool_odeme_plani'] ?? null,
        ]);
    }

    // Delete record
    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM bakiye WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
