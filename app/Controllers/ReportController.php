<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Project;
use App\Models\CostCode;
use App\Models\Tevkifat;

class ReportController extends Controller
{
    public function projectImage(): void
    {
        // Get image filename from URL parameter
        $filename = $_GET['file'] ?? '';

        if (!$filename) {
            http_response_code(400);
            die('No file specified');
        }

        // Build full path - prevent directory traversal
        $filename = basename($filename); // Only allow filename, no paths
        $filepath = dirname(__DIR__, 2) . '/storage/projects/' . $filename;

        // Check if file exists
        if (!file_exists($filepath)) {
            http_response_code(404);
            die('File not found');
        }

        // Determine MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        // Send file
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    public function debug(): void
    {
        $pdo = Database::pdo();

        header('Content-Type: application/json; charset=utf-8');

        // Test: Get project data
        $stmt = $pdo->prepare("SELECT * FROM project WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => 'KUNDU VIVA']);
        $project = $stmt->fetch(\PDO::FETCH_ASSOC);

        echo json_encode([
            'project_name' => $project['name'] ?? null,
            'project_uuid' => $project['uuid'] ?? null,
            'project_image_url' => $project['image_url'] ?? null,
            'all_columns' => array_keys($project)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        die();
    }

    public function index(): void
    {
        $pdo = Database::pdo();

        // Get filter parameters
        $reportDate = $_GET['reportDate'] ?? $_GET['date'] ?? date('Y-m-d');
        $reportDateFormatted = date('Ym', strtotime($reportDate));
        $selectedProject = $_GET['project'] ?? 'KUNDU VIVA';
        $startDate = $_GET['start_date'] ?? $_GET['startDate'] ?? '';
        $endDate = $_GET['end_date'] ?? $_GET['endDate'] ?? '';

        // Get projects for dropdown
        $projects = Project::all($pdo);

        // Get current project info for display
        $currentProject = null;
        if ($selectedProject && $selectedProject !== '*') {
            $stmt = $pdo->prepare("SELECT * FROM project WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $selectedProject]);
            $currentProject = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        // Get cost codes - sorted by cost_code
        $costCodes = CostCode::all($pdo);
        usort($costCodes, function ($a, $b) {
            return strcmp($a['cost_code'] ?? '', $b['cost_code'] ?? '');
        });

        // Get data from all tables
        $costEstimation = $this->getCostEstimationData($pdo, $selectedProject, $startDate, $endDate);
        $barter = $this->getBarterData($pdo, $selectedProject, $startDate, $endDate);
        $muhasebe = $this->getMuhasebeData($pdo, $selectedProject, $startDate, $endDate);
        $bakiye = $this->getBakiyeData($pdo, $selectedProject, $startDate, $endDate);
        $tevkifat = $this->getTevkifatData($pdo, $selectedProject, $reportDate);

        // Build report data
        $reportData = $this->buildReportData($costCodes, $costEstimation, $barter, $muhasebe, $bakiye, $tevkifat, $startDate, $endDate, $reportDate);

        // Generate month columns if date range is selected
        $monthColumns = [];
        if ($startDate && $endDate) {
            $monthColumns = $this->generateMonthColumns($startDate, $endDate);
        }

        $this->view('reports/index', [
            'title' => 'Mali Rapor',
            'reportDate' => $reportDate,
            'reportDateFormatted' => $reportDateFormatted,
            'selectedProject' => $selectedProject,
            'currentProject' => $currentProject,
            'projects' => $projects,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'monthColumns' => $monthColumns,
        ], 'layouts/base');
    }

    public function dashboard(): void
    {
        $pdo = Database::pdo();

        // Get filter parameters
        $reportDate = $_GET['reportDate'] ?? $_GET['date'] ?? date('Y-m-d');
        $selectedProject = $_GET['project'] ?? 'KUNDU VIVA';
        $startDate = $_GET['start_date'] ?? $_GET['startDate'] ?? '';
        $endDate = $_GET['end_date'] ?? $_GET['endDate'] ?? '';

        // Get projects for dropdown
        $projects = Project::all($pdo);

        // Get current project info for display
        $currentProject = null;
        if ($selectedProject && $selectedProject !== '*') {
            $stmt = $pdo->prepare("SELECT * FROM project WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $selectedProject]);
            $currentProject = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Debug: Log project image_url
            error_log("DEBUG Dashboard - Project: " . json_encode([
                'name' => $currentProject['name'] ?? null,
                'uuid' => $currentProject['uuid'] ?? null,
                'image_url' => $currentProject['image_url'] ?? null
            ]));
        }

        // Get cost codes
        $costCodes = CostCode::all($pdo);
        usort($costCodes, function ($a, $b) {
            return strcmp($a['cost_code'] ?? '', $b['cost_code'] ?? '');
        });

        // Get data from all tables
        $costEstimation = $this->getCostEstimationData($pdo, $selectedProject, $startDate, $endDate);
        $barter = $this->getBarterData($pdo, $selectedProject, $startDate, $endDate);
        $muhasebe = $this->getMuhasebeData($pdo, $selectedProject, $startDate, $endDate);
        $bakiye = $this->getBakiyeData($pdo, $selectedProject, $startDate, $endDate);
        $tevkifat = $this->getTevkifatData($pdo, $selectedProject, $reportDate);

        // Build report data
        $reportData = $this->buildReportData($costCodes, $costEstimation, $barter, $muhasebe, $bakiye, $tevkifat, $startDate, $endDate, $reportDate);

        // Calculate summary metrics
        $summaryMetrics = $this->calculateSummaryMetrics($reportData);

        // Get top cost codes by spending
        $topCostCodes = $this->getTopCostCodes($reportData, 10);

        $this->view('reports/dashboard', [
            'title' => 'Mali Rapor - Kontrol Paneli',
            'reportDate' => $reportDate,
            'selectedProject' => $selectedProject,
            'currentProject' => $currentProject,
            'projects' => $projects,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summaryMetrics' => $summaryMetrics,
            'topCostCodes' => $topCostCodes,
            'reportData' => $reportData,
        ], 'layouts/base');
    }

    private function calculateSummaryMetrics($reportData): array
    {
        $metrics = [
            'toplam_kdv_dahil' => 0,
            'tahakkuk_edilen' => 0,
            'kalan_kdv_dahil' => 0,
            'odenmis_gunluk' => 0,
            'odenecek_gunluk' => 0,
            'odenmis_aylik' => 0,
            'odenecek_aylik' => 0,
            'tevkifat' => 0,
            'barter_gerceklesen' => 0,
            'barter_planlanan' => 0,
            'tarihi_belirsiz_borclar' => 0,
        ];

        // First, identify all leaf cost codes (cost codes that have no children)
        $costCodeSet = [];
        foreach ($reportData as $row) {
            $costCodeSet[$row['cost_code']] = true;
        }

        // Check which cost codes are leaf nodes (don't have children)
        $leafCodes = [];
        foreach ($reportData as $row) {
            $code = $row['cost_code'];
            $isLeaf = true;

            // Check if this code has any children (codes starting with this code + separator)
            foreach ($costCodeSet as $checkCode => $_) {
                if ($checkCode !== $code && (
                    strpos($checkCode, $code . '-') === 0 ||
                    strpos($checkCode, $code . '.') === 0
                )) {
                    $isLeaf = false;
                    break;
                }
            }

            if ($isLeaf) {
                $leafCodes[$code] = true;
            }
        }

        // Only sum leaf-level items to avoid double-counting
        foreach ($reportData as $row) {
            if (isset($leafCodes[$row['cost_code']])) {
                $metrics['toplam_kdv_dahil'] += floatval($row['toplam_kdv_dahil'] ?? 0);
                $metrics['tahakkuk_edilen'] += floatval($row['tahakkuk_edilen'] ?? 0);
                $metrics['kalan_kdv_dahil'] += floatval($row['kalan_kdv_dahil'] ?? 0);
                $metrics['odenmis_gunluk'] += floatval($row['odenmis_gunluk'] ?? 0);
                $metrics['odenecek_gunluk'] += floatval($row['odenecek_gunluk'] ?? 0);
                $metrics['odenmis_aylik'] += floatval($row['odenmis_aylik'] ?? 0);
                $metrics['odenecek_aylik'] += floatval($row['odenecek_aylik'] ?? 0);
                $metrics['tevkifat'] += floatval($row['tevkifat'] ?? 0);
                $metrics['barter_gerceklesen'] += floatval($row['gerceklesen_barter'] ?? 0);
                $metrics['barter_planlanan'] += floatval($row['planlanan_barter'] ?? 0);
                $metrics['tarihi_belirsiz_borclar'] += floatval($row['tarihi_belirsiz_borclar'] ?? 0);
            }
        }

        return $metrics;
    }

    private function getTopCostCodes($reportData, $limit = 10): array
    {
        $costCodes = [];

        foreach ($reportData as $row) {
            $level = intval($row['level'] ?? 1);
            // Only consider level 2 items (main categories)
            if ($level == 2) {
                $costCodes[] = [
                    'code' => $row['cost_code'] ?? '',
                    'description' => $row['cost_code_aciklama'] ?? '',
                    'amount' => floatval($row['toplam_kdv_dahil'] ?? 0),
                    'spent' => floatval($row['tahakkuk_edilen'] ?? 0),
                ];
            }
        }

        // Sort by spending (tahakkuk_edilen)
        usort($costCodes, function ($a, $b) {
            return $b['spent'] <=> $a['spent'];
        });

        return array_slice($costCodes, 0, $limit);
    }

    private function getCostEstimationData($pdo, $selectedProject, $startDate, $endDate)
    {
        $sql = "SELECT * FROM costestimation";
        $conditions = [];

        if ($selectedProject !== '*') {
            $conditions[] = "proje = :project";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        if ($selectedProject !== '*') {
            $stmt->bindValue(':project', $selectedProject);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBarterData($pdo, $selectedProject, $startDate, $endDate)
    {
        $sql = "SELECT * FROM barter";
        $conditions = [];

        if ($selectedProject !== '*') {
            $conditions[] = "proje = :project";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        if ($selectedProject !== '*') {
            $stmt->bindValue(':project', $selectedProject);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getMuhasebeData($pdo, $selectedProject, $startDate, $endDate)
    {
        $sql = "SELECT * FROM muhasebe";
        $conditions = [];

        if ($selectedProject !== '*') {
            $conditions[] = "proje = :project";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        if ($selectedProject !== '*') {
            $stmt->bindValue(':project', $selectedProject);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBakiyeData($pdo, $selectedProject, $startDate, $endDate)
    {
        $sql = "SELECT * FROM bakiye";
        $conditions = [];

        if ($selectedProject !== '*') {
            $conditions[] = "proje = :project";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        if ($selectedProject !== '*') {
            $stmt->bindValue(':project', $selectedProject);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getTevkifatData($pdo, $selectedProject, $reportDate = null)
    {
        $sql = "SELECT * FROM tevkifat";
        $conditions = [];

        if ($selectedProject !== '*') {
            $conditions[] = "proje = :project";
        }

        // Filter by reportDate if provided (include records with tarih <= reportDate)
        if ($reportDate) {
            $conditions[] = "tarih <= :reportDate";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        if ($selectedProject !== '*') {
            $stmt->bindValue(':project', $selectedProject);
        }
        if ($reportDate) {
            $stmt->bindValue(':reportDate', $reportDate);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function buildReportData($costCodes, $costEstimation, $barter, $muhasebe, $bakiye, $tevkifat, $startDate, $endDate, $reportDate = null)
    {
        $reportData = [];

        // Initialize month columns if date range selected
        $monthColumns = [];
        if ($startDate && $endDate) {
            $monthColumns = $this->generateMonthColumns($startDate, $endDate);
        }

        foreach ($costCodes as $cc) {
            $costCode = $cc['cost_code'] ?? '';
            if (empty($costCode)) continue;

            $row = [
                'level' => $cc['level'] ?? '',
                'cost_code' => $costCode,
                'cost_code_aciklama' => $cc['muhasebe_kodu_aciklama'] ?? '',
                'toplam_kdv_haric' => 0,
                'toplam_kdv_dahil' => 0,
                'kalan_kdv_dahil' => 0,
                'odenmis_gunluk' => 0,
                'odenecek_gunluk' => 0,
                'tevkifat' => 0,
                'odenmis_aylik' => 0,
                'odenecek_aylik' => 0,
                'gerceklesen_barter' => 0,
                'barter_orani' => 0,
                'planlanan_barter' => 0,
                'tarihi_belirsiz_borclar' => 0,
                'tahakkuk_edilen' => 0,
                'monthly_data' => [],
            ];

            // Initialize monthly data
            foreach ($monthColumns as $month) {
                $row['monthly_data'][$month] = 0;
            }

            // Aggregate data from cost estimation using prefix matching
            foreach ($costEstimation as $ce) {
                $ceCostCode = $ce['cost_code'] ?? '';
                // Match if exact match OR if the cost estimation cost code starts with the row's cost code followed by a separator
                if (
                    $ceCostCode === $costCode ||
                    (strpos($ceCostCode, $costCode . '-') === 0) ||
                    (strpos($ceCostCode, $costCode . '.') === 0)
                ) {
                    $row['toplam_kdv_haric'] += floatval($ce['tutar_try_kdv_haric'] ?? 0);
                    $row['toplam_kdv_dahil'] += floatval($ce['tutar_try_kdv_dahil'] ?? 0);
                }
            }

            // Aggregate data from barter using prefix matching
            $totalBarter = 0;
            foreach ($barter as $b) {
                $bCostCode = $b['cost_code'] ?? '';
                // Match if exact match OR if the barter cost code starts with the row's cost code followed by a separator
                if (
                    $bCostCode === $costCode ||
                    (strpos($bCostCode, $costCode . '-') === 0) ||
                    (strpos($bCostCode, $costCode . '.') === 0)
                ) {
                    $row['gerceklesen_barter'] += floatval($b['barter_gerceklesen'] ?? 0);
                    $row['planlanan_barter'] += floatval($b['barter_planlanan_tutar'] ?? 0);
                    $totalBarter += floatval($b['barter_gerceklesen'] ?? 0);
                }
            }

            // Calculate barter orani
            if ($row['toplam_kdv_dahil'] > 0) {
                $row['barter_orani'] = ($totalBarter / $row['toplam_kdv_dahil']) * 100;
            }

            // Aggregate data from muhasebe - separate by vade_tarihi vs reportDate using prefix matching
            // Also calculate monthly buckets: ODENMIS AYLIK (up to end of report month) and ODENECEK AYLIK (after)
            // Track dates for tevkifat matching
            $muhasebeVadeTarihis = [];

            $reportDateTime = $reportDate ? \DateTime::createFromFormat('Y-m-d', $reportDate) : new \DateTime();
            $endOfReportMonth = (clone $reportDateTime)->modify('last day of this month');

            foreach ($muhasebe as $m) {
                $mCostCode = $m['cost_code'] ?? '';
                // Match if exact match OR if the muhasebe cost code starts with the row's cost code followed by a separator
                if (
                    $mCostCode === $costCode ||
                    (strpos($mCostCode, $costCode . '-') === 0) ||
                    (strpos($mCostCode, $costCode . '.') === 0)
                ) {
                    $amount = floatval($m['tutar_try'] ?? 0);
                    $vadeTarihi = $m['vade_tarihi'] ?? null;

                    // Track all vade_tarihi for this cost code for tevkifat matching
                    if ($vadeTarihi) {
                        $muhasebeVadeTarihis[$vadeTarihi] = true;
                    }

                    // TAHAKKUK EDILEN: Add ALL muhasebe records regardless of vade_tarihi
                    $row['tahakkuk_edilen'] += $amount;

                    // If no reportDate provided, skip date-based calculations
                    if (!$reportDate) {
                        continue;
                    }

                    // If no vade_tarihi, skip date-based calculations
                    if (!$vadeTarihi) {
                        continue;
                    }

                    $vadeDateTime = \DateTime::createFromFormat('Y-m-d', $vadeTarihi);
                    $reportTS = strtotime($reportDate);
                    $vadeTS = strtotime($vadeTarihi);

                    // ODENMIS GUNLUK: vade_tarihi <= reportDate (paid/due by report date)
                    if ($vadeTS <= $reportTS) {
                        $row['odenmis_gunluk'] += $amount;
                    }
                    // ODENECEK GUNLUK: vade_tarihi > reportDate (due after report date)
                    else {
                        $row['odenecek_gunluk'] += $amount;
                    }

                    // ODENMIS AYLIK: vade_tarihi <= end of report month (use string comparison for reliability)
                    $endOfReportMonthStr = $endOfReportMonth->format('Y-m-d');
                    if ($vadeTarihi <= $endOfReportMonthStr) {
                        $row['odenmis_aylik'] += $amount;
                    }
                    // ODENECEK AYLIK: vade_tarihi > end of report month
                    else {
                        $row['odenecek_aylik'] += $amount;
                    }
                }
            }

            // Aggregate data from bakiye using prefix matching
            // Bakiye should be added to TAHAKKUK EDILEN and ODENECEK AYLIK
            foreach ($bakiye as $bak) {
                $bakCostCode = $bak['cost_code'] ?? '';
                // Match if exact match OR if the bakiye cost code starts with the row's cost code followed by a separator
                if (
                    $bakCostCode === $costCode ||
                    (strpos($bakCostCode, $costCode . '-') === 0) ||
                    (strpos($bakCostCode, $costCode . '.') === 0)
                ) {
                    $bakAmount = floatval($bak['tutar_try'] ?? 0);
                    $row['tarihi_belirsiz_borclar'] += $bakAmount;
                    // Bakiye is also added to TAHAKKUK EDILEN (total accrued)
                    $row['tahakkuk_edilen'] += $bakAmount;
                    // Bakiye is also added to ODENECEK AYLIK (uncertain future debt)
                    $row['odenecek_aylik'] += $bakAmount;
                }
            }

            // Aggregate data from tevkifat using prefix matching (same structure as cost codes)
            // Add tevkifat to TAHAKKUK EDILEN, ODENMIS GUNLUK and ODENMIS AYLIK/ODENECEK AYLIK based on tarih
            foreach ($tevkifat as $t) {
                $tCostCode = $t['cost_code'] ?? '';
                $tTarih = $t['tarih'] ?? null;

                // Match cost code using prefix matching (same structure as other aggregations)
                $costCodeMatches = (
                    $tCostCode === $costCode ||
                    (strpos($tCostCode, $costCode . '-') === 0) ||
                    (strpos($tCostCode, $costCode . '.') === 0)
                );

                if (!$costCodeMatches) {
                    continue;
                }

                $tevkifatAmount = floatval($t['tevkifat'] ?? 0);

                // Add to the tevkifat column directly
                $row['tevkifat'] += $tevkifatAmount;

                // Add ALL matching tevkifat records to TAHAKKUK EDILEN
                $row['tahakkuk_edilen'] += $tevkifatAmount;

                // Add ALL matching tevkifat records to ODENMIS GUNLUK (up to reportDate)
                $row['odenmis_gunluk'] += $tevkifatAmount;

                // Also add to ODENMIS AYLIK/ODENECEK AYLIK based on tarih
                if ($tTarih) {
                    // Use string comparison for date reliability (YYYY-MM-DD format)
                    $endOfReportMonthStr = $endOfReportMonth->format('Y-m-d');

                    // ODENMIS AYLIK: tarih <= end of report month
                    if ($tTarih <= $endOfReportMonthStr) {
                        $row['odenmis_aylik'] += $tevkifatAmount;
                    }
                    // ODENECEK AYLIK: tarih > end of report month
                    else {
                        $row['odenecek_aylik'] += $tevkifatAmount;
                    }
                } else {
                    // If no tarih, add to ODENMIS AYLIK
                    $row['odenmis_aylik'] += $tevkifatAmount;
                }
            }

            // Calculate kalan
            $row['kalan_kdv_dahil'] = $row['toplam_kdv_dahil'] - $row['tahakkuk_edilen'] - $row['gerceklesen_barter'] - $row['planlanan_barter'];

            $reportData[$costCode] = $row;
        }

        return $reportData;
    }

    private function generateMonthColumns($startDate, $endDate)
    {
        $months = [];

        if (strlen($startDate) === 6 && strlen($endDate) === 6) {
            $startYear = (int)substr($startDate, 0, 4);
            $startMonth = (int)substr($startDate, 4, 2);
            $endYear = (int)substr($endDate, 0, 4);
            $endMonth = (int)substr($endDate, 4, 2);

            $currentYear = $startYear;
            $currentMonth = $startMonth;

            while ($currentYear < $endYear || ($currentYear === $endYear && $currentMonth <= $endMonth)) {
                $months[] = $currentYear . str_pad((string)$currentMonth, 2, '0', STR_PAD_LEFT);
                $currentMonth++;
                if ($currentMonth > 12) {
                    $currentMonth = 1;
                    $currentYear++;
                }
            }
        }

        return $months;
    }

    public function export(): void
    {
        $pdo = Database::pdo();

        // Get filter parameters
        $selectedProject = $_GET['project'] ?? 'KUNDU VIVA';

        // Get data from all tables
        $costEstimation = $this->getCostEstimationData($pdo, $selectedProject, '', '');
        $muhasebe = $this->getMuhasebeData($pdo, $selectedProject, '', '');
        $bakiye = $this->getBakiyeData($pdo, $selectedProject, '', '');
        $tevkifat = $this->getTevkifatData($pdo, $selectedProject, null);
        $barter = $this->getBarterData($pdo, $selectedProject, '', '');
        $costCodes = CostCode::all($pdo);

        // Build the report data for first sheet
        usort($costCodes, function ($a, $b) {
            return strcmp($a['cost_code'] ?? '', $b['cost_code'] ?? '');
        });
        $reportData = $this->buildReportData($costCodes, $costEstimation, $barter, $muhasebe, $bakiye, $tevkifat, '', '', date('Y-m-d'));

        // Create workbook with default sheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Use the default sheet for Cost Control (first sheet)
        $sheet = $spreadsheet->getActiveSheet();
        $this->setupCostControlSheet($sheet, $reportData, $costCodes);

        // Sheet 2: Muhasebe
        $this->addMuhasebeSheet($spreadsheet, $muhasebe);

        // Sheet 3: Bakiye
        $this->addBakiyeSheet($spreadsheet, $bakiye);

        // Sheet 4: Tevkifat
        $this->addTevkifatSheet($spreadsheet, $tevkifat);

        // Sheet 5: Barter
        $this->addBarterSheet($spreadsheet, $barter);

        // Sheet 6: Cost Estimation
        $this->addCostEstimationSheet($spreadsheet, $costEstimation);

        // Write file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        // Generate filename based on selected project
        if ($selectedProject === '*') {
            $filename = 'ALLPROJECTS_' . date('Ymd_His') . '.xlsx';
        } else {
            $filename = str_replace(' ', '_', $selectedProject) . '_' . date('Ymd_His') . '.xlsx';
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function setupCostControlSheet($sheet, $reportData, $costCodes): void
    {
        $sheet->setTitle('COST CONTROL');

        // Headers
        $headers = ['LEVEL', 'COST CODE', 'COST CODE ACIKLAMA', 'TOPLAM KDV HAR', 'TOPLAM KDV DAHIL', 'KALAN KDV DAHIL', 'ODENMIS GUNLUK', 'ODENECEK GUNLUK', 'TEVKIFAT', 'ODENMIS AYLIK', 'ODENECEK AYLIK', 'GERCEKLESEN BARTER', 'BARTER ORANI', 'PLANLANAN BARTER', 'TARIHI BELIRSIZ BORCLAR', 'TAHAKKUK EDILEN'];

        $sheet->fromArray($headers, NULL, 'A3');

        // Data starts from row 4
        $row = 4;
        foreach ($reportData as $costCode => $data) {
            $sheet->setCellValue('A' . $row, $data['level'] ?? '');
            $sheet->setCellValue('B' . $row, $data['cost_code'] ?? '');
            $sheet->setCellValue('C' . $row, $data['cost_code_aciklama'] ?? '');
            $sheet->setCellValue('D' . $row, $data['toplam_kdv_haric'] ?? 0);
            $sheet->setCellValue('E' . $row, $data['toplam_kdv_dahil'] ?? 0);
            $sheet->setCellValue('F' . $row, $data['kalan_kdv_dahil'] ?? 0);
            $sheet->setCellValue('G' . $row, $data['odenmis_gunluk'] ?? 0);
            $sheet->setCellValue('H' . $row, $data['odenecek_gunluk'] ?? 0);
            $sheet->setCellValue('I' . $row, $data['tevkifat'] ?? 0);
            $sheet->setCellValue('J' . $row, $data['odenmis_aylik'] ?? 0);
            $sheet->setCellValue('K' . $row, $data['odenecek_aylik'] ?? 0);
            $sheet->setCellValue('L' . $row, $data['gerceklesen_barter'] ?? 0);
            $sheet->setCellValue('M' . $row, $data['barter_orani'] ?? 0);
            $sheet->setCellValue('N' . $row, $data['planlanan_barter'] ?? 0);
            $sheet->setCellValue('O' . $row, $data['tarihi_belirsiz_borclar'] ?? 0);
            $sheet->setCellValue('P' . $row, $data['tahakkuk_edilen'] ?? 0);
            $row++;
        }

        // Create table (rows 3-end, where row 3 is headers)
        $endRow = $row - 1;
        if ($endRow >= 4) {
            $range = 'A3:P' . $endRow;
            $this->createTableRange($sheet, $range, 'tbl_CostControl');
        }

        // Auto-size columns
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addMuhasebeSheet($spreadsheet, $muhasebe): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('MUHASEBE');

        $headers = ['ID', 'PROJE', 'COST CODE', 'COST CODE ACIKLAMA', 'TARIH', 'TUTAR TRY', 'VADE TARIHI', 'ACIKLAMALAR'];
        $sheet->fromArray($headers, NULL, 'A3');

        $row = 4;
        foreach ($muhasebe as $item) {
            $sheet->setCellValue('A' . $row, $item['id'] ?? '');
            $sheet->setCellValue('B' . $row, $item['proje'] ?? '');
            $sheet->setCellValue('C' . $row, $item['cost_code'] ?? '');
            $sheet->setCellValue('D' . $row, $item['cost_code_aciklama'] ?? '');
            $this->formatDateCell($sheet, 'E' . $row, $item['tarih'] ?? '');
            $sheet->setCellValue('F' . $row, $item['tutar_try'] ?? 0);
            $this->formatDateCell($sheet, 'G' . $row, $item['vade_tarihi'] ?? '');
            $sheet->setCellValue('H' . $row, $item['aciklamalar'] ?? '');
            $row++;
        }

        $endRow = $row - 1;
        if ($endRow >= 4) {
            $range = 'A3:H' . $endRow;
            $this->createTableRange($sheet, $range, 'tbl_Muhasebe');
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addBakiyeSheet($spreadsheet, $bakiye): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('BAKIYE');

        $headers = ['ID', 'PROJE', 'COST CODE', 'COST CODE ACIKLAMA', 'TUTAR TRY', 'ACIKLAMALAR'];
        $sheet->fromArray($headers, NULL, 'A3');

        $row = 4;
        foreach ($bakiye as $item) {
            $sheet->setCellValue('A' . $row, $item['id'] ?? '');
            $sheet->setCellValue('B' . $row, $item['proje'] ?? '');
            $sheet->setCellValue('C' . $row, $item['cost_code'] ?? '');
            $sheet->setCellValue('D' . $row, $item['cost_code_aciklama'] ?? '');
            $sheet->setCellValue('E' . $row, $item['tutar_try'] ?? 0);
            $sheet->setCellValue('F' . $row, $item['aciklamalar'] ?? '');
            $row++;
        }

        $endRow = $row - 1;
        if ($endRow >= 4) {
            $range = 'A3:F' . $endRow;
            $this->createTableRange($sheet, $range, 'tbl_Bakiye');
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addTevkifatSheet($spreadsheet, $tevkifat): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('TEVKIFAT');

        $headers = ['ID', 'FIRMA', 'PROJE', 'TARIH', 'KARSI HESAP ISMI', 'COST CODE', 'VERGI MATRAHI', 'KDV ORANI', 'TEVKIFAT', 'TEVKIFAT ORANI', 'TOPLAM', 'KDV DAHIL', 'TEVKIFAT USD', 'DIKKATE ALINMAYACAKLAR'];
        $sheet->fromArray($headers, NULL, 'A3');

        $row = 4;
        foreach ($tevkifat as $item) {
            $sheet->setCellValue('A' . $row, $item['id'] ?? '');
            $sheet->setCellValue('B' . $row, $item['firma'] ?? '');
            $sheet->setCellValue('C' . $row, $item['proje'] ?? '');
            $this->formatDateCell($sheet, 'D' . $row, $item['tarih'] ?? '');
            $sheet->setCellValue('E' . $row, $item['karsi_hesap_ismi'] ?? '');
            $sheet->setCellValue('F' . $row, $item['cost_code'] ?? '');
            $sheet->setCellValue('G' . $row, $item['vergi_matrahı'] ?? 0);
            $sheet->setCellValue('H' . $row, $item['kdv_orani'] ?? 0);
            $sheet->setCellValue('I' . $row, $item['tevkifat'] ?? 0);
            $sheet->setCellValue('J' . $row, $item['tevkifat_orani'] ?? 0);
            $sheet->setCellValue('K' . $row, $item['toplam'] ?? 0);
            $sheet->setCellValue('L' . $row, $item['kdv_dahil'] ?? 0);
            $sheet->setCellValue('M' . $row, $item['tevkifat_usd'] ?? 0);
            $sheet->setCellValue('N' . $row, $item['dikkate_alinmayacaklar'] ?? '');
            $row++;
        }

        $endRow = $row - 1;
        if ($endRow >= 4) {
            $range = 'A3:N' . $endRow;
            $this->createTableRange($sheet, $range, 'tbl_Tevkifat');
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addBarterSheet($spreadsheet, $barter): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('BARTER');

        $headers = ['ID', 'PROJE', 'COST CODE', 'COST CODE ACIKLAMA', 'BARTER GERCEKLESEN', 'BARTER PLANLANAN TUTAR', 'ACIKLAMALAR'];
        $sheet->fromArray($headers, NULL, 'A1');

        $row = 2;
        foreach ($barter as $item) {
            $sheet->setCellValue('A' . $row, $item['id'] ?? '');
            $sheet->setCellValue('B' . $row, $item['proje'] ?? '');
            $sheet->setCellValue('C' . $row, $item['cost_code'] ?? '');
            $sheet->setCellValue('D' . $row, $item['cost_code_aciklama'] ?? '');
            $sheet->setCellValue('E' . $row, $item['barter_gerceklesen'] ?? 0);
            $sheet->setCellValue('F' . $row, $item['barter_planlanan_tutar'] ?? 0);
            $sheet->setCellValue('G' . $row, $item['aciklamalar'] ?? '');
            $row++;
        }

        $endRow = $row - 1;
        if ($endRow >= 4) {
            $range = 'A3:F' . $endRow;
            $this->createTableRange($sheet, $range, 'tbl_Barter');
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addCostEstimationSheet($spreadsheet, $costEstimation): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('COST ESTIMATION');

        $headers = ['ID', 'PROJE', 'COST CODE', 'COST CODE ACIKLAMA', 'TUTAR TRY KDV HARIC', 'TUTAR TRY KDV DAHIL', 'ACIKLAMALAR'];
        $sheet->fromArray($headers, NULL, 'A3');

        $row = 4;
        foreach ($costEstimation as $item) {
            $sheet->setCellValue('A' . $row, $item['id'] ?? '');
            $sheet->setCellValue('B' . $row, $item['proje'] ?? '');
            $sheet->setCellValue('C' . $row, $item['cost_code'] ?? '');
            $sheet->setCellValue('D' . $row, $item['cost_code_aciklama'] ?? '');
            $sheet->setCellValue('E' . $row, $item['tutar_try_kdv_haric'] ?? 0);
            $sheet->setCellValue('F' . $row, $item['tutar_try_kdv_dahil'] ?? 0);
            $sheet->setCellValue('G' . $row, $item['aciklamalar'] ?? '');
            $row++;
        }

        $endRow = $row - 1;
        if ($endRow >= 4) {
            $range = 'A3:G' . $endRow;
            $this->createTableRange($sheet, $range, 'tbl_CostEstimation');
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createTableRange($sheet, $range, $tableName): void
    {
        try {
            // Create a table using the Table class
            $table = new \PhpOffice\PhpSpreadsheet\Worksheet\Table($range, $tableName);
            $sheet->addTable($table);
        } catch (\Exception $e) {
            // If table creation fails, at least set the range as auto-filter
            $sheet->setAutoFilter($range);
        }
    }

    private function formatDateCell($sheet, $cellAddress, $dateValue): void
    {
        if (!$dateValue) return;

        try {
            // Try to parse the date string
            $timestamp = strtotime($dateValue);
            if ($timestamp !== false) {
                // Convert Unix timestamp to Excel date serial number
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp);
                $sheet->setCellValue($cellAddress, $excelDate);
                $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode('YYYY-MM-DD');
            }
        } catch (\Exception $e) {
            // If parsing fails, just set as text
            $sheet->setCellValue($cellAddress, $dateValue);
        }
    }
}
