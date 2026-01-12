<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Contract
{
    // Tek noktadan yönetilen para birimleri
    private const ALLOWED_CURRENCIES = ['TRY', 'USD', 'EUR'];
    private const DEFAULT_CURRENCY = 'TRY';

    // Liste (deleted_at IS NULL)
    public static function all(PDO $pdo): array
    {
        $sql = "SELECT
                    c.*,
                    con.name AS contractor_name,
                    sub.name AS subcontractor_name,
                    p.name AS project_name,
                    c.currency_code AS currency_code
                FROM contract c
                LEFT JOIN companies con ON con.id = c.contractor_company_id
                LEFT JOIN companies sub ON sub.id = c.subcontractor_company_id
                LEFT JOIN project p ON p.id = c.project_id
                WHERE c.deleted_at IS NULL
                ORDER BY c.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Tek sözleşme
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT
                 c.*,
                 con.name AS contractor_name,
                 sub.name AS subcontractor_name,
                 p.name AS project_name,
                 c.currency_code AS currency_code
             FROM contract c
             LEFT JOIN companies con ON con.id = c.contractor_company_id
             LEFT JOIN companies sub ON sub.id = c.subcontractor_company_id
             LEFT JOIN project p ON p.id = c.project_id
             WHERE c.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // UUID ile
    public static function findByUuid(PDO $pdo, string $uuid): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM contract WHERE uuid = :uuid LIMIT 1");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Oluştur
    public static function create(PDO $pdo, array $d): int
    {
        $contractor = self::toInt($d['contractor_company_id'] ?? null);
        $subcontractor = self::toInt($d['subcontractor_company_id'] ?? null);
        $contractDate = self::nullIfEmpty($d['contract_date'] ?? null);

        if (!$contractor || !$subcontractor || !$contractDate) {
            throw new \InvalidArgumentException('contractor_company_id, subcontractor_company_id ve contract_date zorunludur.');
        }

        $subject = self::nullIfEmpty($d['subject'] ?? null);
        $projectId = self::toNullableInt($d['project_id'] ?? null);
        $disciplineId = self::toNullableInt($d['discipline_id'] ?? null);
        $branchId = self::toNullableInt($d['branch_id'] ?? null);
        $title = self::nullIfEmpty($d['contract_title'] ?? null);
        $amount = self::toNullableNumeric($d['amount'] ?? null);

        // currency_code: gelmezse DEFAULT_CURRENCY ata; gelirse normalize et ve doğrula
        $currencyCode = strtoupper(trim((string)($d['currency_code'] ?? '')));
        if ($currencyCode === '') {
            $currencyCode = self::DEFAULT_CURRENCY;
        }
        self::assertCurrency($currencyCode);

        $amountInWords = self::nullIfEmpty($d['amount_in_words'] ?? null);
        $endDate = self::nullIfEmpty($d['end_date'] ?? null);
        $isActive = self::toBool($d['is_active'] ?? true);
        $createdBy = self::toNullableInt($d['created_by'] ?? null);
        $updatedBy = self::toNullableInt($d['updated_by'] ?? null);

        $sql = "INSERT INTO contract (
                    contractor_company_id, subcontractor_company_id, contract_date, subject,
                    project_id, discipline_id, branch_id, contract_title,
                    amount, currency_code, amount_in_words, end_date,
                    is_active, created_by, updated_by
                ) VALUES (
                    :contractor, :subcontractor, :cdate, :subject,
                    :project, :disc, :branch, :title,
                    :amount, :currency_code, :amount_words, :end_date,
                    :is_active, :created_by, :updated_by
                ) RETURNING id";

        $st = $pdo->prepare($sql);
        $st->bindValue(':contractor', $contractor, PDO::PARAM_INT);
        $st->bindValue(':subcontractor', $subcontractor, PDO::PARAM_INT);
        $st->bindValue(':cdate', $contractDate);
        $st->bindValue(':subject', $subject);
        $st->bindValue(':project', $projectId);
        $st->bindValue(':disc', $disciplineId);
        $st->bindValue(':branch', $branchId);
        $st->bindValue(':title', $title);
        $st->bindValue(':amount', $amount);
        $st->bindValue(':currency_code', $currencyCode);
        $st->bindValue(':amount_words', $amountInWords);
        $st->bindValue(':end_date', $endDate);
        $st->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
        $st->bindValue(':created_by', $createdBy);
        $st->bindValue(':updated_by', $updatedBy);
        $st->execute();

        return (int)$st->fetchColumn();
    }

    // Güncelle
    public static function update(PDO $pdo, int $id, array $d): void
    {
        $contractor = self::toInt($d['contractor_company_id'] ?? null);
        $subcontractor = self::toInt($d['subcontractor_company_id'] ?? null);
        $contractDate = self::nullIfEmpty($d['contract_date'] ?? null);

        if (!$contractor || !$subcontractor || !$contractDate) {
            throw new \InvalidArgumentException('contractor_company_id, subcontractor_company_id ve contract_date zorunludur.');
        }

        $subject = self::nullIfEmpty($d['subject'] ?? null);
        $projectId = self::toNullableInt($d['project_id'] ?? null);
        $disciplineId = self::toNullableInt($d['discipline_id'] ?? null);
        $branchId = self::toNullableInt($d['branch_id'] ?? null);
        $title = self::nullIfEmpty($d['contract_title'] ?? null);
        $amount = self::toNullableNumeric($d['amount'] ?? null);

        // currency_code: boş gelirse mevcut kayıttan oku ve doğrula
        $currencyCode = strtoupper(trim((string)($d['currency_code'] ?? '')));
        if ($currencyCode === '') {
            // Mevcut kaydın currency_code'unu al
            $existing = self::find($pdo, $id);
            $currencyCode = strtoupper((string)($existing['currency_code'] ?? self::DEFAULT_CURRENCY));
        }
        self::assertCurrency($currencyCode);

        $amountInWords = self::nullIfEmpty($d['amount_in_words'] ?? null);
        $endDate = self::nullIfEmpty($d['end_date'] ?? null);
        $paymentNotes = self::nullIfEmpty($d['payment_notes'] ?? null);

        $sql = "UPDATE contract SET
                    contractor_company_id = :contractor,
                    subcontractor_company_id = :subcontractor,
                    contract_date = :cdate,
                    subject = :subject,
                    project_id = :project,
                    discipline_id = :disc,
                    branch_id = :branch,
                    contract_title = :title,
                    amount = :amount,
                    currency_code = :currency_code,
                    amount_in_words = :amount_words,
                    end_date = :end_date,
                    payment_notes = :payment_notes,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";

        $st = $pdo->prepare($sql);
        $st->bindValue(':contractor', $contractor, PDO::PARAM_INT);
        $st->bindValue(':subcontractor', $subcontractor, PDO::PARAM_INT);
        $st->bindValue(':cdate', $contractDate);
        $st->bindValue(':subject', $subject);
        $st->bindValue(':project', $projectId);
        $st->bindValue(':disc', $disciplineId);
        $st->bindValue(':branch', $branchId);
        $st->bindValue(':title', $title);
        $st->bindValue(':amount', $amount);
        $st->bindValue(':currency_code', $currencyCode);
        $st->bindValue(':amount_words', $amountInWords);
        $st->bindValue(':end_date', $endDate);
        $st->bindValue(':payment_notes', $paymentNotes);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    // Soft delete
    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare("UPDATE contract SET deleted_at = now() WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function deleteByUuid(PDO $pdo, string $uuid): void
    {
        $stmt = $pdo->prepare("UPDATE contract SET deleted_at = now() WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();
    }

    // Helpers
    private static function toBool($v): bool
    {
        if (is_bool($v)) return $v;
        $s = strtolower(trim((string)$v));
        return in_array($s, ['1', 'true', 'on', 'yes', 'evet'], true);
    }

    private static function nullIfEmpty($v): ?string
    {
        if ($v === null) return null;
        $s = is_string($v) ? trim($v) : (string)$v;
        return $s === '' ? null : $s;
    }

    private static function toInt($v): ?int
    {
        return is_numeric($v) ? (int)$v : null;
    }

    private static function toNullableInt($v): ?int
    {
        if ($v === null || $v === '') return null;
        return is_numeric($v) ? (int)$v : null;
    }

    private static function toNullableNumeric($v): ?string
    {
        if ($v === null || $v === '') return null;
        $n = str_replace(',', '.', (string)$v);
        return is_numeric($n) ? number_format((float)$n, 2, '.', '') : null;
    }

    private static function assertCurrency(?string $code): void
    {
        if ($code === null || $code === '') {
            throw new \InvalidArgumentException('currency_code boş olamaz.');
        }
        if (!in_array($code, self::ALLOWED_CURRENCIES, true)) {
            $allowed = implode(',', self::ALLOWED_CURRENCIES);
            throw new \InvalidArgumentException("currency_code must be one of {$allowed}.");
        }
    }
}
