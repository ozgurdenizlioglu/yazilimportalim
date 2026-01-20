<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Tutanak;
use App\Models\Units;

class TutanakController extends Controller
{
    private array $cfg;

    public function __construct()
    {
        $baseStorage = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
        $this->cfg = [
            'paths' => [
                'tutanak' => $baseStorage . DIRECTORY_SEPARATOR . 'tutanak',
            ],
        ];
    }

    // List all tutanak records
    public function index(): void
    {
        $pdo = Database::pdo();
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $rows = Tutanak::all($pdo, $projectId);

        $this->view('tutanak/index', [
            'title' => 'Tutanaklar',
            'tutanaks' => $rows,
            'projectId' => $projectId
        ], 'layouts/base');
    }

    // Show create form
    public function create(): void
    {
        $pdo = Database::pdo();

        // Get projects
        $projects = $pdo->query("SELECT id, name, short_name FROM project WHERE deleted_at IS NULL ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);

        // Get companies
        $companies = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);

        // Get units
        $units = Units::all($pdo);

        $this->view('tutanak/create', [
            'title' => 'Yeni Tutanak',
            'projects' => $projects,
            'companies' => $companies,
            'units' => $units
        ], 'layouts/base');
    }

    // Show edit form
    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /tutanak');
            return;
        }

        $pdo = Database::pdo();
        $tutanak = Tutanak::find($pdo, $id);

        if (!$tutanak) {
            header('Location: /tutanak');
            return;
        }

        // Get projects
        $projects = $pdo->query("SELECT id, name, short_name FROM project WHERE deleted_at IS NULL ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);

        // Get companies
        $companies = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);

        // Get units
        $units = Units::all($pdo);

        $this->view('tutanak/edit', [
            'title' => 'Tutanak Düzenle',
            'tutanak' => $tutanak,
            'projects' => $projects,
            'companies' => $companies,
            'units' => $units
        ], 'layouts/base');
    }

    // Store new tutanak
    public function store(): void
    {
        header('Content-Type: application/json');

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Get form data
            $projectId = (int)($_POST['project_id'] ?? 0);
            $tutanakNo = trim($_POST['tutanak_no'] ?? '');
            $odemeFirma = trim($_POST['odeme_yapilacak_firma'] ?? '');
            $kesintiFirma = trim($_POST['kesinti_yapilacak_firma'] ?? '');
            $tarih = $_POST['tarih'] ?? date('Y-m-d');
            $konu = trim($_POST['konu'] ?? '');
            $notText = trim($_POST['not_text'] ?? '');

            // Validation
            if ($projectId <= 0) {
                echo json_encode(['error' => 'Proje seçimi zorunludur']);
                return;
            }

            // Get project name
            $stmt = $pdo->prepare("SELECT name, short_name FROM project WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$project) {
                echo json_encode(['error' => 'Proje bulunamadı']);
                return;
            }

            $projectName = $project['short_name'] ?: $project['name'];

            // Generate title with date in YYYYMMDD format
            $dateForTitle = date('Ymd', strtotime($tarih));
            $baseTitle = Tutanak::generateTitle($projectName, $odemeFirma, $kesintiFirma, $dateForTitle);
            $tutanakTitle = Tutanak::getNextSequence($pdo, $baseTitle);

            // Parse items data from JSON
            $itemsJson = $_POST['items_json'] ?? '[]';
            $items = json_decode($itemsJson, true) ?: [];

            if (empty($items)) {
                echo json_encode(['error' => 'En az bir satır eklenmelidir']);
                return;
            }

            // Calculate total tutar
            $totalTutar = 0;
            foreach ($items as $item) {
                $tutar = $this->parseNumeric($item['tutar'] ?? '0');
                $totalTutar += $tutar;
            }

            // Get first item for main tutanak record (backwards compatibility)
            $firstItem = $items[0];

            // Prepare main tutanak data
            $data = [
                'tutanak_title' => $tutanakTitle,
                'project_id' => $projectId,
                'tutanak_no' => $tutanakNo,
                'malzeme_yevmiye_ceza' => $firstItem['malzeme'] ?? '',
                'birim_id' => !empty($firstItem['birim_id']) ? (int)$firstItem['birim_id'] : null,
                'birim_fiyat' => $this->parseNumeric($firstItem['birim_fiyat'] ?? '0'),
                'miktar' => $this->parseNumeric($firstItem['miktar'] ?? '0'),
                'odeme_yapilacak_firma' => $odemeFirma,
                'tutar' => $totalTutar, // Total amount
                'kesinti_yapilacak_firma' => $kesintiFirma,
                'not_text' => $notText,
                'tur' => $firstItem['tur'] ?? '',
                'tarih' => $tarih,
                'konu' => $konu,
                'pdf_path' => null,
                'created_by' => $_SESSION['user_id'] ?? null,
            ];

            // Create tutanak folder
            $tutanakFolder = $this->cfg['paths']['tutanak'] . DIRECTORY_SEPARATOR . $tutanakTitle;
            if (!is_dir($tutanakFolder)) {
                mkdir($tutanakFolder, 0755, true);
            }

            // Insert record
            $id = Tutanak::create($pdo, $data);

            // Store items data as JSON in not_text if multiple rows
            if (count($items) > 1) {
                $itemsInfo = "TUTANAK SATIRLARI:\n";
                foreach ($items as $index => $item) {
                    $itemsInfo .= sprintf(
                        "%d. Tür: %s | Malzeme: %s | Birim: %s | Miktar: %s | Birim Fiyat: %s | Tutar: %s\n",
                        $index + 1,
                        $item['tur'] ?? '',
                        $item['malzeme'] ?? '',
                        $item['birim_id'] ?? '',
                        $item['miktar'] ?? '0',
                        $item['birim_fiyat'] ?? '0',
                        $item['tutar'] ?? '0'
                    );
                }
                if ($notText) {
                    $notText = $itemsInfo . "\n--- NOT ---\n" . $notText;
                } else {
                    $notText = $itemsInfo;
                }
                $stmt = $pdo->prepare("UPDATE tutanak SET not_text = ? WHERE id = ?");
                $stmt->execute([$notText, $id]);
            }

            // Handle PDF upload if provided
            if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['pdf'];
                $filename = $tutanakTitle . '.pdf';
                $filepath = $tutanakFolder . DIRECTORY_SEPARATOR . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Update pdf_path in database
                    $stmt = $pdo->prepare("UPDATE tutanak SET pdf_path = ? WHERE id = ?");
                    $stmt->execute([$tutanakTitle . '/' . $filename, $id]);
                }
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'id' => $id,
                'tutanak_title' => $tutanakTitle,
                'message' => 'Tutanak başarıyla oluşturuldu'
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Update existing tutanak
    public function update(): void
    {
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Geçersiz tutanak ID']);
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $tutanak = Tutanak::find($pdo, $id);
            if (!$tutanak) {
                echo json_encode(['error' => 'Tutanak bulunamadı']);
                return;
            }

            // Parse numeric values
            $birimFiyat = $this->parseNumeric($_POST['birim_fiyat'] ?? '0');
            $miktar = $this->parseNumeric($_POST['miktar'] ?? '0');
            $tutar = $birimFiyat * $miktar;

            // Prepare data (title remains unchanged)
            $data = [
                'tutanak_title' => $tutanak['tutanak_title'], // Keep original title
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'tutanak_no' => trim($_POST['tutanak_no'] ?? ''),
                'malzeme_yevmiye_ceza' => trim($_POST['malzeme_yevmiye_ceza'] ?? ''),
                'birim_id' => !empty($_POST['birim_id']) ? (int)$_POST['birim_id'] : null,
                'birim_fiyat' => $birimFiyat,
                'miktar' => $miktar,
                'odeme_yapilacak_firma' => trim($_POST['odeme_yapilacak_firma'] ?? ''),
                'tutar' => $tutar,
                'kesinti_yapilacak_firma' => trim($_POST['kesinti_yapilacak_firma'] ?? ''),
                'not_text' => trim($_POST['not_text'] ?? ''),
                'tur' => trim($_POST['tur'] ?? ''),
                'tarih' => $_POST['tarih'] ?? date('Y-m-d'),
                'konu' => trim($_POST['konu'] ?? ''),
                'pdf_path' => $tutanak['pdf_path'], // Keep existing
            ];

            // Update record
            Tutanak::update($pdo, $id, $data);

            // Handle PDF upload if provided
            if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['pdf'];
                $tutanakFolder = $this->cfg['paths']['tutanak'] . DIRECTORY_SEPARATOR . $tutanak['tutanak_title'];

                if (!is_dir($tutanakFolder)) {
                    mkdir($tutanakFolder, 0755, true);
                }

                $filename = $tutanak['tutanak_title'] . '.pdf';
                $filepath = $tutanakFolder . DIRECTORY_SEPARATOR . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $stmt = $pdo->prepare("UPDATE tutanak SET pdf_path = ? WHERE id = ?");
                    $stmt->execute([$tutanak['tutanak_title'] . '/' . $filename, $id]);
                }
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Tutanak başarıyla güncellendi'
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Delete tutanak (soft delete)
    public function delete(): void
    {
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Geçersiz tutanak ID']);
            return;
        }

        $pdo = Database::pdo();

        try {
            $success = Tutanak::delete($pdo, $id);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Tutanak silindi' : 'Tutanak silinemedi'
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Helper to parse numeric input (handles Turkish format)
    private function parseNumeric(string $input): float
    {
        $sanitized = str_replace('.', '', $input); // Remove thousand separators
        $sanitized = str_replace(',', '.', $sanitized); // Replace decimal comma with dot
        return (float)$sanitized;
    }
}
