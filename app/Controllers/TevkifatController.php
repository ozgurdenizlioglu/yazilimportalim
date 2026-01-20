<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Tevkifat;
use App\Models\Project;
use App\Core\Helpers;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TevkifatController extends Controller
{
    public function index()
    {
        $pdo = Database::pdo();
        $selectedProject = $_GET['project'] ?? '*';

        $records = Tevkifat::all($pdo, $selectedProject);
        $projects = Project::all($pdo);

        // Handle JSON format request
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        return $this->view('tevkifat/index', [
            'title' => 'Tevkifat',
            'records' => $records,
            'projects' => $projects,
            'selectedProject' => $selectedProject
        ], 'layouts/base');
    }

    public function create()
    {
        $pdo = Database::pdo();
        $projects = Project::all($pdo);

        return $this->view('tevkifat/create', [
            'title' => 'Tevkifat Kaydı Ekle',
            'projects' => $projects
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /tevkifat');
            return;
        }

        $pdo = Database::pdo();

        $data = [
            'firma' => $_POST['firma'] ?? '',
            'proje' => $_POST['proje'] ?? '',
            'tarih' => $_POST['tarih'] ?? '',
            'karsi_hesap_ismi' => $_POST['karsi_hesap_ismi'] ?? '',
            'cost_code' => $_POST['cost_code'] ?? '',
            'vergi_matrahı' => $_POST['vergi_matrahı'] ?? '',
            'kdv_orani' => $_POST['kdv_orani'] ?? '',
            'tevkifat' => $_POST['tevkifat'] ?? '',
            'tevkifat_orani' => $_POST['tevkifat_orani'] ?? '',
            'toplam' => $_POST['toplam'] ?? '',
            'kdv_dahil' => $_POST['kdv_dahil'] ?? '',
            'tevkifat_usd' => $_POST['tevkifat_usd'] ?? '',
            'dikkate_alinmayacaklar' => $_POST['dikkate_alinmayacaklar'] ?? ''
        ];

        Tevkifat::create($pdo, $data);
        header('Location: /tevkifat');
    }

    public function edit($id)
    {
        $pdo = Database::pdo();
        $record = Tevkifat::find($pdo, (int)$id);
        if (!$record) {
            header('Location: /tevkifat');
            return;
        }

        $projects = Project::all($pdo);

        return $this->view('tevkifat/edit', [
            'title' => 'Tevkifat Kaydını Düzenle',
            'record' => $record,
            'projects' => $projects
        ]);
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /tevkifat');
            return;
        }

        $pdo = Database::pdo();

        $data = [
            'firma' => $_POST['firma'] ?? '',
            'proje' => $_POST['proje'] ?? '',
            'tarih' => $_POST['tarih'] ?? '',
            'karsi_hesap_ismi' => $_POST['karsi_hesap_ismi'] ?? '',
            'cost_code' => $_POST['cost_code'] ?? '',
            'vergi_matrahı' => $_POST['vergi_matrahı'] ?? '',
            'kdv_orani' => $_POST['kdv_orani'] ?? '',
            'tevkifat' => $_POST['tevkifat'] ?? '',
            'tevkifat_orani' => $_POST['tevkifat_orani'] ?? '',
            'toplam' => $_POST['toplam'] ?? '',
            'kdv_dahil' => $_POST['kdv_dahil'] ?? '',
            'tevkifat_usd' => $_POST['tevkifat_usd'] ?? '',
            'dikkate_alinmayacaklar' => $_POST['dikkate_alinmayacaklar'] ?? ''
        ];

        Tevkifat::update($pdo, (int)$id, $data);
        header('Location: /tevkifat');
    }

    public function delete($id)
    {
        $pdo = Database::pdo();
        Tevkifat::delete($pdo, (int)$id);
        header('Location: /tevkifat');
    }

    // Bulk upload records
    public function bulkUpload(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(600);

        try {
            error_log('[Tevkifat BulkUpload] Starting...');

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
                error_log('[Tevkifat BulkUpload] Error: payload missing');
                return;
            }

            $payload = json_decode($payloadJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'payload not valid JSON']);
                ob_end_flush();
                error_log('[Tevkifat BulkUpload] Error: invalid JSON - ' . json_last_error_msg());
                return;
            }

            $rows = $payload['rows'] ?? null;
            if (!is_array($rows) || count($rows) < 2) {
                http_response_code(422);
                echo json_encode(['message' => 'rows must include header + at least 1 data row']);
                ob_end_flush();
                error_log('[Tevkifat BulkUpload] Error: rows invalid');
                return;
            }

            error_log('[Tevkifat BulkUpload] Received ' . count($rows) . ' rows');

            $headers = array_map(static fn($h) => trim((string)$h), (array)$rows[0]);
            $dataRows = array_slice($rows, 1);

            // Column name mapping from Excel headers to database fields
            $columnMap = [
                'FIRMA' => 'firma',
                'PROJE' => 'proje',
                'TARIH' => 'tarih',
                'KARSI HESAP ISMI' => 'karsi_hesap_ismi',
                'COST CODE' => 'cost_code',
                'Vergi matrahı' => 'vergi_matrahı',
                'KDV Orani' => 'kdv_orani',
                'Tevkifat' => 'tevkifat',
                'Tevkifat Orani' => 'tevkifat_orani',
                'Toplam' => 'toplam',
                'KDV DAHIL' => 'kdv_dahil',
                'Tevkifat USD' => 'tevkifat_usd',
                'DIKKATE ALMA' => 'dikkate_alinmayacaklar',
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
                            if ($dbColumn === 'tarih') {
                                $record[$dbColumn] = $parseDate($value);
                            } else {
                                $record[$dbColumn] = $value;
                            }
                        }
                    }
                }

                try {
                    Tevkifat::create($pdo, $record);
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
                error_log('[Tevkifat BulkUpload] Partial success: ' . $inserted . ' records, errors: ' . count($errors));
                return;
            }

            http_response_code(200);
            echo json_encode([
                'inserted' => $inserted,
                'message' => "$inserted tevkifat kaydı başarıyla yüklendi"
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            error_log('[Tevkifat BulkUpload] Success: inserted ' . $inserted . ' records');
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Exception: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            error_log('[Tevkifat BulkUpload] Exception: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(): void
    {
        try {
            // Clear any existing output buffers
            if (ob_get_level()) {
                ob_end_clean();
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['FIRMA', 'PROJE', 'TARIH', 'KARSI HESAP ISMI', 'COST CODE', 'Vergi matrahı', 'KDV Orani', 'Tevkifat', 'Tevkifat Orani', 'Toplam', 'KDV DAHIL', 'Tevkifat USD', 'DIKKATE ALMA'];

            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }

            // Format header row
            $headerStyle = $sheet->getStyle('A1:M1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(Fill::FILL_SOLID);
            $headerStyle->getFill()->getStartColor()->setRGB('4472C4');
            $headerStyle->getFont()->getColor()->setRGB('FFFFFF');

            // Set column widths
            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setWidth(18);
            }

            // Send headers AFTER clearing output buffer
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename=tevkifat_template.xlsx');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        } catch (\Exception $e) {
            http_response_code(500);
            echo 'Error generating template: ' . $e->getMessage();
        }

        exit;
    }

    private function parseCSV($file)
    {
        $records = [];
        if (($handle = fopen($file, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if ($headers) {
                $headerMap = $this->mapHeaders($headers);

                while (($row = fgetcsv($handle)) !== false) {
                    if (empty(array_filter($row))) continue;

                    $record = $this->mapRow($row, $headerMap);
                    if (!empty(array_filter($record))) {
                        $records[] = $record;
                    }
                }
            }
            fclose($handle);
        }
        return $records;
    }

    private function parseExcel($file)
    {
        $records = [];

        try {
            require_once __DIR__ . '/../../vendor/autoload.php';

            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $headers[] = $cell->getValue();
                }
                break;
            }

            $headerMap = $this->mapHeaders($headers);

            foreach ($sheet->getRowIterator(2) as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if (empty(array_filter($rowData))) continue;

                $record = $this->mapRow($rowData, $headerMap);
                if (!empty(array_filter($record))) {
                    $records[] = $record;
                }
            }
        } catch (\Exception $e) {
            // Return empty array if parsing fails
        }

        return $records;
    }

    private function mapHeaders($headers)
    {
        $map = [];
        $columnNames = [
            'firma' => ['firma', 'company', 'şirket'],
            'proje' => ['proje', 'project', 'proj'],
            'tarih' => ['tarih', 'date', 'tarihi'],
            'karsi_hesap_ismi' => ['karsi', 'karşı', 'hesap', 'muhasebe', 'account', 'cari'],
            'cost_code' => ['cost', 'code', 'kod', 'maliyet'],
            'vergi_matrahı' => ['vergi', 'matrah', 'vergi matrahı'],
            'kdv_orani' => ['kdv', 'kdv oran', 'kdv orani', 'tax rate'],
            'tevkifat' => ['tevkifat', 'withholding', 'tevk'],
            'tevkifat_orani' => ['tevkifat oran', 'tevkifat orani', 'withholding rate'],
            'toplam' => ['toplam', 'total', 'sum'],
            'kdv_dahil' => ['kdv dahil', 'with tax', 'kdv included', 'including'],
            'tevkifat_usd' => ['usd', 'dolar', 'tevkifat usd', 'withholding usd'],
            'dikkate_alinmayacaklar' => ['dikkate', 'remark', 'not', 'açıklama']
        ];

        foreach ($headers as $idx => $header) {
            $normalized = strtolower(trim($header));
            foreach ($columnNames as $dbField => $variations) {
                if (
                    in_array($normalized, $variations) ||
                    str_contains($normalized, $variations[0])
                ) {
                    $map[$idx] = $dbField;
                    break;
                }
            }
        }

        return $map;
    }

    private function mapRow($row, $map)
    {
        $record = [
            'firma' => '',
            'proje' => '',
            'tarih' => '',
            'karsi_hesap_ismi' => '',
            'cost_code' => '',
            'vergi_matrahı' => '',
            'kdv_orani' => '',
            'tevkifat' => '',
            'tevkifat_orani' => '',
            'toplam' => '',
            'kdv_dahil' => '',
            'tevkifat_usd' => '',
            'dikkate_alinmayacaklar' => ''
        ];

        foreach ($map as $idx => $field) {
            if (isset($row[$idx])) {
                $record[$field] = $row[$idx];
            }
        }

        return $record;
    }
}
