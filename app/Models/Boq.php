<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Boq
{
    public static function all(PDO $pdo, ?string $projectId = null): array
    {
        if ($projectId) {
            $stmt = $pdo->prepare("
                SELECT * FROM boq 
                WHERE project = :project_id 
                ORDER BY id DESC
            ");
            $stmt->execute([':project_id' => $projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $sql = "SELECT * FROM boq ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM boq WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByGuid(PDO $pdo, string $guid): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM boq WHERE id_text = :guid LIMIT 1");
        $stmt->execute([':guid' => $guid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(PDO $pdo, array $data): int
    {
        // Map new field names to existing table columns
        $sql = "
            INSERT INTO boq (
                project, drawing, coor, layer, length, size1, size2, area, 
                type_name, height, member_type, folder, handle, id_text, 
                poz_text, boq_beton, boq_formwork, boq_rebar, block, floor, 
                poz_cizim, poz_text2
            ) VALUES (
                :project, :drawing, :coor, :layer, :length, :size1, :size2, :area,
                :type_name, :height, :member_type, :folder, :handle, :id_text,
                :poz_text, :boq_beton, :boq_formwork, :boq_rebar, :block, :floor,
                :poz_cizim, :poz_text2
            )
            RETURNING id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project' => $data['project'] ?? $data['project_id'] ?? null,
            ':drawing' => $data['drawing'] ?? $data['dwg_filename'] ?? $data[0] ?? '',
            ':coor' => $data['coor'] ?? $data['coordinates'] ?? $data[3] ?? '',
            ':layer' => $data['layer'] ?? $data['layer_name'] ?? $data[4] ?? '',
            ':length' => $data['length'] ?? $data[5] ?? 0,
            ':size1' => $data['size1'] ?? $data[6] ?? 0,
            ':size2' => $data['size2'] ?? $data[7] ?? 0,
            ':area' => $data['area'] ?? $data[8] ?? 0,
            ':type_name' => $data['type_name'] ?? $data[9] ?? '',
            ':height' => $data['height'] ?? $data[10] ?? 0,
            ':member_type' => $data['member_type'] ?? $data['tur_yapi_elemani'] ?? $data[14] ?? '',
            ':folder' => $data['folder'] ?? $data['file_path'] ?? $data[15] ?? '',
            ':handle' => $data['handle'] ?? $data[16] ?? '',
            ':id_text' => $data['id_text'] ?? $data['guid'] ?? $data[17] ?? '',
            ':poz_text' => $data['poz_text'] ?? $data[19] ?? '',
            ':boq_beton' => $data['boq_beton'] ?? $data['metraj_beton'] ?? $data[21] ?? 0,
            ':boq_formwork' => $data['boq_formwork'] ?? $data['metraj_kalip'] ?? $data[22] ?? 0,
            ':boq_rebar' => $data['boq_rebar'] ?? $data['metraj_donati'] ?? $data[23] ?? 0,
            ':block' => $data['block'] ?? $data['blok'] ?? $data[25] ?? '',
            ':floor' => $data['floor'] ?? $data['kat'] ?? $data[27] ?? null,
            ':poz_cizim' => $data['poz_cizim'] ?? $data['poz'] ?? $data[11] ?? '',
            ':poz_text2' => $data['poz_text2'] ?? $data['text_content'] ?? $data[18] ?? '',
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function delete(PDO $pdo, string $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM boq WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function deleteByProject(PDO $pdo, string $projectId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM boq WHERE project = :project_id");
        return $stmt->execute([':project_id' => $projectId]);
    }
}
