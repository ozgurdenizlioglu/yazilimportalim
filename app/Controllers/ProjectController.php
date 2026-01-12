<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Project;
use PDO;
use PDOException;

class ProjectController extends Controller
{
    // Liste
    public function index(): void
    {
        $pdo = Database::pdo();
        $projects = Project::all($pdo); // deleted_at IS NULL filtreli

        $this->view('projects/index', [
            'title' => 'Projeler',
            'projects' => $projects, // projects/index.php $projects bekler
        ]);
    }

    // Oluştur formu
    public function create(): void
    {
        $pdo = Database::pdo();

        // Get list of contractor companies for the form
        $stmt = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL AND contractor = true ORDER BY name ASC");
        $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->view('projects/create', [
            'title' => 'Proje Ekle',
            'companies' => $companies,
        ]);
    }

    // Kayıt
    public function store(): void
    {
        $pdo = Database::pdo();

        // Handle image upload first
        try {
            $imageUrl = $this->handleImageUpload();
        } catch (\Exception $e) {
            http_response_code(400);
            echo 'Görsel yükleme hatası: ' . $e->getMessage();
            return;
        }

        $data = $this->collectFormData(isUpdate: false);
        $data['image_url'] = $imageUrl;

        // created_by/updated_by (oturumdan)
        $currentUserId = $_SESSION['user_id'] ?? null;
        $data['created_by'] = $currentUserId;
        $data['updated_by'] = $currentUserId;

        // Checkbox’lar
        $data['is_active'] = $this->toBool($_POST['is_active'] ?? true);

        $errors = $this->validate($data, isUpdate: false);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        try {
            $id = Project::create($pdo, $data);
        } catch (PDOException $e) {
            // Unique violation (PostgreSQL 23505) — uuid unique ihlali olabilir
            if ($e->getCode() === '23505') {
                http_response_code(409);
                echo 'Benzersiz alan ihlali (muhtemelen uuid).';
                return;
            }
            throw $e;
        }

        header('Location: /projects');
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

        $project = Project::find($pdo, $id);
        if (!$project) {
            http_response_code(404);
            echo 'Proje bulunamadı';
            return;
        }

        // Get list of contractor companies for the form
        $stmt = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL AND contractor = true ORDER BY name ASC");
        $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->view('projects/edit', [
            'title' => 'Projeyi Düzenle',
            'project' => $project,
            'companies' => $companies,
        ]);
    }

    // Güncelle
    public function update(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo 'Geçersiz proje';
            return;
        }

        // Handle image upload first
        try {
            $imageUrl = $this->handleImageUpload();
        } catch (\Exception $e) {
            http_response_code(400);
            echo 'Görsel yükleme hatası: ' . $e->getMessage();
            return;
        }

        $data = $this->collectFormData(isUpdate: true);
        $data['image_url'] = $imageUrl;

        $data['is_active'] = $this->toBool($_POST['is_active'] ?? true);

        $currentUserId = $_SESSION['user_id'] ?? null;
        $data['updated_by'] = $currentUserId;

        $errors = $this->validate($data, isUpdate: true);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        try {
            Project::update($pdo, $id, $data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                http_response_code(409);
                echo 'Benzersiz alan ihlali (muhtemelen uuid).';
                return;
            }
            throw $e;
        }

        header('Location: /projects');
        exit;
    }

    // Silme (soft delete)
    public function destroy(): void
    {
        $pdo = Database::pdo();

        // index tablosunda form uuid gönderebilir:
        $uuid = $_POST['uuid'] ?? null;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($uuid) {
            Project::deleteByUuid($pdo, $uuid);
        } elseif ($id > 0) {
            Project::delete($pdo, $id);
        }

        header('Location: /projects');
        exit;
    }

    // ============= BULK UPLOAD =============
    // projects/index’teki “Sunucuya Yükle” butonunun action’ı: /projects/bulk-upload
    // Router’inizde bu metoda yönlendirdiğinizden emin olun.
    public function bulkUpload(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 1) Gövde
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

            // 3) İzin verilen kolonlar (project şemasına göre)
            $allowedColumns = [
                'id',
                'uuid',
                'name',
                'short_name',
                'project_path',
                'address_line1',
                'address_line2',
                'city',
                'state_region',
                'postal_code',
                'country_code',
                'company_id',
                'start_date',
                'end_date',
                'budget',
                'image_url',
                'notes',
                'status',
                'currency_code',
                'timezone',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at',
                'created_by',
                'updated_by',
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

                // uuid: boşsa gönderme; DB default gen_random_uuid()
                if (array_key_exists('uuid', $assoc) && $assoc['uuid'] === '') {
                    unset($assoc['uuid']);
                }

                // Boolean alanlar
                foreach (['is_active'] as $bk) {
                    if (array_key_exists($bk, $assoc)) {
                        $b = $this->strToBoolOrNull($assoc[$bk]);
                        if ($b === null) {
                            unset($assoc[$bk]); // default devreye girsin
                        } else {
                            $assoc[$bk] = $b;
                        }
                    }
                }

                // Tarihler: created_at, updated_at, deleted_at (timestamptz), start_date, end_date (date)
                foreach (['created_at', 'updated_at', 'deleted_at'] as $dk) {
                    if (array_key_exists($dk, $assoc)) {
                        $parsed = $this->parseDateOrNull($assoc[$dk]);
                        if ($parsed === null) {
                            unset($assoc[$dk]);
                        } else {
                            $assoc[$dk] = $parsed; // 'Y-m-d H:i:s'
                        }
                    }
                }
                foreach (['start_date', 'end_date'] as $dk) {
                    if (array_key_exists($dk, $assoc)) {
                        $parsed = $this->parseDateOnlyOrNull($assoc[$dk]);
                        if ($parsed === null) {
                            unset($assoc[$dk]);
                        } else {
                            $assoc[$dk] = $parsed; // 'Y-m-d'
                        }
                    }
                }

                // Sayısal: budget, company_id
                if (array_key_exists('budget', $assoc)) {
                    $assoc['budget'] = $this->parseNumberOrNull($assoc['budget']);
                    if ($assoc['budget'] === null && $assoc['budget'] !== 0.0) {
                        unset($assoc['budget']);
                    }
                }
                if (array_key_exists('company_id', $assoc)) {
                    $assoc['company_id'] = $this->parseIntOrNull($assoc['company_id']);
                    if ($assoc['company_id'] === null) {
                        unset($assoc['company_id']);
                    }
                }

                // Boş stringleri NULL’a çevir (metinsel alanlar)
                foreach (
                    [
                        'name',
                        'short_name',
                        'project_path',
                        'address_line1',
                        'address_line2',
                        'city',
                        'state_region',
                        'postal_code',
                        'country_code',
                        'image_url',
                        'notes',
                        'status',
                        'currency_code',
                        'timezone',
                        'created_by',
                        'updated_by',
                    ] as $tk
                ) {
                    if (array_key_exists($tk, $assoc) && $assoc[$tk] === '') {
                        if ($tk === 'status') {
                            unset($assoc[$tk]); // DB default 'active'
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

                // country_code 2 char (opsiyonel kısaltma)
                if (isset($assoc['country_code']) && $assoc['country_code'] !== null) {
                    $assoc['country_code'] = mb_substr((string)$assoc['country_code'], 0, 2) ?: null;
                }

                // currency_code 3 char
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

            // Başlığın kesişimine göre kolon listesi
            $insertable = array_values(array_intersect($allowedColumns, $headers));
            // name kesin olmalı
            if (!in_array('name', $insertable, true)) {
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode(['message' => 'Header must include "name"']);
                return;
            }

            // Aynı kolon setine sahip satırları grupla
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
                    continue;
                }

                $colsSql = '"' . implode('","', $cols) . '"';
                $ph = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
                $sql = "INSERT INTO project ($colsSql) VALUES $ph";
                $stmt = $pdo->prepare($sql);

                foreach ($group['rows'] as $row) {
                    $vals = [];
                    foreach ($cols as $c) {
                        $v = $row[$c] ?? null;
                        if (is_bool($v)) {
                            $v = $v ? 1 : 0;
                        }
                        $vals[] = $v;
                    }
                    try {
                        $stmt->execute($vals);
                        $inserted += $stmt->rowCount();
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        http_response_code(500);
                        echo json_encode([
                            'message' => 'db error',
                            'detail' => $e->getMessage(),
                            'sql' => $sql,
                            'params' => $vals
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
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

        $toFloat = static function (?string $v): ?float {
            if ($v === null || $v === '') return null;
            $v = str_replace(',', '.', $v);
            $n = filter_var($v, FILTER_VALIDATE_FLOAT);
            return $n === false ? null : (float)$n;
        };

        $toInt = static function (?string $v): ?int {
            if ($v === null || $v === '') return null;
            $n = filter_var($v, FILTER_VALIDATE_INT);
            return $n === false ? null : (int)$n;
        };

        $toDateOnly = function (?string $v): ?string {
            if ($v === null || trim($v) === '') return null;
            return $this->parseDateOnlyOrNull($v);
        };

        return [
            // Temel
            'name'          => $g('name', 255),
            'short_name'    => $g('short_name', 100),
            'project_path'  => $g('project_path', 512),

            // Adres
            'address_line1' => $g('address_line1'),
            'address_line2' => $g('address_line2'),
            'city'          => $g('city', 100),
            'state_region'  => $g('state_region', 100),
            'postal_code'   => $g('postal_code', 20),
            'country_code'  => $g('country_code', 2),

            // İlişkiler
            'company_id'    => $toInt($g('company_id')),

            // Tarihler
            'start_date'    => $toDateOnly($g('start_date')),
            'end_date'      => $toDateOnly($g('end_date')),

            // Finans
            'budget'        => $toFloat($g('budget')),
            'currency_code' => $g('currency_code', 3),

            // Diğer
            'image_url'     => $g('image_url'),
            'notes'         => $g('notes'),
            'status'        => $g('status', 20) ?? 'active',
            'timezone'      => $g('timezone', 50),
        ];
    }

    private function validate(array $data, bool $isUpdate): array
    {
        $errors = [];

        if (!$data['name']) {
            $errors[] = 'Proje adı (name) gereklidir.';
        }

        $allowedStatus = ['active', 'planned', 'in_progress', 'on_hold', 'completed', 'cancelled', 'inactive'];
        if (!empty($data['status']) && !in_array($data['status'], $allowedStatus, true)) {
            $errors[] = 'Geçersiz durum (status).';
        }

        if (!empty($data['country_code']) && mb_strlen($data['country_code']) !== 2) {
            $errors[] = 'Ülke kodu (country_code) 2 karakter olmalı.';
        }

        if (!empty($data['currency_code']) && mb_strlen($data['currency_code']) !== 3) {
            $errors[] = 'Para birimi (currency_code) 3 karakter olmalı.';
        }

        if ($data['budget'] !== null) {
            // numeric(18,2) doğrulaması
            $scaled = round((float)$data['budget'], 2);
            if (abs((float)$data['budget'] - $scaled) > 0.00001) {
                $errors[] = 'Bütçe (budget) en fazla 2 ondalık olmalı.';
            }
        }

        // Tarih ilişkisi
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $errors[] = 'Bitiş tarihi (end_date) başlangıç tarihinden (start_date) önce olamaz.';
            }
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
        return date('Y-m-d H:i:s', $ts); // timestamptz için
    }

    private function parseDateOnlyOrNull($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        $ts = strtotime($s);
        if ($ts === false) return null;
        return date('Y-m-d', $ts); // date için
    }

    private function parseNumberOrNull($v): ?float
    {
        if ($v === null) return null;
        $s = str_replace(',', '.', trim((string)$v));
        if ($s === '') return null;
        $n = filter_var($s, FILTER_VALIDATE_FLOAT);
        return $n === false ? null : (float)$n;
    }

    private function parseIntOrNull($v): ?int
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        $n = filter_var($s, FILTER_VALIDATE_INT);
        return $n === false ? null : (int)$n;
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // v4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function handleImageUpload(): ?string
    {
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) {
            // No file uploaded, keep existing
            return $_POST['existing_image_url'] ?? null;
        }

        $file = $_FILES['image_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Görsel yükleme hatası: ' . $file['error']);
        }

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \RuntimeException('Geçersiz dosya türü. Sadece PNG, JPG, JPEG, GIF kabul edilir.');
        }

        // Check file size (max 5MB for project images)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \RuntimeException('Dosya çok büyük. Maksimum 5MB.');
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (empty($ext)) {
            $ext = str_replace('image/', '', $mimeType);
            if ($ext === 'jpeg') $ext = 'jpg';
        }
        $filename = $this->uuidv4() . '.' . strtolower($ext);

        // Ensure projects directory exists
        $projectsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'projects';
        if (!is_dir($projectsDir)) {
            mkdir($projectsDir, 0755, true);
        }

        $targetPath = $projectsDir . DIRECTORY_SEPARATOR . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Dosya kaydedilemedi.');
        }

        // Return the relative path that can be used in URLs
        return '/storage/projects/' . $filename;
    }

    /**
     * API endpoint to get contractor companies for filtering
     * Used in project create/edit forms with company dropdown
     */
    public function companyList(): void
    {
        $pdo = Database::pdo();
        $search = trim($_GET['q'] ?? '');

        if ($search !== '') {
            // Search contractor companies by name
            $stmt = $pdo->prepare("
                SELECT id, name 
                FROM companies 
                WHERE deleted_at IS NULL 
                AND contractor = true
                AND (name ILIKE :search OR short_name ILIKE :search)
                ORDER BY name ASC
                LIMIT 50
            ");
            $stmt->execute([':search' => "%$search%"]);
        } else {
            // Get all contractor companies
            $stmt = $pdo->query("
                SELECT id, name 
                FROM companies 
                WHERE deleted_at IS NULL 
                AND contractor = true
                ORDER BY name ASC
                LIMIT 100
            ");
        }

        $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $companies,
        ]);
    }
}
