<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Tutanak
{
    /**
     * Get the birim column name based on current language
     */
    private static function getBirimColumn(): string
    {
        return currentLanguage() === 'en' ? 'u.EN' : 'u.TR';
    }

    public static function all(PDO $pdo, ?int $projectId = null): array
    {
        $birimCol = self::getBirimColumn();

        if ($projectId) {
            $sql = "
                SELECT t.*, p.name as project_name, COALESCE($birimCol, '') as birim_name
                FROM tutanak t
                LEFT JOIN project p ON t.project_id = p.id
                LEFT JOIN units u ON t.birim_id = u.id
                WHERE t.project_id = :project_id AND t.deleted_at IS NULL
                ORDER BY t.tarih DESC, t.tutanak_no DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':project_id' => $projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $sql = "
            SELECT t.*, p.name as project_name, COALESCE($birimCol, '') as birim_name
            FROM tutanak t
            LEFT JOIN project p ON t.project_id = p.id
            LEFT JOIN units u ON t.birim_id = u.id
            WHERE t.deleted_at IS NULL
            ORDER BY t.tarih DESC, t.tutanak_no DESC
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $birimCol = self::getBirimColumn();

        $sql = "
            SELECT t.*, p.name as project_name, COALESCE($birimCol, '') as birim_name
            FROM tutanak t
            LEFT JOIN project p ON t.project_id = p.id
            LEFT JOIN units u ON t.birim_id = u.id
            WHERE t.id = :id AND t.deleted_at IS NULL LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByTitle(PDO $pdo, string $title): ?array
    {
        $stmt = $pdo->prepare("
            SELECT * FROM tutanak 
            WHERE tutanak_title = :title AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute([':title' => $title]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): int
    {
        $sql = "
            INSERT INTO tutanak (
                tutanak_title, project_id, tutanak_no, malzeme_yevmiye_ceza,
                birim_id, birim_fiyat, miktar, odeme_yapilacak_firma,
                tutar, kesinti_yapilacak_firma, not_text, tur, tarih, konu,
                pdf_path, created_by
            ) VALUES (
                :title, :project_id, :no, :malzeme,
                :birim_id, :birim_fiyat, :miktar, :odeme_firma,
                :tutar, :kesinti_firma, :not, :tur, :tarih, :konu,
                :pdf_path, :created_by
            )
            RETURNING id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['tutanak_title'] ?? '',
            ':project_id' => $data['project_id'] ?? null,
            ':no' => $data['tutanak_no'] ?? '',
            ':malzeme' => $data['malzeme_yevmiye_ceza'] ?? null,
            ':birim_id' => $data['birim_id'] ?? null,
            ':birim_fiyat' => $data['birim_fiyat'] ?? 0,
            ':miktar' => $data['miktar'] ?? 0,
            ':odeme_firma' => $data['odeme_yapilacak_firma'] ?? null,
            ':tutar' => $data['tutar'] ?? 0,
            ':kesinti_firma' => $data['kesinti_yapilacak_firma'] ?? null,
            ':not' => $data['not_text'] ?? null,
            ':tur' => $data['tur'] ?? null,
            ':tarih' => $data['tarih'] ?? null,
            ':konu' => $data['konu'] ?? null,
            ':pdf_path' => $data['pdf_path'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function update(PDO $pdo, int $id, array $data): bool
    {
        $sql = "
            UPDATE tutanak SET
                tutanak_title = :title,
                project_id = :project_id,
                tutanak_no = :no,
                malzeme_yevmiye_ceza = :malzeme,
                birim_id = :birim_id,
                birim_fiyat = :birim_fiyat,
                miktar = :miktar,
                odeme_yapilacak_firma = :odeme_firma,
                tutar = :tutar,
                kesinti_yapilacak_firma = :kesinti_firma,
                not_text = :not,
                tur = :tur,
                tarih = :tarih,
                konu = :konu,
                pdf_path = :pdf_path,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['tutanak_title'] ?? '',
            ':project_id' => $data['project_id'] ?? null,
            ':no' => $data['tutanak_no'] ?? '',
            ':malzeme' => $data['malzeme_yevmiye_ceza'] ?? null,
            ':birim_id' => $data['birim_id'] ?? null,
            ':birim_fiyat' => $data['birim_fiyat'] ?? 0,
            ':miktar' => $data['miktar'] ?? 0,
            ':odeme_firma' => $data['odeme_yapilacak_firma'] ?? null,
            ':tutar' => $data['tutar'] ?? 0,
            ':kesinti_firma' => $data['kesinti_yapilacak_firma'] ?? null,
            ':not' => $data['not_text'] ?? null,
            ':tur' => $data['tur'] ?? null,
            ':tarih' => $data['tarih'] ?? null,
            ':konu' => $data['konu'] ?? null,
            ':pdf_path' => $data['pdf_path'] ?? null,
        ]);
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("UPDATE tutanak SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function restore(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("UPDATE tutanak SET deleted_at = NULL WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function generateTitle(string $projectName, string $odemeFirma, string $kesintiFirma, string $tarih): string
    {
        // TUT_KUNVIV_RGGAYRIM_RBGLOBAL_20250120_00 format
        $projShort = strtoupper(substr(str_replace(' ', '', $projectName), 0, 6));
        $odemeShort = strtoupper(substr(str_replace(' ', '', $kesintiFirma), 0, 7)); // Kesinti first
        $kesShort = strtoupper(substr(str_replace(' ', '', $odemeFirma), 0, 8)); // Odeme second

        // Format: TUT_PROJNAME_FIRM1_FIRM2_YYYYMMDD_SEQ
        return "TUT_{$projShort}_{$odemeShort}_{$kesShort}_{$tarih}_00";
    }

    public static function getNextSequence(PDO $pdo, string $baseTitle): string
    {
        $prefix = substr($baseTitle, 0, -2); // Remove last 2 chars (_00, _01, etc)

        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(tutanak_title FROM LENGTH(:prefix) + 2) AS INTEGER)) as max_seq
            FROM tutanak
            WHERE tutanak_title LIKE :pattern
        ");
        $stmt->execute([
            ':prefix' => $prefix,
            ':pattern' => $prefix . '_%'
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextSeq = ($result['max_seq'] ?? -1) + 1;

        return $prefix . '_' . str_pad((string)$nextSeq, 2, '0', STR_PAD_LEFT);
    }
}
