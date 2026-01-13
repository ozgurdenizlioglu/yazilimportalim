<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Muhasebe;

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
                    Muhasebe::create($pdo, $record);
                    $inserted++;
                } catch (\Exception $e) {
                    http_response_code(400);
                    echo json_encode([
                        'message' => 'Satır ' . ($rowNum + 2) . "'de hata: " . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ob_end_flush();
                    error_log('[Muhasebe BulkUpload] Row ' . ($rowNum + 2) . ' error: ' . $e->getMessage());
                    return;
                }
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
