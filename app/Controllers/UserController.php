<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use PDO;
use PDOException;
use DateTime;

class UserController extends Controller
{
    // Liste: kullanıcıları firma adıyla
    public function index(): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT
              u.*,
              c.name AS company_name
            FROM public.users u
            LEFT JOIN public.companies c ON c.id = u.company_id
            WHERE u.deleted_at IS NULL
            ORDER BY u.id ASC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('users/index', [
            'title' => trans('common.users'),
            'users' => $users,
        ]);
    }

    // Oluştur formu
    public function create(): void
    {
        $companies = $this->listCompanies();

        $this->view('users/create', [
            'title'      => trans('common.add_user'),
            'companies'  => $companies,
        ]);
    }

    // Kayıt
    public function store(): void
    {
        $pdo = Database::pdo();

        $data = $this->collectFormData();

        // Zorunlu alan validasyonu
        $errors = $this->validate($data, isUpdate: false);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        // Checkbox/boole
        $data['is_active'] = $this->toBool($_POST['is_active'] ?? '1');

        // Firma ID (isteğe bağlı)
        $companyId = $_POST['company_id'] ?? null;
        $data['company_id'] = ($companyId !== null && $companyId !== '' && ctype_digit((string)$companyId))
            ? (int)$companyId
            : null;

        // created_by/updated_by (oturumdan)
        $currentUserId = $_SESSION['user_id'] ?? null;
        $data['created_by'] = $currentUserId;
        $data['updated_by'] = $currentUserId;

        // Unique ön-kontrol (opsiyonel; DB zaten garantiliyor)
        foreach (['email', 'national_id', 'passport_no'] as $u) {
            if (!empty($data[$u]) && User::existsByUnique($pdo, $u, (string)$data[$u])) {
                http_response_code(409);
                echo "Aynı $u ile kayıt mevcut.";
                return;
            }
        }

        try {
            $id = User::create($pdo, $data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                http_response_code(409);
                echo 'Benzersiz alan ihlali (email/national_id/passport_no).';
                return;
            }
            throw $e;
        }

        header('Location: /users');
        exit;
    }

    // Düzenleme formu
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

        $companies = $this->listCompanies();

        $this->view('users/edit', [
            'title'      => trans('common.edit_user'),
            'user'       => $user,
            'companies'  => $companies,
        ]);
    }

    // Güncelle
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
        $data['company_id'] = ($companyId !== null && $companyId !== '' && ctype_digit((string)$companyId))
            ? (int)$companyId
            : null;

        $errors = $this->validate($data, isUpdate: true);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        $data['is_active'] = $this->toBool($_POST['is_active'] ?? '1');

        $currentUserId = $_SESSION['user_id'] ?? null;
        $data['updated_by'] = $currentUserId;

        // Unique ön-kontrol (mevcut id hariç)
        foreach (['email', 'national_id', 'passport_no'] as $u) {
            if (!empty($data[$u]) && User::existsByUnique($pdo, $u, (string)$data[$u], excludeId: $id)) {
                http_response_code(409);
                echo "Aynı $u ile başka kullanıcı mevcut.";
                return;
            }
        }

        try {
            User::update($pdo, $id, $data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                http_response_code(409);
                echo 'Benzersiz alan ihlali (email/national_id/passport_no).';
                return;
            }
            throw $e;
        }

        header('Location: /users');
        exit;
    }

    // Soft delete
    public function destroy(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            User::delete(Database::pdo(), $id);
        }
        header('Location: /users');
        exit;
    }

    // ------------------------------------------------
    // Excel/CSV Şablon İndirme
    // Route: GET /users/template
    public function downloadTemplate(): void
    {
        $headers = [
            'email',
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'birth_date',
            'death_date',
            'phone',
            'secondary_phone',
            'national_id',
            'passport_no',
            'marital_status',
            'nationality_code',
            'place_of_birth',
            'timezone',
            'language',
            'photo_url',
            'address_line1',
            'address_line2',
            'city',
            'state_region',
            'postal_code',
            'country_code',
            'notes',
            'is_active',
            'last_login_at',
            'company_id',
            // opsiyoneller
            'uuid',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
        ];

        $sample = [
            'jane.doe@example.com',
            'Jane',
            '',
            'Doe',
            'female',
            '1990-05-12',
            '',
            '+90 555 000 00 00',
            '',
            '',
            '',
            'single',
            'TR',
            'Istanbul',
            'Europe/Istanbul',
            'tr',
            '',
            'Adres satırı 1',
            '',
            'İstanbul',
            'Kadıköy',
            '34710',
            'TR',
            'Notlar...',
            'true',
            '2024-12-01 10:15:00+03:00',
            '',
            '',
            '',
            '',
            '',
            ''
        ];

        $filename = 'users_template.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM (Excel uyumu)
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    // ------------------------------------------------
    // Toplu Yükleme (Excel/CSV → JSON payload)
    // Route: POST /users/bulk-upload
    // Body:
    //  - FormData: payload = JSON string
    //  - JSON: { "rows": [ [header...], [row1...], ... ] }

    public function bulkUpload(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 1) Gövdeyi al
            $payloadJson = null;

            if (isset($_POST['payload'])) {
                $payloadJson = (string)$_POST['payload'];
            } else {
                $raw = file_get_contents('php://input') ?: '';
                if ($raw !== '') {
                    $parsed = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if (isset($parsed['payload']) && is_string($parsed['payload'])) {
                            $payloadJson = $parsed['payload'];
                        } elseif (isset($parsed['rows']) && is_array($parsed['rows'])) {
                            $payloadJson = json_encode(['rows' => $parsed['rows']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }
                }
            }

            if (!$payloadJson) {
                http_response_code(400);
                echo json_encode(['message' => 'payload missing']);
                return;
            }

            $payload = json_decode($payloadJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'payload not valid JSON']);
                return;
            }

            $rows = $payload['rows'] ?? null;
            if (!is_array($rows) || count($rows) < 2) {
                http_response_code(422);
                echo json_encode(['message' => 'rows must include header + at least 1 data row']);
                return;
            }

            // 2) Başlıklar
            $headers = array_map(static fn($h) => trim((string)$h), (array)$rows[0]);
            $dataRows = array_slice($rows, 1);

            // 3) İzin verilen kolonlar (users şemasına göre)
            $allowedColumns = [
                'id',
                'email',
                'uuid',
                'first_name',
                'middle_name',
                'last_name',
                'gender',
                'birth_date',
                'death_date',
                'phone',
                'secondary_phone',
                'national_id',
                'passport_no',
                'marital_status',
                'nationality_code',
                'place_of_birth',
                'timezone',
                'language',
                'photo_url',
                'address_line1',
                'address_line2',
                'city',
                'state_region',
                'postal_code',
                'country_code',
                'notes',
                'is_active', // inputta kabul edeceğiz ama INSERT'e almayacağız
                'created_at',
                'updated_at',
                'deleted_at',
                'last_login_at',
                'created_by',
                'updated_by',
                'qr_secret',
                'company_id',
                'company_name', // input kabul, DB'ye yazılmayacak
            ];

            // Bilinmeyen başlık kontrolü
            $unknown = array_values(array_diff($headers, $allowedColumns));
            if (!empty($unknown)) {
                http_response_code(422);
                echo json_encode(['message' => 'Unknown header(s): ' . implode(', ', $unknown)]);
                return;
            }

            // 4) Satırları normalize et
            $pdo = Database::pdo(); // şirket adı -> ID eşlemesi için
            $findCompanyIdByName = static function (\PDO $pdo, ?string $name): ?int {
                if (!$name) return null;
                $sql = "SELECT id FROM public.companies WHERE deleted_at IS NULL AND LOWER(name) = LOWER(:name) LIMIT 1";
                $st  = $pdo->prepare($sql);
                $st->execute([':name' => $name]);
                $id = $st->fetchColumn();
                return $id ? (int)$id : null;
            };

            $prepared = [];
            foreach ($dataRows as $r) {
                if (!is_array($r)) continue;

                // Satırı başlığa eşitle (eksik/fazla dengele)
                $rowVals = array_map(static fn($v) => is_null($v) ? '' : (string)$v, $r);
                if (count($rowVals) < count($headers)) {
                    $rowVals = array_pad($rowVals, count($headers), '');
                } elseif (count($rowVals) > count($headers)) {
                    $rowVals = array_slice($rowVals, 0, count($headers));
                }

                $assoc = array_combine($headers, $rowVals);
                if ($assoc === false) continue;

                // Trim
                foreach ($assoc as $k => $v) {
                    $assoc[$k] = is_string($v) ? trim($v) : $v;
                }

                // uuid: boşsa gönderme; DB default gen_random_uuid()
                if (array_key_exists('uuid', $assoc) && $assoc['uuid'] === '') {
                    unset($assoc['uuid']);
                }

                // Boolean: is_active — parse et, edemezsen KALDIR
                if (array_key_exists('is_active', $assoc)) {
                    $valRaw = $assoc['is_active'];
                    $val = is_string($valRaw) ? trim($valRaw) : $valRaw;

                    if ($val === '' || $val === null || strtoupper((string)$val) === 'NULL') {
                        unset($assoc['is_active']); // DB default true
                    } else {
                        $mapTrue  = ['1', 'true', 't', 'yes', 'y', 'evet', 'on', 'aktif'];
                        $mapFalse = ['0', 'false', 'f', 'no', 'n', 'hayir', 'hayır', 'off', 'pasif'];
                        $lower = mb_strtolower((string)$val, 'UTF-8');

                        if (in_array($lower, $mapTrue, true)) {
                            // is_active'i INSERT'e sokmayacağız ama tutarlı olsun diye boolean'a çeviriyoruz
                            $assoc['is_active'] = true;
                        } elseif (in_array($lower, $mapFalse, true)) {
                            $assoc['is_active'] = false;
                        } else {
                            unset($assoc['is_active']); // anlaşılmadı -> default
                        }
                    }
                }

                // Tarihler: date alanları
                foreach (['birth_date', 'death_date'] as $dk) {
                    if (array_key_exists($dk, $assoc)) {
                        $parsed = $this->parseDateYmdOrNull($assoc[$dk]);
                        if ($parsed === null) {
                            unset($assoc[$dk]);
                        } else {
                            $assoc[$dk] = $parsed;
                        }
                    }
                }

                // Timestamptz alanları
                foreach (['created_at', 'updated_at', 'deleted_at', 'last_login_at'] as $tk) {
                    if (array_key_exists($tk, $assoc)) {
                        $parsed = $this->parseTimestampOrNull($assoc[$tk]);
                        if ($parsed === null) {
                            unset($assoc[$tk]);
                        } else {
                            $assoc[$tk] = $parsed;
                        }
                    }
                }

                // company_id sayısal
                if (array_key_exists('company_id', $assoc)) {
                    $cid = $assoc['company_id'];
                    if ($cid === '') {
                        $assoc['company_id'] = null;
                    } elseif (ctype_digit((string)$cid)) {
                        $assoc['company_id'] = (int)$cid;
                    } else {
                        $assoc['company_id'] = null;
                    }
                }

                // company_name -> company_id
                if (array_key_exists('company_name', $assoc)) {
                    $cname = $assoc['company_name'];
                    $cname = is_string($cname) ? trim($cname) : '';
                    if ((!isset($assoc['company_id']) || $assoc['company_id'] === null) && $cname !== '') {
                        $cid = $findCompanyIdByName($pdo, $cname);
                        if ($cid !== null) {
                            $assoc['company_id'] = $cid;
                        }
                    }
                    // DB'de kolon yok; kesin kaldır
                    unset($assoc['company_name']);
                }

                // Boş stringleri NULL yap (metin alanları) — is_active hariç
                foreach (
                    [
                        'email',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'gender',
                        'phone',
                        'secondary_phone',
                        'national_id',
                        'passport_no',
                        'marital_status',
                        'nationality_code',
                        'place_of_birth',
                        'timezone',
                        'language',
                        'photo_url',
                        'address_line1',
                        'address_line2',
                        'city',
                        'state_region',
                        'postal_code',
                        'country_code',
                        'notes',
                        'qr_secret',
                        'created_by',
                        'updated_by'
                    ] as $mk
                ) {
                    if (array_key_exists($mk, $assoc) && $assoc[$mk] === '') {
                        $assoc[$mk] = null;
                    }
                }

                // Minimum doğrulama: email zorunlu ve geçerli
                if (
                    !isset($assoc['email']) || $assoc['email'] === null || $assoc['email'] === '' ||
                    !filter_var((string)$assoc['email'], FILTER_VALIDATE_EMAIL)
                ) {
                    continue;
                }

                // ISO-3166-1 alpha-2 düzeltmeleri
                foreach (['nationality_code', 'country_code'] as $cc) {
                    if (isset($assoc[$cc]) && $assoc[$cc] !== null) {
                        $assoc[$cc] = mb_substr((string)$assoc[$cc], 0, 2) ?: null;
                    }
                }

                // language 5 karakter (CHAR(5))
                if (isset($assoc['language']) && $assoc['language'] !== null) {
                    $assoc['language'] = mb_substr((string)$assoc['language'], 0, 5) ?: null;
                }

                // id identity — Excel’den geldiyse yok say
                if (array_key_exists('id', $assoc)) {
                    unset($assoc['id']);
                }

                $prepared[] = $assoc;
            }

            if (empty($prepared)) {
                http_response_code(422);
                echo json_encode(['message' => 'No valid data rows']);
                return;
            }

            // 5) INSERT (dinamik kolon gruplama ile)
            $pdo->beginTransaction();

            $inserted = 0;

            // Aynı kolon setine sahip satırları grupla
            $groups = [];
            foreach ($prepared as $row) {
                // Emniyet: company_name asla INSERT'e girmesin
                if (array_key_exists('company_name', $row)) {
                    unset($row['company_name']);
                }
                // KRİTİK: is_active'i her koşulda INSERT'ten çıkar
                if (array_key_exists('is_active', $row)) {
                    unset($row['is_active']);
                }

                $cols = array_keys($row);
                sort($cols);
                $key = implode('|', $cols);
                $groups[$key]['cols'] = $cols;
                $groups[$key]['rows'][] = $row;
            }

            foreach ($groups as $group) {
                $cols = $group['cols'];
                if (!in_array('email', $cols, true)) {
                    continue;
                }

                $colsSql = '"' . implode('","', $cols) . '"';
                $place = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
                $sql = "INSERT INTO public.users ($colsSql) VALUES $place";
                $stmt = $pdo->prepare($sql);

                foreach ($group['rows'] as $row) {
                    $vals = [];
                    foreach ($cols as $c) {
                        $vals[] = $row[$c] ?? null;
                    }

                    try {
                        $stmt->execute($vals);
                        $inserted += $stmt->rowCount();
                    } catch (\PDOException $e) {
                        if ($e->getCode() === '23505') {
                            $pdo->rollBack();
                            http_response_code(409);
                            echo json_encode(['message' => 'unique violation', 'detail' => $e->getMessage()]);
                            return;
                        }
                        throw $e;
                    }
                }
            }

            $pdo->commit();

            http_response_code(200);
            echo json_encode(['message' => 'ok', 'inserted' => $inserted], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $code = $e->getCode() === '23505' ? 409 : 500;
            http_response_code($code);
            echo json_encode(['message' => 'db error', 'detail' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }
    // ------------------------------------------------
    // Yardımcılar

    private function listCompanies(): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT id, name FROM public.companies WHERE deleted_at IS NULL ORDER BY name ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function collectFormData(): array
    {
        // Trim + boşları null
        $g = static function (string $key, ?int $maxLen = null) {
            $val = $_POST[$key] ?? null;
            if ($val === null) return null;
            $val = is_string($val) ? trim($val) : $val;
            if ($val === '') return null;
            if ($maxLen !== null && is_string($val) && mb_strlen($val) > $maxLen) {
                $val = mb_substr($val, 0, $maxLen);
            }
            return $val;
        };

        $birthDate = $this->normalizeDate($g('birth_date'));
        $deathDate = $this->normalizeDate($g('death_date'));
        $lastLogin = $this->normalizeTimestamp($g('last_login_at')); // opsiyonel

        return [
            'email'            => $g('email', 255),
            'first_name'       => $g('first_name', 100),
            'middle_name'      => $g('middle_name', 100),
            'last_name'        => $g('last_name', 100),
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
            'notes'            => $g('notes'),
            'is_active'        => $this->toBool($_POST['is_active'] ?? '1'),
            'last_login_at'    => $lastLogin,
        ];
    }

    private function validate(array $data, bool $isUpdate): array
    {
        $errors = [];

        if (!$data['email'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta gereklidir.';
        }
        if (!$isUpdate) {
            // İsterseniz firstname/lastname’i zorunlu yapabilirsiniz.
        }

        // Tarihler
        if ($data['birth_date'] && !$this->isValidDate($data['birth_date'])) {
            $errors[] = 'Doğum tarihi Y-m-d formatında olmalı.';
        }
        if ($data['death_date'] && !$this->isValidDate($data['death_date'])) {
            $errors[] = 'Vefat tarihi Y-m-d formatında olmalı.';
        }

        // gender ve marital_status whitelist (şema CHECK)
        $allowedGenders = ['male', 'female', 'nonbinary', 'unknown'];
        if ($data['gender'] && !in_array($data['gender'], $allowedGenders, true)) {
            $errors[] = 'Geçersiz cinsiyet.';
        }

        $allowedMarital = ['single', 'married', 'divorced', 'widowed', 'other'];
        if ($data['marital_status'] && !in_array($data['marital_status'], $allowedMarital, true)) {
            $errors[] = 'Geçersiz medeni hal.';
        }

        // phone minimal kontrol
        if ($data['phone'] && mb_strlen(preg_replace('/\D+/', '', $data['phone'])) < 6) {
            $errors[] = 'Telefon numarası çok kısa.';
        }

        // nationality_code / country_code 2 karakter
        foreach (['nationality_code', 'country_code'] as $cc) {
            if ($data[$cc] && mb_strlen($data[$cc]) !== 2) {
                $errors[] = strtoupper($cc) . ' 2 karakter olmalı.';
            }
        }

        // language 2-5 karakter
        if ($data['language'] && (mb_strlen($data['language']) < 2 || mb_strlen($data['language']) > 5)) {
            $errors[] = 'Dil kodu 2-5 karakter olmalı.';
        }

        // company_id kontrol (varsa)
        if (isset($_POST['company_id']) && $_POST['company_id'] !== '') {
            if (!ctype_digit((string)$_POST['company_id'])) {
                $errors[] = 'Firma seçimi geçersiz.';
            }
        }

        return $errors;
    }

    private function normalizeDate(?string $s): ?string
    {
        if (!$s) return null;
        // Y-m-d veya d.m.Y desteği
        $d = DateTime::createFromFormat('Y-m-d', $s) ?: DateTime::createFromFormat('d.m.Y', $s);
        return $d ? $d->format('Y-m-d') : null;
    }

    private function normalizeTimestamp(?string $s): ?string
    {
        if (!$s) return null;
        $d = date_create($s);
        return $d ? $d->format('Y-m-d H:i:sP') : null; // timestamptz için uygun
    }

    private function isValidDate(string $s): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $s);
        return $d && $d->format('Y-m-d') === $s;
    }

    private function toBool($v): bool
    {
        if (is_bool($v)) return $v;
        $v = strtolower((string)$v);
        return in_array($v, ['1', 'true', 'on', 'yes', 'evet'], true);
    }

    // ---- Bulk helper’lar ----
    private function strToBoolOrNull($v): ?bool
    {
        if ($v === null) return null;
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;
        $trueSet = ['1', 'true', 'on', 'yes', 'evet', 'y', 't'];
        $falseSet = ['0', 'false', 'off', 'no', 'hayir', 'hayır', 'n', 'f'];
        if (in_array($s, $trueSet, true)) return true;
        if (in_array($s, $falseSet, true)) return false;
        return null;
    }

    private function parseDateYmdOrNull($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;

        $d = DateTime::createFromFormat('Y-m-d', $s) ?: DateTime::createFromFormat('d.m.Y', $s);
        if ($d === false) return null;
        return $d->format('Y-m-d');
    }

    private function parseTimestampOrNull($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        $ts = strtotime($s);
        if ($ts === false) return null;
        // timestamptz için ISO uyumlu bir format
        return date('Y-m-d H:i:sP', $ts);
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
