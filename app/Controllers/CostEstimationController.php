<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\CostEstimation;
use App\Models\Project;

class CostEstimationController extends Controller
{
    // List all records
    public function index(): void
    {
        $pdo = Database::pdo();
        $records = CostEstimation::all($pdo);

        $this->view('costestimation/index', [
            'title' => 'Cost Estimation',
            'records' => $records,
        ], 'layouts/base');
    }

    // API endpoint to get records as JSON with pagination
    public function apiRecords(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = Database::pdo();

            // Get pagination parameters from query string
            $page = (int)($_GET['page'] ?? 1);
            $pageSize = (int)($_GET['pageSize'] ?? 500); // Default 500 records per page
            $pageSize = min($pageSize, 1000); // Max 1000 per page
            $page = max($page, 1);

            // Get total count
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM costestimation");
            $countResult = $countStmt->fetch(\PDO::FETCH_ASSOC);
            $total = (int)($countResult['count'] ?? 0);
            $totalPages = ceil($total / $pageSize);

            // Get paginated data
            $offset = ($page - 1) * $pageSize;
            $sql = "SELECT * FROM costestimation ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                'data' => $records,
                'pagination' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'total' => $total,
                    'totalPages' => $totalPages
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // Get projects for dropdown
    public function getProjects(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT DISTINCT name FROM project WHERE name IS NOT NULL AND name != '' ORDER BY name");
            $projects = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                'data' => $projects
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // Show create form
    public function create(): void
    {
        $this->view('costestimation/create', [
            'title' => 'Cost Estimation - Yeni Kayıt',
        ]);
    }

    // Store new record
    public function store(): void
    {
        $pdo = Database::pdo();

        try {
            $data = [
                'proje' => trim($_POST['proje'] ?? ''),
                'cost_code' => trim($_POST['cost_code'] ?? ''),
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'tur' => trim($_POST['tur'] ?? ''),
                'birim_maliyet' => $_POST['birim_maliyet'] ?? null,
                'currency' => trim($_POST['currency'] ?? ''),
                'date' => $_POST['date'] ?? null,
                'kur' => $_POST['kur'] ?? null,
                'birim' => trim($_POST['birim'] ?? ''),
                'kapsam' => trim($_POST['kapsam'] ?? ''),
                'tutar_try_kdv_haric' => $_POST['tutar_try_kdv_haric'] ?? null,
                'kdv_orani' => trim($_POST['kdv_orani'] ?? ''),
                'tutar_try_kdv_dahil' => $_POST['tutar_try_kdv_dahil'] ?? null,
                'not_field' => trim($_POST['not_field'] ?? ''),
                'path' => trim($_POST['path'] ?? ''),
                'yuklenici' => trim($_POST['yuklenici'] ?? ''),
                'karsi_hesap_ismi' => trim($_POST['karsi_hesap_ismi'] ?? ''),
                'sozlesme_durumu' => trim($_POST['sozlesme_durumu'] ?? ''),
            ];

            CostEstimation::create($pdo, $data);

            header('Location: /costestimation', true, 302);
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            echo 'Hata: ' . htmlspecialchars($e->getMessage());
        }
    }

    // Show edit form
    public function edit(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz ID';
            return;
        }

        $record = CostEstimation::find($pdo, $id);
        if (!$record) {
            http_response_code(404);
            echo 'Kayıt bulunamadı';
            return;
        }

        $this->view('costestimation/edit', [
            'title' => 'Cost Estimation - Düzenle',
            'record' => $record,
        ]);
    }

    // Update record
    public function update(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz ID';
            return;
        }

        $record = CostEstimation::find($pdo, $id);
        if (!$record) {
            http_response_code(404);
            echo 'Kayıt bulunamadı';
            return;
        }

        try {
            $data = [
                'proje' => trim($_POST['proje'] ?? ''),
                'cost_code' => trim($_POST['cost_code'] ?? ''),
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'tur' => trim($_POST['tur'] ?? ''),
                'birim_maliyet' => $_POST['birim_maliyet'] ?? null,
                'currency' => trim($_POST['currency'] ?? ''),
                'date' => $_POST['date'] ?? null,
                'kur' => $_POST['kur'] ?? null,
                'birim' => trim($_POST['birim'] ?? ''),
                'kapsam' => trim($_POST['kapsam'] ?? ''),
                'tutar_try_kdv_haric' => $_POST['tutar_try_kdv_haric'] ?? null,
                'kdv_orani' => trim($_POST['kdv_orani'] ?? ''),
                'tutar_try_kdv_dahil' => $_POST['tutar_try_kdv_dahil'] ?? null,
                'not_field' => trim($_POST['not_field'] ?? ''),
                'path' => trim($_POST['path'] ?? ''),
                'yuklenici' => trim($_POST['yuklenici'] ?? ''),
                'karsi_hesap_ismi' => trim($_POST['karsi_hesap_ismi'] ?? ''),
                'sozlesme_durumu' => trim($_POST['sozlesme_durumu'] ?? ''),
            ];

            CostEstimation::update($pdo, $id, $data);

            header('Location: /costestimation', true, 302);
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            echo 'Hata: ' . htmlspecialchars($e->getMessage());
        }
    }

    // Delete record
    public function destroy(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz ID';
            return;
        }

        try {
            CostEstimation::delete($pdo, $id);
            header('Location: /costestimation', true, 302);
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            echo 'Hata: ' . htmlspecialchars($e->getMessage());
        }
    }

    // Bulk upload records
    public function bulkUpload(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(600);

        try {
            error_log('[CostEstimation BulkUpload] Starting...');

            // Get payload from POST or raw body
            $payloadJson = $_POST['payload'] ?? null;
            if (!$payloadJson) {
                $raw = file_get_contents('php://input') ?: '';
                if ($raw) {
                    $parsed = json_decode($raw, true);
                    if (isset($parsed['payload'])) $payloadJson = (string)$parsed['payload'];
                    elseif (isset($parsed['rows']) && is_array($parsed['rows'])) {
                        $payloadJson = json_encode(['rows' => $parsed['rows']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }

            if (!$payloadJson) {
                http_response_code(400);
                echo json_encode(['message' => 'payload missing']);
                ob_end_flush();
                error_log('[CostEstimation BulkUpload] Error: payload missing');
                return;
            }

            $payload = json_decode($payloadJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'payload not valid JSON']);
                ob_end_flush();
                error_log('[CostEstimation BulkUpload] Error: invalid JSON - ' . json_last_error_msg());
                return;
            }

            $rows = $payload['rows'] ?? null;
            if (!is_array($rows) || count($rows) < 2) {
                http_response_code(422);
                echo json_encode(['message' => 'rows must include header + at least 1 data row']);
                ob_end_flush();
                error_log('[CostEstimation BulkUpload] Error: rows invalid');
                return;
            }

            error_log('[CostEstimation BulkUpload] Received ' . count($rows) . ' rows');

            $headers = array_map(static fn($h) => trim((string)$h), (array)$rows[0]);
            $dataRows = array_slice($rows, 1);

            // Column name mapping from Excel headers to database fields
            $columnMap = [
                'PROJE' => 'proje',
                'COST CODE' => 'cost_code',
                'ACIKLAMA' => 'aciklama',
                'TUR' => 'tur',
                'BIRIM MALIYET' => 'birim_maliyet',
                'CURRENCY' => 'currency',
                'DATE' => 'date',
                'KUR' => 'kur',
                'BIRIM' => 'birim',
                'KAPSAM' => 'kapsam',
                'TUTAR TRY (KDV HARIC)' => 'tutar_try_kdv_haric',
                'KDV ORANI' => 'kdv_orani',
                'TUTAR TRY (KDV DAHIL)' => 'tutar_try_kdv_dahil',
                'NOT' => 'not_field',
                'PATH' => 'path',
                'YUKLENICI' => 'yuklenici',
                'KARSI HESAP ISMI' => 'karsi_hesap_ismi',
                'SOZLESME DURUMU' => 'sozlesme_durumu',
            ];

            $pdo = Database::pdo();
            $inserted = 0;
            $errors = [];

            foreach ($dataRows as $rowNum => $values) {
                $record = [];
                foreach ($headers as $colIdx => $header) {
                    if (isset($columnMap[$header])) {
                        $dbColumn = $columnMap[$header];
                        $value = $values[$colIdx] ?? null;

                        if ($value === '' || $value === null) {
                            $record[$dbColumn] = null;
                        } else {
                            $record[$dbColumn] = $value;
                        }
                    }
                }

                try {
                    CostEstimation::create($pdo, $record);
                    $inserted++;
                } catch (\Exception $e) {
                    $errors[] = 'Satır ' . ($rowNum + 2) . ': ' . $e->getMessage();
                    if (count($errors) >= 10) {
                        break;
                    }
                }
            }

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'inserted' => $inserted,
                    'message' => "Bazı satırlar yüklenmedi. Başarılı: $inserted. Hatalar: " . implode('; ', array_slice($errors, 0, 5))
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                ob_end_flush();
                error_log('[CostEstimation BulkUpload] Partial success: ' . $inserted . ' records, errors: ' . count($errors));
                return;
            }

            http_response_code(200);
            echo json_encode([
                'inserted' => $inserted,
                'message' => "$inserted cost estimation kaydı başarıyla yüklendi"
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            error_log('[CostEstimation BulkUpload] Success: inserted ' . $inserted . ' records');
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Exception: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            error_log('[CostEstimation BulkUpload] Exception: ' . $e->getMessage());
        }
    }
}
