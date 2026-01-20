<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Muhasebe;
use App\Models\ConvertCariHesapIsmi;
use App\Models\CostCodeAssignment;

class MuhasebeController extends Controller
{
    // List all records
    public function index(): void
    {
        $pdo = Database::pdo();
        $records = Muhasebe::all($pdo);

        $this->view('muhasebe/index', [
            'title' => 'Muhasebe',
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
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM muhasebe");
            $countResult = $countStmt->fetch(\PDO::FETCH_ASSOC);
            $total = (int)($countResult['count'] ?? 0);
            $totalPages = ceil($total / $pageSize);

            // Get paginated data
            $offset = ($page - 1) * $pageSize;
            $sql = "SELECT * FROM muhasebe ORDER BY id DESC LIMIT :limit OFFSET :offset";
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

    // Show create form
    public function create(): void
    {
        $this->view('muhasebe/create', [
            'title' => 'Muhasebe Kaydı Ekle',
        ]);
    }

    // Store new record
    public function store(): void
    {
        $pdo = Database::pdo();

        try {
            $data = [
                'proje' => trim($_POST['proje'] ?? ''),
                'tahakkuk_tarihi' => $_POST['tahakkuk_tarihi'] ?? null,
                'vade_tarihi' => $_POST['vade_tarihi'] ?? null,
                'cek_no' => trim($_POST['cek_no'] ?? ''),
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'aciklama2' => trim($_POST['aciklama2'] ?? ''),
                'aciklama3' => trim($_POST['aciklama3'] ?? ''),
                'tutar_try' => $_POST['tutar_try'] ?? null,
                'cari_hesap_ismi' => trim($_POST['cari_hesap_ismi'] ?? ''),
                'wb' => trim($_POST['wb'] ?? ''),
                'ws' => trim($_POST['ws'] ?? ''),
                'row_col' => trim($_POST['row_col'] ?? ''),
                'cost_code' => trim($_POST['cost_code'] ?? ''),
                'dikkate_alinmayacaklar' => trim($_POST['dikkate_alinmayacaklar'] ?? ''),
                'usd_karsiligi' => $_POST['usd_karsiligi'] ?? null,
                'id_text' => trim($_POST['id_text'] ?? ''),
                'id_veriler' => trim($_POST['id_veriler'] ?? ''),
                'id_odeme_plan_satinalma_odeme_onay_listesi' => trim($_POST['id_odeme_plan_satinalma_odeme_onay_listesi'] ?? ''),
                'not_field' => trim($_POST['not_field'] ?? ''),
                'not_ool_odeme_plani' => trim($_POST['not_ool_odeme_plani'] ?? ''),
            ];

            Muhasebe::create($pdo, $data);

            header('Location: /muhasebe', true, 302);
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

        $record = Muhasebe::find($pdo, $id);
        if (!$record) {
            http_response_code(404);
            echo 'Kayıt bulunamadı';
            return;
        }

        $this->view('muhasebe/edit', [
            'title' => 'Muhasebe Kaydı Düzenle',
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

        $record = Muhasebe::find($pdo, $id);
        if (!$record) {
            http_response_code(404);
            echo 'Kayıt bulunamadı';
            return;
        }

        try {
            $data = [
                'proje' => trim($_POST['proje'] ?? ''),
                'tahakkuk_tarihi' => $_POST['tahakkuk_tarihi'] ?? null,
                'vade_tarihi' => $_POST['vade_tarihi'] ?? null,
                'cek_no' => trim($_POST['cek_no'] ?? ''),
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'aciklama2' => trim($_POST['aciklama2'] ?? ''),
                'aciklama3' => trim($_POST['aciklama3'] ?? ''),
                'tutar_try' => $_POST['tutar_try'] ?? null,
                'cari_hesap_ismi' => trim($_POST['cari_hesap_ismi'] ?? ''),
                'wb' => trim($_POST['wb'] ?? ''),
                'ws' => trim($_POST['ws'] ?? ''),
                'row_col' => trim($_POST['row_col'] ?? ''),
                'cost_code' => trim($_POST['cost_code'] ?? ''),
                'dikkate_alinmayacaklar' => trim($_POST['dikkate_alinmayacaklar'] ?? ''),
                'usd_karsiligi' => $_POST['usd_karsiligi'] ?? null,
                'id_text' => trim($_POST['id_text'] ?? ''),
                'id_veriler' => trim($_POST['id_veriler'] ?? ''),
                'id_odeme_plan_satinalma_odeme_onay_listesi' => trim($_POST['id_odeme_plan_satinalma_odeme_onay_listesi'] ?? ''),
                'not_field' => trim($_POST['not_field'] ?? ''),
                'not_ool_odeme_plani' => trim($_POST['not_ool_odeme_plani'] ?? ''),
            ];

            Muhasebe::update($pdo, $id, $data);

            header('Location: /muhasebe', true, 302);
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
            Muhasebe::delete($pdo, $id);
            header('Location: /muhasebe', true, 302);
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
            error_log('[Muhasebe BulkUpload] Starting...');

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
                error_log('[Muhasebe BulkUpload] Error: payload missing');
                return;
            }

            $payload = json_decode($payloadJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'payload not valid JSON']);
                ob_end_flush();
                error_log('[Muhasebe BulkUpload] Error: invalid JSON - ' . json_last_error_msg());
                return;
            }

            $rows = $payload['rows'] ?? null;
            if (!is_array($rows) || count($rows) < 2) {
                http_response_code(422);
                echo json_encode(['message' => 'rows must include header + at least 1 data row']);
                ob_end_flush();
                error_log('[Muhasebe BulkUpload] Error: rows invalid');
                return;
            }

            error_log('[Muhasebe BulkUpload] Received ' . count($rows) . ' rows');

            $headers = array_map(static fn($h) => trim((string)$h), (array)$rows[0]);
            $dataRows = array_slice($rows, 1);

            // Column name mapping from Excel headers to database fields
            $columnMap = [
                'Proje' => 'proje',
                'Tahakkuk Tarihi' => 'tahakkuk_tarihi',
                'Vade Tarihi' => 'vade_tarihi',
                'Çek No' => 'cek_no',
                'Açıklama' => 'aciklama',
                'Açıklama 2' => 'aciklama2',
                'Açıklama 3' => 'aciklama3',
                'Tutar (TRY)' => 'tutar_try',
                'Cari Hesap' => 'cari_hesap_ismi',
                'WB' => 'wb',
                'WS' => 'ws',
                'Row' => 'row_col',
                'Cost Code' => 'cost_code',
                'Dikkate Alınmayacaklar' => 'dikkate_alinmayacaklar',
                'USD Karşılığı' => 'usd_karsiligi',
                'ID (Text)' => 'id_text',
                'ID Veriler' => 'id_veriler',
                'ID Ödeme Plan' => 'id_odeme_plan_satinalma_odeme_onay_listesi',
                'Not' => 'not_field',
                'Not OOL/Ödeme' => 'not_ool_odeme_plani',
            ];

            $pdo = Database::pdo();
            $inserted = 0;
            $batchSize = 100;
            $batch = [];
            $errors = [];

            // Helper function to parse and validate dates
            $parseDate = function ($value) {
                if (!$value || $value === '' || $value === null) {
                    return null;
                }

                $value = trim((string)$value);

                // Try to detect if it's an Excel serial date (number)
                if (is_numeric($value) && $value > 0) {
                    // Excel stores dates as serial numbers starting from 1899-12-31
                    // Serial 1 = January 1, 1900, so epoch is December 31, 1899
                    // Excel has a leap year bug: it treats 1900 as a leap year (it wasn't)
                    // So for dates >= 60, we need to subtract 1 day
                    $num = intval($value);
                    $excelEpoch = \DateTime::createFromFormat('Y-m-d', '1899-12-31');
                    $date = clone $excelEpoch;

                    // Adjust for Excel's leap year bug
                    if ($num >= 60) {
                        $num--; // Account for the non-existent Feb 29, 1900
                    }

                    $date->modify('+' . $num . ' days');
                    return $date->format('Y-m-d');
                }

                // Try common date formats
                $formats = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
                foreach ($formats as $format) {
                    $parsed = \DateTime::createFromFormat($format, $value);
                    if ($parsed && $parsed->format($format) === $value) {
                        return $parsed->format('Y-m-d');
                    }
                }

                // If it looks like a date string but doesn't match formats, try strtotime
                if (preg_match('/\d{1,4}[-\/\.]\d{1,2}[-\/\.]\d{1,4}/', $value)) {
                    $parsed = strtotime($value);
                    if ($parsed !== false) {
                        return date('Y-m-d', $parsed);
                    }
                }

                // Return null if not a valid date
                return null;
            };

            foreach ($dataRows as $rowNum => $values) {
                $record = [];
                foreach ($headers as $colIdx => $header) {
                    if (isset($columnMap[$header])) {
                        $dbColumn = $columnMap[$header];
                        $value = $values[$colIdx] ?? null;

                        if ($value === '' || $value === null) {
                            $record[$dbColumn] = null;
                        } else {
                            // Special handling for date fields
                            if (in_array($dbColumn, ['tahakkuk_tarihi', 'vade_tarihi'])) {
                                $record[$dbColumn] = $parseDate($value);
                            } else {
                                $record[$dbColumn] = $value;
                            }
                        }
                    }
                }

                try {
                    // Apply cari hesap name conversion if mapping exists
                    if (!empty($record['cari_hesap_ismi'])) {
                        $record['cari_hesap_ismi'] = ConvertCariHesapIsmi::convert($pdo, $record['cari_hesap_ismi']);
                    }

                    // Apply cost code assignment if cost code is blank or contains 'X'
                    if (empty($record['cost_code']) || str_contains((string)$record['cost_code'], 'X')) {
                        // Build search text from all description fields and cari hesap
                        $searchText = implode(' ', array_filter([
                            $record['aciklama'] ?? '',
                            $record['aciklama2'] ?? '',
                            $record['aciklama3'] ?? '',
                            $record['cari_hesap_ismi'] ?? ''
                        ]));

                        if ($searchText) {
                            $assignedCode = CostCodeAssignment::assignCostCode($pdo, $searchText, $record['cost_code'] ?? '');
                            if ($assignedCode) {
                                $record['cost_code'] = $assignedCode;
                            }
                        }
                    }

                    Muhasebe::create($pdo, $record);
                    $inserted++;
                } catch (\Exception $e) {
                    $errors[] = 'Satır ' . ($rowNum + 2) . ': ' . $e->getMessage();
                    if (count($errors) >= 10) {
                        // Stop if too many errors
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
                error_log('[Muhasebe BulkUpload] Partial success: ' . $inserted . ' records, errors: ' . count($errors));
                return;
            }

            http_response_code(200);
            echo json_encode([
                'inserted' => $inserted,
                'message' => "$inserted muhasebe kaydı başarıyla yüklendi"
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            error_log('[Muhasebe BulkUpload] Success: inserted ' . $inserted . ' records');
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Exception: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            error_log('[Muhasebe BulkUpload] Exception: ' . $e->getMessage());
        }
    }
}
