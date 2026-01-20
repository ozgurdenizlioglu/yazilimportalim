<?php

// FILE: public/index.php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Controllers\FirmController;
use App\Controllers\ScanController;
use App\Controllers\AttendanceController;
use App\Controllers\ProjectController;
use App\Controllers\ContractController;
use App\Controllers\DisciplineController;
use App\Controllers\MuhasebeController;
use App\Controllers\BakiyeController;
use App\Controllers\CostEstimationController;
use App\Controllers\BarterController;
use App\Controllers\CostCodeController;
use App\Controllers\TevkifatController;
use App\Controllers\ReportController;
use App\Controllers\AdminController;
use App\Controllers\DiagnosticController;
use App\Controllers\BoqController;
use App\Controllers\TutanakController;

// .env yükle
$root = dirname(__DIR__);
if (file_exists($root . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

// Session başlat
session_start();

// Dil yöneticisini başlat
\App\Language\LanguageManager::init($root);

// Load global helper functions (required for views)
require_once __DIR__ . '/../app/Core/HelpersFunctions.php';

// Hata gösterimi
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : 0);

// Config (gerekliyse)
$config = require __DIR__ . '/../app/config.php';

// Language switching handler
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'tr'])) {
        setLanguage($lang);
        // Redirect to referrer or home
        $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
        $url = strtok($referrer, '?');
        header("Location: {$url}");
        exit;
    }
}

// Router
$router = new Router();

// Root
$router->get('/', [HomeController::class, 'index']);

// Rotalar - users
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/store', [UserController::class, 'store']);
$router->get('/users/edit', [UserController::class, 'edit']); // ?id=...
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/delete', [UserController::class, 'destroy']);
$router->post('/users/bulk-upload', [UserController::class, 'bulkUpload']);
$router->get('/users/template', [UserController::class, 'downloadTemplate']);

// QR/Scan/Attendance
$router->get('/scan', [ScanController::class, 'show']);
$router->get('/attendance', [AttendanceController::class, 'index']);
$router->get('/attendance/report', [AttendanceController::class, 'report']);
$router->post('/api/attendance/scan', [AttendanceController::class, 'store']);

// Geçici: token üretimi (sadece geliştirme)
$router->get('/users/token', [UserController::class, 'token']);

// Rotalar - firms
$router->get('/firms', [FirmController::class, 'index']);
$router->get('/firms/create', [FirmController::class, 'create']);
$router->post('/firms/store', [FirmController::class, 'store']);
$router->get('/firms/edit', [FirmController::class, 'edit']); // ?id=...
$router->post('/firms/update', [FirmController::class, 'update']);
$router->post('/firms/delete', [FirmController::class, 'destroy']);
$router->post('/firms/bulk-upload', [FirmController::class, 'bulkUpload']);

// Rotalar - project
$router->get('/projects', [ProjectController::class, 'index']);
$router->get('/projects/create', [ProjectController::class, 'create']);
$router->post('/projects/store', [ProjectController::class, 'store']);
$router->get('/projects/edit', [ProjectController::class, 'edit']); // ?id=...
$router->post('/projects/update', [ProjectController::class, 'update']);
$router->post('/projects/delete', [ProjectController::class, 'destroy']);
$router->post('/projects/bulk-upload', [ProjectController::class, 'bulkUpload']);
$router->get('/projects/company-list', [ProjectController::class, 'companyList']); // API endpoint for company filtering

// Rotalar - contract
$router->get('/contracts', [ContractController::class, 'index']);
$router->get('/contracts/create', [ContractController::class, 'create']);
$router->post('/contracts/store', [ContractController::class, 'store']);
$router->get('/contracts/edit', [ContractController::class, 'edit']); // ?id=...
$router->post('/contracts/update', [ContractController::class, 'update']);
$router->post('/contracts/delete', [ContractController::class, 'destroy']);
$router->post('/contracts/bulk-upload', [ContractController::class, 'bulkUpload']);

// Contracts — JSON yardımcı uçlar
$router->get('/contracts/project-info', [ContractController::class, 'projectInfo']);
$router->get('/contracts/company-search', [ContractController::class, 'companySearch']);
$router->post('/contracts/company-create', [ContractController::class, 'companyCreate']);
$router->get('/contracts/company-list', [ContractController::class, 'companyList']);
$router->get('/contracts/company-top', [ContractController::class, 'companyTop']);
$router->get('/contracts/project-list', [ContractController::class, 'projectList']);
$router->get('/contracts/discipline-list', [ContractController::class, 'disciplineList']);
$router->get('/contracts/discipline-branch-list', [ContractController::class, 'disciplineBranchList']);
$router->get('/contracts/generate-word', [ContractController::class, 'generateWord']);
$router->get('/contracts/generate-pdf', [ContractController::class, 'generatePdf']);
$router->get('/contracts/get-uploaded-pdf', [ContractController::class, 'getUploadedPdf']);
$router->get('/contracts/get-pdf-by-title', [ContractController::class, 'getPdfByTitle']);
$router->get('/contracts/export-to-excel', [ContractController::class, 'exportToExcel']);
$router->get('/contracts/template-data', [ContractController::class, 'templateData']);
$router->get('/contracts/list-documents', [ContractController::class, 'listDocuments']);
$router->get('/contracts/download-document', [ContractController::class, 'downloadDocument']);
$router->get('/contracts/open-document-folder', [ContractController::class, 'openDocumentFolder']);
$router->post('/contracts/upload-signed-pdf', [ContractController::class, 'uploadSignedPdf']);
$router->post('/contracts/upload-pdf', [ContractController::class, 'uploadPdf']);
$router->post('/contracts/upload-document', [ContractController::class, 'uploadDocument']);
$router->post('/contracts/delete-document', [ContractController::class, 'deleteDocument']);
$router->post('/contracts/update-status', [ContractController::class, 'updateStatus']);

// Rotalar - discipline
$router->get('/disciplines', [DisciplineController::class, 'index']);
$router->get('/disciplines/create', [DisciplineController::class, 'create']);
$router->post('/disciplines/store', [DisciplineController::class, 'store']);
$router->get('/disciplines/edit', [DisciplineController::class, 'edit']); // ?id=...
$router->post('/disciplines/update', [DisciplineController::class, 'update']);
$router->post('/disciplines/delete', [DisciplineController::class, 'destroy']);
$router->post('/disciplines/store-branch', [DisciplineController::class, 'storeBranch']);

// Discipline API endpoints
$router->get('/api/disciplines', [DisciplineController::class, 'list']);
$router->get('/api/disciplines/branches', [DisciplineController::class, 'branches']);
$router->post('/api/disciplines/create', [DisciplineController::class, 'createApi']);
$router->post('/api/disciplines/create-branch', [DisciplineController::class, 'createBranchApi']);

// Rotalar - muhasebe
$router->get('/muhasebe', [MuhasebeController::class, 'index']);
$router->get('/muhasebe/api/records', [MuhasebeController::class, 'apiRecords']);
$router->get('/muhasebe/create', [MuhasebeController::class, 'create']);
$router->post('/muhasebe/store', [MuhasebeController::class, 'store']);
$router->get('/muhasebe/edit', [MuhasebeController::class, 'edit']); // ?id=...
$router->post('/muhasebe/update', [MuhasebeController::class, 'update']);
$router->post('/muhasebe/delete', [MuhasebeController::class, 'destroy']);
$router->post('/muhasebe/bulk-upload', [MuhasebeController::class, 'bulkUpload']);

// Rotalar - tevkifat
$router->get('/tevkifat', [TevkifatController::class, 'index']);
$router->get('/tevkifat/create', [TevkifatController::class, 'create']);
$router->post('/tevkifat/store', [TevkifatController::class, 'store']);
$router->get('/tevkifat/edit', [TevkifatController::class, 'edit']); // ?id=...
$router->post('/tevkifat/update', [TevkifatController::class, 'update']);
$router->get('/tevkifat/delete', [TevkifatController::class, 'delete']); // ?id=...
$router->post('/tevkifat/bulkUpload', [TevkifatController::class, 'bulkUpload']);
$router->get('/tevkifat/downloadTemplate', [TevkifatController::class, 'downloadTemplate']);

// Rotalar - bakiye
$router->get('/bakiye', [BakiyeController::class, 'index']);
$router->get('/bakiye/api/records', [BakiyeController::class, 'apiRecords']);
$router->get('/bakiye/create', [BakiyeController::class, 'create']);
$router->post('/bakiye/store', [BakiyeController::class, 'store']);
$router->get('/bakiye/edit', [BakiyeController::class, 'edit']); // ?id=...
$router->post('/bakiye/update', [BakiyeController::class, 'update']);
$router->post('/bakiye/delete', [BakiyeController::class, 'destroy']);
$router->post('/bakiye/bulk-upload', [BakiyeController::class, 'bulkUpload']);

// Rotalar - costestimation
$router->get('/costestimation', [CostEstimationController::class, 'index']);
$router->get('/costestimation/api/records', [CostEstimationController::class, 'apiRecords']);
$router->get('/costestimation/get-projects', [CostEstimationController::class, 'getProjects']);
$router->get('/costestimation/create', [CostEstimationController::class, 'create']);
$router->post('/costestimation/store', [CostEstimationController::class, 'store']);
$router->get('/costestimation/edit', [CostEstimationController::class, 'edit']); // ?id=...
$router->post('/costestimation/update', [CostEstimationController::class, 'update']);
$router->post('/costestimation/delete', [CostEstimationController::class, 'destroy']);
$router->post('/costestimation/bulk-upload', [CostEstimationController::class, 'bulkUpload']);

// Rotalar - tutanak
$router->get('/tutanak', [TutanakController::class, 'index']);
$router->get('/tutanak/create', [TutanakController::class, 'create']);
$router->get('/tutanak/edit', [TutanakController::class, 'edit']); // ?id=...
$router->post('/tutanak/store', [TutanakController::class, 'store']);
$router->post('/tutanak/update', [TutanakController::class, 'update']);
$router->post('/tutanak/delete', [TutanakController::class, 'delete']);

// Rotalar - barter
$router->get('/barter', [BarterController::class, 'index']);
$router->get('/barter/api/records', [BarterController::class, 'apiRecords']);
$router->get('/barter/get-projects', [BarterController::class, 'getProjects']);
$router->get('/barter/create', [BarterController::class, 'create']);
$router->post('/barter/store', [BarterController::class, 'store']);
$router->get('/barter/edit', [BarterController::class, 'edit']); // ?id=...
$router->post('/barter/update', [BarterController::class, 'update']);
$router->post('/barter/delete', [BarterController::class, 'destroy']);
$router->post('/barter/bulk-upload', [BarterController::class, 'bulkUpload']);

// Rotalar - costcodes
$router->get('/costcodes', [CostCodeController::class, 'index']);
$router->get('/costcodes/api/records', [CostCodeController::class, 'apiRecords']);
$router->get('/costcodes/create', [CostCodeController::class, 'create']);
$router->post('/costcodes/store', [CostCodeController::class, 'store']);
$router->get('/costcodes/edit', [CostCodeController::class, 'edit']); // ?id=...
$router->post('/costcodes/update', [CostCodeController::class, 'update']);
$router->post('/costcodes/delete', [CostCodeController::class, 'destroy']);
$router->post('/costcodes/bulk-upload', [CostCodeController::class, 'bulkUpload']);
$router->post('/costcodes/remove-duplicates', [CostCodeController::class, 'removeDuplicates']);

// Rotalar - reports
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/dashboard', [ReportController::class, 'dashboard']);
$router->get('/reports/debug', [ReportController::class, 'debug']);
$router->get('/reports/export', [ReportController::class, 'export']);
$router->get('/reports/project-image', [ReportController::class, 'projectImage']);

// Diagnostic routes
$router->get('/api/diagnostic/muhasebe', [DiagnosticController::class, 'muhasebeStatus']);

// BOQ routes
$router->get('/boq', [BoqController::class, 'index']);
$router->post('/boq/upload', [BoqController::class, 'upload']);
$router->post('/boq/import', [BoqController::class, 'import']);
$router->post('/boq/delete', [BoqController::class, 'delete']);

// Admin routes
$router->post('/admin/update-muhasebe-aciklama', [AdminController::class, 'updateMuhasebeAciklama']);

// İsteği çalıştır
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$router->dispatch($method, $path);
// FILE: app/Controllers/ContractController.php