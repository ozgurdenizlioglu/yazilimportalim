<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Firm;
use PDO;
use PDOException;

class FirmController extends Controller
{
    // Liste
    public function index(): void
    {
        $pdo = Database::pdo();
        $firms = Firm::all($pdo); // deleted_at IS NULL filtreli

        $this->view('firms/index', [
            'title' => 'Firmalar',
            'firms' => $firms, // ÖNEMLİ: firms/index.php $firms bekliyor
        ]);
    }

    // Oluştur formu
    public function create(): void
    {
        $this->view('firms/create', [
            'title' => 'Firma Ekle',
        ]);
    }

    // Kayıt
    public function store(): void
    {
        $pdo = Database::pdo();
        $data = $this->collectFormData(isUpdate: false);

        // created_by/updated_by (oturumdan)
        $currentUserId = $_SESSION['user_id'] ?? null;
        $data['created_by'] = $currentUserId;
        $data['updated_by'] = $currentUserId;

        // Checkbox’lar
        $data['vat_exempt'] = $this->toBool($_POST['vat_exempt'] ?? false);
        $data['e_invoice_enabled'] = $this->toBool($_POST['e_invoice_enabled'] ?? false);
        $data['is_active'] = $this->toBool($_POST['is_active'] ?? true);

        $errors = $this->validate($data, isUpdate: false);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        // Opsiyonel: unique pre-check (DB zaten garantiliyor)
        foreach (['registration_no','mersis_no','tax_number'] as $u) {
            if (!empty($data[$u]) && Firm::existsByUnique($pdo, $u, (string)$data[$u])) {
                http_response_code(409);
                echo "Aynı $u ile kayıt mevcut.";
                return;
            }
        }

        try {
            $id = Firm::create($pdo, $data);
        } catch (PDOException $e) {
            // Unique violation (PostgreSQL 23505)
            if ($e->getCode() === '23505') {
                http_response_code(409);
                echo 'Benzersiz alan ihlali (registration_no/mersis_no/tax_number).';
                return;
            }
            throw $e;
        }

        header('Location: /firms');
        exit;
    }

    // Düzenleme formu
    public function edit(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz id';
            return;
        }

        $firm = Firm::find($pdo, $id);
        if (!$firm) {
            http_response_code(404);
            echo 'Firma bulunamadı';
            return;
        }

        $this->view('firms/edit', [
            'title' => 'Firmayı Düzenle',
            'firm' => $firm, // View tarafında 'firm' olarak kullanmak pratik
        ]);
    }

    // Güncelle
    public function update(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo 'Geçersiz firma';
            return;
        }

        $data = $this->collectFormData(isUpdate: true);

        $data['vat_exempt'] = $this->toBool($_POST['vat_exempt'] ?? false);
        $data['e_invoice_enabled'] = $this->toBool($_POST['e_invoice_enabled'] ?? false);
        $data['is_active'] = $this->toBool($_POST['is_active'] ?? true);

        $currentUserId = $_SESSION['user_id'] ?? null;
        $data['updated_by'] = $currentUserId;

        $errors = $this->validate($data, isUpdate: true);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        foreach (['registration_no','mersis_no','tax_number'] as $u) {
            if (!empty($data[$u]) && Firm::existsByUnique($pdo, $u, (string)$data[$u], excludeId: $id)) {
                http_response_code(409);
                echo "Aynı $u ile başka kayıt mevcut.";
                return;
            }
        }

        try {
            Firm::update($pdo, $id, $data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                http_response_code(409);
                echo 'Benzersiz alan ihlali (registration_no/mersis_no/tax_number).';
                return;
            }
            throw $e;
        }

        header('Location: /firms');
        exit;
    }

    // Silme (soft delete)
    public function destroy(): void
    {
        $pdo = Database::pdo();

        // index tablosunda form uuid gönderiyor:
        $uuid = $_POST['uuid'] ?? null;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($uuid) {
            Firm::deleteByUuid($pdo, $uuid);
        } elseif ($id > 0) {
            Firm::delete($pdo, $id);
        }

        header('Location: /firms');
        exit;
    }

    // --------------- Yardımcılar ---------------

    private function collectFormData(bool $isUpdate): array
    {
        // Trim + boşları null’a çevir
        $g = static function (string $key, ?int $maxLen = null) {
            $val = $_POST[$key] ?? null;
            if ($val === null) {
                return null;
            }
            $val = is_string($val) ? trim($val) : $val;
            if ($val === '') {
                return null;
            }
            if ($maxLen !== null && is_string($val) && mb_strlen($val) > $maxLen) {
                $val = mb_substr($val, 0, $maxLen);
            }
            return $val;
        };

        $toNumber = static function (?string $v): ?float {
            if ($v === null || $v === '') {
                return null;
            }
            $n = filter_var($v, FILTER_VALIDATE_FLOAT);
            return $n === false ? null : (float)$n;
        };

        return [
            // Temel
            'name'              => $g('name', 200),
            'short_name'        => $g('short_name', 100),
            'legal_type'        => $g('legal_type', 30),
            'registration_no'   => $g('registration_no', 100),
            'mersis_no'         => $g('mersis_no', 50),
            'tax_office'        => $g('tax_office', 120),
            'tax_number'        => $g('tax_number', 50),

            // İletişim
            'email'             => $g('email'),
            'phone'             => $g('phone', 30),
            'secondary_phone'   => $g('secondary_phone', 30),
            'fax'               => $g('fax', 30),
            'website'           => $g('website', 2048),

            // Adres
            'address_line1'     => $g('address_line1', 200),
            'address_line2'     => $g('address_line2', 200),
            'city'              => $g('city', 100),
            'state_region'      => $g('state_region', 100),
            'postal_code'       => $g('postal_code', 20),
            'country_code'      => $g('country_code', 2),

            // Koordinatlar
            'latitude'          => $toNumber($g('latitude')),
            'longitude'         => $toNumber($g('longitude')),

            // Operasyonel
            'industry'          => $g('industry', 120),
            'status'            => $g('status', 20) ?? 'active',
            'currency_code'     => $g('currency_code', 3),
            'timezone'          => $g('timezone', 50),

            // Medya/Not
            'logo_url'          => $g('logo_url', 2048),
            'notes'             => $g('notes'),
        ];
    }

    private function validate(array $data, bool $isUpdate): array
    {
        $errors = [];

        if (!$data['name']) {
            $errors[] = 'Firma adı (name) gereklidir.';
        }

        $allowedStatus = ['active','prospect','lead','suspended','inactive'];
        if (!empty($data['status']) && !in_array($data['status'], $allowedStatus, true)) {
            $errors[] = 'Geçersiz durum (status).';
        }

        if (!empty($data['country_code']) && mb_strlen($data['country_code']) !== 2) {
            $errors[] = 'Ülke kodu (country_code) 2 karakter olmalı.';
        }

        if (!empty($data['currency_code']) && mb_strlen($data['currency_code']) !== 3) {
            $errors[] = 'Para birimi (currency_code) 3 karakter olmalı.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta girin.';
        }

        if ($data['latitude'] !== null && ($data['latitude'] < -90 || $data['latitude'] > 90)) {
            $errors[] = 'Latitude -90 ile 90 arasında olmalı.';
        }
        if ($data['longitude'] !== null && ($data['longitude'] < -180 || $data['longitude'] > 180)) {
            $errors[] = 'Longitude -180 ile 180 arasında olmalı.';
        }

        return $errors;
    }

    private function toBool($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        $v = strtolower((string)$v);
        return in_array($v, ['1','true','on','yes','evet'], true);
    }
}
