<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Project
{
    // Tüm aktif projeler (soft delete hariç)
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM project WHERE deleted_at IS NULL ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Tek proje
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM project WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // UUID ile tek proje (opsiyonel)
    public static function findByUuid(PDO $pdo, string $uuid): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM project WHERE uuid = :uuid LIMIT 1");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Benzersiz alan kontrolü (gerekirse uyarlayın)
    // Örn. name + company_id benzersiz olsun istiyorsanız, bu metodu genişletebilirsiniz.
    public static function existsByUnique(PDO $pdo, string $field, string $value, ?int $excludeId = null): bool
    {
        $allowed = ['project_path']; // ihtiyaç halinde ['name','project_path'] vb. ekleyin
        if (!in_array($field, $allowed, true)) {
            throw new \InvalidArgumentException("Geçersiz alan: $field");
        }

        $sql = "SELECT 1 FROM project WHERE $field = :val";
        $params = [':val' => $value];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // Oluştur
    public static function create(PDO $pdo, array $d): int
    {
        // Zorunlu alan
        $name = trim((string)($d['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('name zorunludur.');
        }

        // Normalizasyon
        $shortName   = self::nullIfEmpty($d['short_name'] ?? null);
        $projectPath = self::nullIfEmpty($d['project_path'] ?? null);

        $address1 = self::nullIfEmpty($d['address_line1'] ?? null);
        $address2 = self::nullIfEmpty($d['address_line2'] ?? null);
        $city     = self::nullIfEmpty($d['city'] ?? null);
        $state    = self::nullIfEmpty($d['state_region'] ?? null);
        $postal   = self::nullIfEmpty($d['postal_code'] ?? null);
        $country  = self::nullIfEmpty($d['country_code'] ?? null);

        $companyId = self::toNullableInt($d['company_id'] ?? null);

        $startDate = self::nullIfEmpty($d['start_date'] ?? null); // 'YYYY-MM-DD' vb.
        $endDate   = self::nullIfEmpty($d['end_date'] ?? null);

        $budget    = self::toNullableFloat($d['budget'] ?? null);
        $imageUrl  = self::nullIfEmpty($d['image_url'] ?? null);
        $notes     = self::nullIfEmpty($d['notes'] ?? null);

        $status    = self::nullIfEmpty($d['status'] ?? null) ?? 'active';
        $currency  = self::nullIfEmpty($d['currency_code'] ?? null);
        $timezone  = self::nullIfEmpty($d['timezone'] ?? null);

        $isActive  = self::toBool($d['is_active'] ?? true);

        $createdBy = self::toNullableInt($d['created_by'] ?? null);
        $updatedBy = self::toNullableInt($d['updated_by'] ?? null);

        $sql = "INSERT INTO project (
            name, short_name, project_path,
            address_line1, address_line2, city, state_region, postal_code, country_code,
            company_id, start_date, end_date, budget, image_url, notes,
            status, currency_code, timezone,
            is_active, created_by, updated_by
        ) VALUES (
            :name, :short_name, :project_path,
            :address_line1, :address_line2, :city, :state_region, :postal_code, :country_code,
            :company_id, :start_date, :end_date, :budget, :image_url, :notes,
            :status, :currency_code, :timezone,
            :is_active, :created_by, :updated_by
        ) RETURNING id";

        $stmt = $pdo->prepare($sql);

        // Tip güvenli bind
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':short_name', $shortName);
        $stmt->bindValue(':project_path', $projectPath);

        $stmt->bindValue(':address_line1', $address1);
        $stmt->bindValue(':address_line2', $address2);
        $stmt->bindValue(':city', $city);
        $stmt->bindValue(':state_region', $state);
        $stmt->bindValue(':postal_code', $postal);
        $stmt->bindValue(':country_code', $country);

        $stmt->bindValue(':company_id', $companyId);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->bindValue(':budget', $budget);
        $stmt->bindValue(':image_url', $imageUrl);
        $stmt->bindValue(':notes', $notes);

        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':currency_code', $currency);
        $stmt->bindValue(':timezone', $timezone);

        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);

        $stmt->bindValue(':created_by', $createdBy);
        $stmt->bindValue(':updated_by', $updatedBy);

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Güncelle
    public static function update(PDO $pdo, int $id, array $d): void
    {
        // Zorunlu alan
        $name = trim((string)($d['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('name zorunludur.');
        }

        // Normalize
        $shortName   = self::nullIfEmpty($d['short_name'] ?? null);
        $projectPath = self::nullIfEmpty($d['project_path'] ?? null);

        $address1 = self::nullIfEmpty($d['address_line1'] ?? null);
        $address2 = self::nullIfEmpty($d['address_line2'] ?? null);
        $city     = self::nullIfEmpty($d['city'] ?? null);
        $state    = self::nullIfEmpty($d['state_region'] ?? null);
        $postal   = self::nullIfEmpty($d['postal_code'] ?? null);
        $country  = self::nullIfEmpty($d['country_code'] ?? null);

        $companyId = self::toNullableInt($d['company_id'] ?? null);

        $startDate = self::nullIfEmpty($d['start_date'] ?? null);
        $endDate   = self::nullIfEmpty($d['end_date'] ?? null);

        $budget    = self::toNullableFloat($d['budget'] ?? null);
        $imageUrl  = self::nullIfEmpty($d['image_url'] ?? null);
        $notes     = self::nullIfEmpty($d['notes'] ?? null);

        $status    = self::nullIfEmpty($d['status'] ?? null) ?? 'active';
        $currency  = self::nullIfEmpty($d['currency_code'] ?? null);
        $timezone  = self::nullIfEmpty($d['timezone'] ?? null);

        $isActive  = self::toBool($d['is_active'] ?? true);

        $updatedBy = self::toNullableInt($d['updated_by'] ?? null);

        $sql = "UPDATE project SET
            name = :name,
            short_name = :short_name,
            project_path = :project_path,
            address_line1 = :address_line1,
            address_line2 = :address_line2,
            city = :city,
            state_region = :state_region,
            postal_code = :postal_code,
            country_code = :country_code,
            company_id = :company_id,
            start_date = :start_date,
            end_date = :end_date,
            budget = :budget,
            image_url = :image_url,
            notes = :notes,
            status = :status,
            currency_code = :currency_code,
            timezone = :timezone,
            is_active = :is_active,
            updated_by = :updated_by
        WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        // Tip güvenli bind
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':short_name', $shortName);
        $stmt->bindValue(':project_path', $projectPath);

        $stmt->bindValue(':address_line1', $address1);
        $stmt->bindValue(':address_line2', $address2);
        $stmt->bindValue(':city', $city);
        $stmt->bindValue(':state_region', $state);
        $stmt->bindValue(':postal_code', $postal);
        $stmt->bindValue(':country_code', $country);

        $stmt->bindValue(':company_id', $companyId);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->bindValue(':budget', $budget);
        $stmt->bindValue(':image_url', $imageUrl);
        $stmt->bindValue(':notes', $notes);

        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':currency_code', $currency);
        $stmt->bindValue(':timezone', $timezone);

        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);

        $stmt->bindValue(':updated_by', $updatedBy);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
    }

    // Soft delete
    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare("UPDATE project SET deleted_at = now() WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function deleteByUuid(PDO $pdo, string $uuid): void
    {
        $stmt = $pdo->prepare("UPDATE project SET deleted_at = now() WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();
    }

    // Helpers (Firm ile aynı)

    private static function toBool($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        $s = strtolower(trim((string)$v));
        if ($s === '' || $s === '0' || $s === 'false' || $s === 'no' || $s === 'off' || $s === 'hayir' || $s === 'hayır') {
            return false;
        }
        if ($s === '1' || $s === 'true' || $s === 'yes' || $s === 'on' || $s === 'evet') {
            return true;
        }
        return false;
    }

    private static function nullIfEmpty($v): mixed
    {
        if ($v === null) {
            return null;
        }
        if (is_string($v) && trim($v) === '') {
            return null;
        }
        return $v;
    }

    private static function toNullableFloat($v): ?float
    {
        $v = self::nullIfEmpty($v);
        if ($v === null) {
            return null;
        }
        return is_numeric($v) ? (float)$v : null;
    }

    private static function toNullableInt($v): ?int
    {
        $v = self::nullIfEmpty($v);
        if ($v === null) {
            return null;
        }
        return is_numeric($v) ? (int)$v : null;
    }
}
