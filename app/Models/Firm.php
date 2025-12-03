<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Firm
{
    // Tüm aktif firmalar (soft delete hariç)
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT * FROM companies WHERE deleted_at IS NULL ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Tek firma
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // UUID ile tek firma (opsiyonel)
    public static function findByUuid(PDO $pdo, string $uuid): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE uuid = :uuid LIMIT 1");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Benzersiz alan kontrolü
    public static function existsByUnique(PDO $pdo, string $field, string $value, ?int $excludeId = null): bool
    {
        $allowed = ['registration_no','mersis_no','tax_number'];
        if (!in_array($field, $allowed, true)) {
            throw new \InvalidArgumentException("Geçersiz alan: $field");
        }

        $sql = "SELECT 1 FROM companies WHERE $field = :val";
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
        $shortName = self::nullIfEmpty($d['short_name'] ?? null);
        $legalType = self::nullIfEmpty($d['legal_type'] ?? null);
        $registrationNo = self::nullIfEmpty($d['registration_no'] ?? null);
        $mersisNo = self::nullIfEmpty($d['mersis_no'] ?? null);
        $taxOffice = self::nullIfEmpty($d['tax_office'] ?? null);
        $taxNumber = self::nullIfEmpty($d['tax_number'] ?? null);

        $email = self::nullIfEmpty($d['email'] ?? null);
        $phone = self::nullIfEmpty($d['phone'] ?? null);
        $secondaryPhone = self::nullIfEmpty($d['secondary_phone'] ?? null);
        $fax = self::nullIfEmpty($d['fax'] ?? null);
        $website = self::nullIfEmpty($d['website'] ?? null);

        $address1 = self::nullIfEmpty($d['address_line1'] ?? null);
        $address2 = self::nullIfEmpty($d['address_line2'] ?? null);
        $city = self::nullIfEmpty($d['city'] ?? null);
        $state = self::nullIfEmpty($d['state_region'] ?? null);
        $postal = self::nullIfEmpty($d['postal_code'] ?? null);
        $country = self::nullIfEmpty($d['country_code'] ?? null);

        $lat = self::toNullableFloat($d['latitude'] ?? null);
        $lng = self::toNullableFloat($d['longitude'] ?? null);

        $industry = self::nullIfEmpty($d['industry'] ?? null);
        $status = self::nullIfEmpty($d['status'] ?? null) ?? 'active';
        $currency = self::nullIfEmpty($d['currency_code'] ?? null);
        $timezone = self::nullIfEmpty($d['timezone'] ?? null);

        $vatExempt = self::toBool($d['vat_exempt'] ?? false);
        $eInvoice  = self::toBool($d['e_invoice_enabled'] ?? false);
        $logoUrl = self::nullIfEmpty($d['logo_url'] ?? null);
        $notes = self::nullIfEmpty($d['notes'] ?? null);
        $isActive = self::toBool($d['is_active'] ?? true);

        $createdBy = self::toNullableInt($d['created_by'] ?? null);
        $updatedBy = self::toNullableInt($d['updated_by'] ?? null);

        $sql = "INSERT INTO companies (
            name, short_name, legal_type, registration_no, mersis_no, tax_office, tax_number,
            email, phone, secondary_phone, fax, website,
            address_line1, address_line2, city, state_region, postal_code, country_code,
            latitude, longitude,
            industry, status, currency_code, timezone,
            vat_exempt, e_invoice_enabled, logo_url, notes,
            is_active, created_by, updated_by
        ) VALUES (
            :name, :short_name, :legal_type, :registration_no, :mersis_no, :tax_office, :tax_number,
            :email, :phone, :secondary_phone, :fax, :website,
            :address_line1, :address_line2, :city, :state_region, :postal_code, :country_code,
            :latitude, :longitude,
            :industry, :status, :currency_code, :timezone,
            :vat_exempt, :e_invoice_enabled, :logo_url, :notes,
            :is_active, :created_by, :updated_by
        ) RETURNING id";

        $stmt = $pdo->prepare($sql);

        // Tip güvenli bind
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':short_name', $shortName);
        $stmt->bindValue(':legal_type', $legalType);
        $stmt->bindValue(':registration_no', $registrationNo);
        $stmt->bindValue(':mersis_no', $mersisNo);
        $stmt->bindValue(':tax_office', $taxOffice);
        $stmt->bindValue(':tax_number', $taxNumber);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':phone', $phone);
        $stmt->bindValue(':secondary_phone', $secondaryPhone);
        $stmt->bindValue(':fax', $fax);
        $stmt->bindValue(':website', $website);
        $stmt->bindValue(':address_line1', $address1);
        $stmt->bindValue(':address_line2', $address2);
        $stmt->bindValue(':city', $city);
        $stmt->bindValue(':state_region', $state);
        $stmt->bindValue(':postal_code', $postal);
        $stmt->bindValue(':country_code', $country);
        $stmt->bindValue(':latitude', $lat);
        $stmt->bindValue(':longitude', $lng);
        $stmt->bindValue(':industry', $industry);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':currency_code', $currency);
        $stmt->bindValue(':timezone', $timezone);
        $stmt->bindValue(':vat_exempt', $vatExempt, PDO::PARAM_BOOL);
        $stmt->bindValue(':e_invoice_enabled', $eInvoice, PDO::PARAM_BOOL);
        $stmt->bindValue(':logo_url', $logoUrl);
        $stmt->bindValue(':notes', $notes);
        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
        $stmt->bindValue(':created_by', $createdBy);
        $stmt->bindValue(':updated_by', $updatedBy);

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Güncelle
    public static function update(PDO $pdo, int $id, array $d): void
    {
        // Normalize
        $name = trim((string)($d['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('name zorunludur.');
        }

        $shortName = self::nullIfEmpty($d['short_name'] ?? null);
        $legalType = self::nullIfEmpty($d['legal_type'] ?? null);
        $registrationNo = self::nullIfEmpty($d['registration_no'] ?? null);
        $mersisNo = self::nullIfEmpty($d['mersis_no'] ?? null);
        $taxOffice = self::nullIfEmpty($d['tax_office'] ?? null);
        $taxNumber = self::nullIfEmpty($d['tax_number'] ?? null);

        $email = self::nullIfEmpty($d['email'] ?? null);
        $phone = self::nullIfEmpty($d['phone'] ?? null);
        $secondaryPhone = self::nullIfEmpty($d['secondary_phone'] ?? null);
        $fax = self::nullIfEmpty($d['fax'] ?? null);
        $website = self::nullIfEmpty($d['website'] ?? null);

        $address1 = self::nullIfEmpty($d['address_line1'] ?? null);
        $address2 = self::nullIfEmpty($d['address_line2'] ?? null);
        $city = self::nullIfEmpty($d['city'] ?? null);
        $state = self::nullIfEmpty($d['state_region'] ?? null);
        $postal = self::nullIfEmpty($d['postal_code'] ?? null);
        $country = self::nullIfEmpty($d['country_code'] ?? null);

        $lat = self::toNullableFloat($d['latitude'] ?? null);
        $lng = self::toNullableFloat($d['longitude'] ?? null);

        $industry = self::nullIfEmpty($d['industry'] ?? null);
        $status = self::nullIfEmpty($d['status'] ?? null) ?? 'active';
        $currency = self::nullIfEmpty($d['currency_code'] ?? null);
        $timezone = self::nullIfEmpty($d['timezone'] ?? null);

        $vatExempt = self::toBool($d['vat_exempt'] ?? false);
        $eInvoice  = self::toBool($d['e_invoice_enabled'] ?? false);
        $logoUrl = self::nullIfEmpty($d['logo_url'] ?? null);
        $notes = self::nullIfEmpty($d['notes'] ?? null);
        $isActive = self::toBool($d['is_active'] ?? true);

        $updatedBy = self::toNullableInt($d['updated_by'] ?? null);

        $sql = "UPDATE companies SET
            name = :name,
            short_name = :short_name,
            legal_type = :legal_type,
            registration_no = :registration_no,
            mersis_no = :mersis_no,
            tax_office = :tax_office,
            tax_number = :tax_number,
            email = :email,
            phone = :phone,
            secondary_phone = :secondary_phone,
            fax = :fax,
            website = :website,
            address_line1 = :address_line1,
            address_line2 = :address_line2,
            city = :city,
            state_region = :state_region,
            postal_code = :postal_code,
            country_code = :country_code,
            latitude = :latitude,
            longitude = :longitude,
            industry = :industry,
            status = :status,
            currency_code = :currency_code,
            timezone = :timezone,
            vat_exempt = :vat_exempt,
            e_invoice_enabled = :e_invoice_enabled,
            logo_url = :logo_url,
            notes = :notes,
            is_active = :is_active,
            updated_by = :updated_by
        WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        // Tip güvenli bind
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':short_name', $shortName);
        $stmt->bindValue(':legal_type', $legalType);
        $stmt->bindValue(':registration_no', $registrationNo);
        $stmt->bindValue(':mersis_no', $mersisNo);
        $stmt->bindValue(':tax_office', $taxOffice);
        $stmt->bindValue(':tax_number', $taxNumber);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':phone', $phone);
        $stmt->bindValue(':secondary_phone', $secondaryPhone);
        $stmt->bindValue(':fax', $fax);
        $stmt->bindValue(':website', $website);
        $stmt->bindValue(':address_line1', $address1);
        $stmt->bindValue(':address_line2', $address2);
        $stmt->bindValue(':city', $city);
        $stmt->bindValue(':state_region', $state);
        $stmt->bindValue(':postal_code', $postal);
        $stmt->bindValue(':country_code', $country);
        $stmt->bindValue(':latitude', $lat);
        $stmt->bindValue(':longitude', $lng);
        $stmt->bindValue(':industry', $industry);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':currency_code', $currency);
        $stmt->bindValue(':timezone', $timezone);
        $stmt->bindValue(':vat_exempt', $vatExempt, PDO::PARAM_BOOL);
        $stmt->bindValue(':e_invoice_enabled', $eInvoice, PDO::PARAM_BOOL);
        $stmt->bindValue(':logo_url', $logoUrl);
        $stmt->bindValue(':notes', $notes);
        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
        $stmt->bindValue(':updated_by', $updatedBy);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
    }

    // Hard delete yerine soft delete (önerilir)
    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare("UPDATE companies SET deleted_at = now() WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function deleteByUuid(PDO $pdo, string $uuid): void
    {
        $stmt = $pdo->prepare("UPDATE companies SET deleted_at = now() WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();
    }

    // Helpers
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
        // İstersen burada geçersiz sayı için exception fırlatabilirsin
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
