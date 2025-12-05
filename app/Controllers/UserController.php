<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use PDO;
use DateTime;

class UserController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        // Kullanıcıları firma adı ile birlikte getir (LEFT JOIN)
        $stmt = $pdo->prepare("
            SELECT
              u.*,
              c.name AS company_name
            FROM public.users u
            LEFT JOIN public.companies c ON c.id = u.company_id
            ORDER BY u.id ASC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('users/index', [
            'title' => 'Kullanıcılar',
            'users' => $users,
        ]);
    }

    public function create(): void
    {
        // Firma seçenekleri (aktif ve silinmemiş)
        $companies = $this->listCompanies();

        // Formu göstermek için $companies gönderiyoruz
        $this->view('users/create', [
            'title'      => 'Kullanıcı Ekle',
            'companies'  => $companies,
        ]);
    }

    public function store(): void
    {
        $pdo = Database::pdo();

        // Form alanlarını al
        $data = $this->collectFormData();

        // Zorunlu alanlar: first_name, last_name, email (şemanıza göre)
        $errors = $this->validate($data, isUpdate: false);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        // is_active checkbox ise string "on"/"1" gelebilir; bool'a çevir
        $data['is_active'] = $this->toBool($_POST['is_active'] ?? '1');

        // Firma ID (isteğe bağlı)
        $companyId = $_POST['company_id'] ?? null;
        $data['company_id'] = ($companyId !== null && $companyId !== '') ? (int)$companyId : null;

        // created_by/updated_by örnek: oturumdaki kullanıcı ID'si varsa set edebilirsin
        $currentUserId = null; // örn: $_SESSION['user_id'] ?? null;
        $data['created_by'] = $currentUserId;
        $data['updated_by'] = $currentUserId;

        // Oluştur
        $id = User::create($pdo, $data);

        header('Location: /users');
    }

    public function edit(): void
    {
        $pdo = Database::pdo();

        $id = (int)($_GET['id'] ?? 0);
        $user = User::find($pdo, $id);
        if (!$user) {
            http_response_code(404);
            echo 'Kullanıcı bulunamadı';
            return;
        }

        // Firma seçenekleri
        $companies = $this->listCompanies();

        $this->view('users/edit', [
            'title'      => 'Kullanıcıyı Düzenle',
            'user'       => $user,
            'companies'  => $companies,
        ]);
    }

    public function update(): void
    {
        $pdo = Database::pdo();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo 'Geçersiz kullanıcı';
            return;
        }

        $data = $this->collectFormData();

        // Firma ID (isteğe bağlı)
        $companyId = $_POST['company_id'] ?? null;
        $data['company_id'] = ($companyId !== null && $companyId !== '') ? (int)$companyId : null;

        $errors = $this->validate($data, isUpdate: true);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        $data['is_active'] = $this->toBool($_POST['is_active'] ?? '1');

        $currentUserId = null; // örn: $_SESSION['user_id'] ?? null;
        $data['updated_by'] = $currentUserId;

        User::update($pdo, $id, $data);

        header('Location: /users');
    }

    public function destroy(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            User::delete(Database::pdo(), $id);
        }
        header('Location: /users');
    }

    // ----------------- Yardımcılar -----------------

    private function listCompanies(): array
    {
        $pdo = Database::pdo();
        // Şemanıza göre koşulları uyarlayın (is_active/deleted_at olmayabilir)
        $sql = "SELECT id, name FROM public.companies WHERE (deleted_at IS NULL OR deleted_at IS NULL) ORDER BY name ASC";
        // Not: deleted_at IS NULL iki kez gereksiz; tek kez yeterli. Ama güvenli.
        $sql = "SELECT id, name FROM public.companies WHERE deleted_at IS NULL OR deleted_at IS NULL ORDER BY name ASC";
        // is_active kolonu varsa ekleyin:
        // $sql = "SELECT id, name FROM public.companies WHERE deleted_at IS NULL AND is_active = true ORDER BY name ASC";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function collectFormData(): array
    {
        // Trim ve boş stringleri null’a çevir
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

        // Tarihler Y-m-d formatı bekleniyor
        $birthDate = $this->normalizeDate($g('birth_date'));
        $deathDate = $this->normalizeDate($g('death_date'));
        $lastLogin = $this->normalizeTimestamp($g('last_login_at')); // opsiyonel

        return [
            'first_name'       => $g('first_name', 100),
            'middle_name'      => $g('middle_name', 100),
            'last_name'        => $g('last_name', 100),
            'email'            => $g('email', 255),
            'gender'           => $g('gender', 20),
            'birth_date'       => $birthDate,
            'death_date'       => $deathDate,
            'phone'            => $g('phone', 20),
            'secondary_phone'  => $g('secondary_phone', 20),
            'national_id'      => $g('national_id', 50),
            'passport_no'      => $g('passport_no', 30),
            'marital_status'   => $g('marital_status', 20),
            'nationality_code' => $g('nationality_code', 2),
            'place_of_birth'   => $g('place_of_birth', 120),
            'timezone'         => $g('timezone', 50),
            'language'         => $g('language', 5),
            'photo_url'        => $g('photo_url', 2048),
            'address_line1'    => $g('address_line1', 200),
            'address_line2'    => $g('address_line2', 200),
            'city'             => $g('city', 100),
            'state_region'     => $g('state_region', 100),
            'postal_code'      => $g('postal_code', 20),
            'country_code'     => $g('country_code', 2),
            'notes'            => $g('notes'), // uzun olabilir
            'is_active'        => $this->toBool($_POST['is_active'] ?? '1'),
            'last_login_at'    => $lastLogin,
            // company_id ayrı okunup store/update içinde ekleniyor
            // created_by / updated_by controller içinde ayrı set ediliyor
        ];
    }

    private function validate(array $data, bool $isUpdate): array
    {
        $errors = [];

        if (!$data['first_name']) {
            $errors[] = 'Ad (first_name) gereklidir.';
        }
        if (!$data['last_name']) {
            $errors[] = 'Soyad (last_name) gereklidir.';
        }

        if (!$data['email'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta gereklidir.';
        }

        // Tarih formatları
        if ($data['birth_date'] && !$this->isValidDate($data['birth_date'])) {
            $errors[] = 'Doğum tarihi Y-m-d formatında olmalı.';
        }
        if ($data['death_date'] && !$this->isValidDate($data['death_date'])) {
            $errors[] = 'Vefat tarihi Y-m-d formatında olmalı.';
        }

        // gender ve marital_status whitelists (şemanızdaki CHECK ile uyumlu)
        $allowedGenders = ['male','female','nonbinary','unknown'];
        if ($data['gender'] && !in_array($data['gender'], $allowedGenders, true)) {
            $errors[] = 'Geçersiz cinsiyet.';
        }

        $allowedMarital = ['single','married','divorced','widowed','other'];
        if ($data['marital_status'] && !in_array($data['marital_status'], $allowedMarital, true)) {
            $errors[] = 'Geçersiz medeni hal.';
        }

        // phone uzunluk kontrolü (pattern’i view’da da ekleyebilirsin)
        if ($data['phone'] && mb_strlen(preg_replace('/\D+/', '', $data['phone'])) < 6) {
            $errors[] = 'Telefon numarası çok kısa.';
        }

        // nationality_code ve country_code 2 karakter olmalı (ISO-3166-1 alpha-2)
        foreach (['nationality_code','country_code'] as $cc) {
            if ($data[$cc] && mb_strlen($data[$cc]) !== 2) {
                $errors[] = strtoupper($cc) . ' 2 karakter olmalı.';
            }
        }

        // language 2-5 karakter (örn: tr, en, tr-TR)
        if ($data['language'] && (mb_strlen($data['language']) < 2 || mb_strlen($data['language']) > 5)) {
            $errors[] = 'Dil kodu 2-5 karakter olmalı.';
        }

        // company_id (varsa) pozitif integer olmalı
        if (isset($_POST['company_id']) && $_POST['company_id'] !== '') {
            if (!ctype_digit((string)$_POST['company_id'])) {
                $errors[] = 'Firma seçimi geçersiz.';
            }
        }

        return $errors;
    }

    private function normalizeDate(?string $s): ?string
    {
        if (!$s) {
            return null;
        }
        // Kabul edilecek formatlar: Y-m-d veya d.m.Y
        $d = DateTime::createFromFormat('Y-m-d', $s) ?: DateTime::createFromFormat('d.m.Y', $s);
        return $d ? $d->format('Y-m-d') : null;
    }

    private function normalizeTimestamp(?string $s): ?string
    {
        if (!$s) {
            return null;
        }
        // ISO-8601 vb. girişleri kabul etmeye çalış
        $d = date_create($s);
        return $d ? $d->format('Y-m-d H:i:sP') : null; // PostgreSQL timestamptz için uygun
    }

    private function isValidDate(string $s): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $s);
        return $d && $d->format('Y-m-d') === $s;
    }

    private function toBool($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        $v = strtolower((string)$v);
        return in_array($v, ['1','true','on','yes'], true);
    }

    public function token(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !ctype_digit($id)) {
            http_response_code(400);
            echo "id gerekli";
            return;
        }
        $db  = \App\Core\Database::pdo();
        $svc = new \App\Core\TokenService($db);
        $token = $svc->createTokenForUser((int)$id);
        header('Content-Type: text/plain; charset=utf-8');
        echo $token;
    }
}