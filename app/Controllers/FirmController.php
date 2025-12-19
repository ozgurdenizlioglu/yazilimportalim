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
        foreach (['registration_no', 'mersis_no', 'tax_number'] as $u) {
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
            'firm' => $firm,
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

        foreach (['registration_no', 'mersis_no', 'tax_number'] as $u) {
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

    // ============= BULK UPLOAD =============
    // firms/index’teki “Sunucuya Yükle” butonunun action’ı: /firms/bulk-upload
    // Router’inizde bu metoda yönlendirdiğinizden emin olun.
    public function bulkUpload(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 1) Gövdeyi al (FormData: payload ya da application/json)
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

            // 3) İzin verilen kolonlar (companies şemasına göre)
            $allowedColumns = [
                'id',
                'uuid',
                'name',
                'short_name',
                'legal_type',
                'registration_no',
                'mersis_no',
                'tax_office',
                'tax_number',
                'email',
                'phone',
                'secondary_phone',
                'fax',
                'website',
                'address_line1',
                'address_line2',
                'city',
                'state_region',
                'postal_code',
                'country_code',
                'latitude',
                'longitude',
                'industry',
                'status',
                'currency_code',
                'timezone',
                'vat_exempt',
                'e_invoice_enabled',
                'logo_url',
                'notes',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at',
                'created_by',
                'updated_by'
            ];

            // Bilinmeyen başlık kontrolü
            $unknown = array_values(array_diff($headers, $allowedColumns));
            if (!empty($unknown)) {
                http_response_code(422);
                echo json_encode(['message' => 'Unknown header(s): ' . implode(', ', $unknown)]);
                return;
            }

            // 4) Satırları normalize et
            $prepared = [];
            foreach ($dataRows as $r) {
                if (!is_array($r)) continue;

                // Satırı başlığa eşitle
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

                // uuid: boşsa gönderme; DB default gen_random_uuid() üretecek
                if (array_key_exists('uuid', $assoc) && $assoc['uuid'] === '') {
                    unset($assoc['uuid']);
                }

                // Boolean alanlar
                foreach (['is_active', 'vat_exempt', 'e_invoice_enabled'] as $bk) {
                    if (array_key_exists($bk, $assoc)) {
                        $b = $this->strToBoolOrNull($assoc[$bk]);
                        // NULL ise unset edersek DB default devreye girer
                        if ($b === null) {
                            unset($assoc[$bk]);
                        } else {
                            $assoc[$bk] = $b;
                        }
                    }
                }

                // Tarihler (created_at, updated_at, deleted_at) — boşsa unset, doluysa parse
                foreach (['created_at', 'updated_at', 'deleted_at'] as $dk) {
                    if (array_key_exists($dk, $assoc)) {
                        $parsed = $this->parseDateOrNull($assoc[$dk]);
                        if ($parsed === null) {
                            unset($assoc[$dk]); // default/NULL çalışır
                        } else {
                            $assoc[$dk] = $parsed; // 'Y-m-d H:i:s' (timestamptz parse edilir)
                        }
                    }
                }

                // Sayısal: latitude/longitude
                foreach (['latitude', 'longitude'] as $nk) {
                    if (array_key_exists($nk, $assoc)) {
                        $val = str_replace(',', '.', (string)$assoc[$nk]);
                        if ($val === '') {
                            unset($assoc[$nk]); // NULL olsun
                        } else {
                            $assoc[$nk] = is_numeric($val) ? (float)$val : null;
                        }
                    }
                }

                // Boş stringleri NULL’a çevir (metinsel alanlar)
                foreach (
                    [
                        'name',
                        'short_name',
                        'legal_type',
                        'registration_no',
                        'mersis_no',
                        'tax_office',
                        'tax_number',
                        'email',
                        'phone',
                        'secondary_phone',
                        'fax',
                        'website',
                        'address_line1',
                        'address_line2',
                        'city',
                        'state_region',
                        'postal_code',
                        'country_code',
                        'industry',
                        'status',
                        'currency_code',
                        'timezone',
                        'logo_url',
                        'notes',
                        'created_by',
                        'updated_by'
                    ] as $tk
                ) {
                    if (array_key_exists($tk, $assoc) && $assoc[$tk] === '') {
                        // status boşsa hiç göndermeyelim (DB default 'active')
                        if ($tk === 'status') {
                            unset($assoc[$tk]);
                        } else {
                            $assoc[$tk] = null;
                        }
                    }
                }

                // id: identity — excel’den gelirse kullanmayalım (opsiyonel)
                if (array_key_exists('id', $assoc)) {
                    unset($assoc['id']);
                }

                // Minimum doğrulama
                if (!isset($assoc['name']) || $assoc['name'] === null || $assoc['name'] === '') {
                    continue; // name olmadan satırı atla
                }

                // country_code 2 char ise bırak, değilse NULL’a çek (opsiyonel)
                if (isset($assoc['country_code']) && $assoc['country_code'] !== null) {
                    $assoc['country_code'] = mb_substr((string)$assoc['country_code'], 0, 2) ?: null;
                }

                // currency_code 3 char (opsiyonel kısaltma)
                if (isset($assoc['currency_code']) && $assoc['currency_code'] !== null) {
                    $assoc['currency_code'] = mb_substr((string)$assoc['currency_code'], 0, 3) ?: null;
                }

                $prepared[] = $assoc;
            }

            if (empty($prepared)) {
                http_response_code(422);
                echo json_encode(['message' => 'No valid data rows']);
                return;
            }

            // 5) INSERT
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            // Başlığın kesişimine göre kolon listesi (id yok; uuid opsiyonel; created_at/updated_at opsiyonel)
            $insertable = array_values(array_intersect($allowedColumns, $headers));
            // name kesin olmalı
            if (!in_array('name', $insertable, true)) {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['message' => 'Header must include "name"']);
                return;
            }

            // Her satır kolonları farklı olabilir (çünkü unset yaptık). Bu nedenle dinamik insert kullanacağız:
            // Aynı kolon setine sahip satırları gruplayalım.
            $groups = [];
            foreach ($prepared as $row) {
                $cols = array_keys($row);
                sort($cols);
                $key = implode('|', $cols);
                $groups[$key]['cols'] = $cols;
                $groups[$key]['rows'][] = $row;
            }

            $inserted = 0;

            foreach ($groups as $group) {
                $cols = $group['cols'];
                if (!in_array('name', $cols, true)) {
                    // name bu grupta yoksa bu grup atlanır
                    continue;
                }

                $colsSql = '"' . implode('","', $cols) . '"';
                $ph = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
                $sql = "INSERT INTO companies ($colsSql) VALUES $ph";
                $stmt = $pdo->prepare($sql);

                foreach ($group['rows'] as $row) {
                    $vals = [];
                    foreach ($cols as $c) {
                        $vals[] = $row[$c] ?? null;
                    }
                    $stmt->execute($vals);
                    $inserted += $stmt->rowCount();
                }
            }

            $pdo->commit();

            http_response_code(200);
            echo json_encode(['message' => 'ok', 'inserted' => $inserted], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (PDOException $e) {
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

        $allowedStatus = ['active', 'prospect', 'lead', 'suspended', 'inactive'];
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

    private function parseDateOrNull($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        $ts = strtotime($s);
        if ($ts === false) return null;
        // timestamptz için bu format uygundur
        return date('Y-m-d H:i:s', $ts);
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // v4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
