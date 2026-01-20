<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Boq;
use PDO;

class BoqController extends Controller
{
    // List all BOQ items (optionally filtered by project)
    public function index(): void
    {
        $pdo = Database::pdo();
        $projectId = $_GET['project_id'] ?? null;

        $boqItems = Boq::all($pdo, $projectId);

        $this->view('boq/index', [
            'title' => trans('common.boq'),
            'boqItems' => $boqItems,
            'projectId' => $projectId,
        ]);
    }

    // API endpoint to import BOQ data from Excel/worker
    public function import(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $pdo = Database::pdo();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['items']) || !is_array($input['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input. Expected "items" array.']);
            return;
        }

        $currentUserId = $_SESSION['user_id'] ?? null;
        $imported = 0;
        $errors = [];

        try {
            $pdo->beginTransaction();

            foreach ($input['items'] as $idx => $item) {
                try {
                    // Map Excel columns to DB fields
                    $data = [
                        'project_id' => $input['project_id'] ?? $item['project_id'] ?? null,
                        'dwg_filename' => $item['dwg_filename'] ?? $item[0] ?? '',
                        'coor_x' => $item['coor_x'] ?? $item[1] ?? 0,
                        'coor_y' => $item['coor_y'] ?? $item[2] ?? 0,
                        'coordinates' => $item['coordinates'] ?? $item[3] ?? '',
                        'layer_name' => $item['layer_name'] ?? $item[4] ?? '',
                        'length' => $item['length'] ?? $item[5] ?? 0,
                        'size1' => $item['size1'] ?? $item[6] ?? 0,
                        'size2' => $item['size2'] ?? $item[7] ?? 0,
                        'area' => $item['area'] ?? $item[8] ?? 0,
                        'type_name' => $item['type_name'] ?? $item[9] ?? '',
                        'height' => $item['height'] ?? $item[10] ?? 0,
                        'poz' => $item['poz'] ?? $item[11] ?? '',
                        'minha' => $item['minha'] ?? $item[12] ?? '',
                        'tur_malzeme' => $item['tur_malzeme'] ?? $item[13] ?? '',
                        'tur_yapi_elemani' => $item['tur_yapi_elemani'] ?? $item[14] ?? '',
                        'file_path' => $item['file_path'] ?? $item[15] ?? '',
                        'handle' => $item['handle'] ?? $item[16] ?? '',
                        'guid' => $item['guid'] ?? $item[17] ?? '',
                        'text_content' => $item['text_content'] ?? $item[18] ?? '',
                        'poz_text' => $item['poz_text'] ?? $item[19] ?? '',
                        'hesap_turu' => $item['hesap_turu'] ?? $item[20] ?? '',
                        'metraj_beton' => $item['metraj_beton'] ?? $item[21] ?? 0,
                        'metraj_kalip' => $item['metraj_kalip'] ?? $item[22] ?? 0,
                        'metraj_donati' => $item['metraj_donati'] ?? $item[23] ?? 0,
                        'mahal' => $item['mahal'] ?? $item[24] ?? '',
                        'blok' => $item['blok'] ?? $item[25] ?? '',
                        'blok2' => $item['blok2'] ?? $item[26] ?? '',
                        'kat' => $item['kat'] ?? $item[27] ?? null,
                        'created_by' => $currentUserId,
                    ];

                    Boq::create($pdo, $data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row $idx: " . $e->getMessage();
                }
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'imported' => $imported,
                'total' => count($input['items']),
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    // API endpoint to upload DWG file(s) - supports multiple files
    public function upload(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_FILES['dwg'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No DWG file uploaded']);
            return;
        }

        $projectId = $_POST['project_id'] ?? null;
        $baseQueueDir = __DIR__ . '/../../storage/dwg-queue';

        // Create timestamp-based subfolder: YYYYMMDDHHmmss-NNN (with unique sequence number)
        $timestamp = date('YmdHis');
        $seq = 1;
        $folderBaseName = $timestamp;
        $queueDir = $baseQueueDir . '/' . $folderBaseName . '-001';

        // If folder already exists (same second), increment sequence
        while (is_dir($queueDir)) {
            $seq++;
            $queueDir = $baseQueueDir . '/' . $folderBaseName . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
        }

        mkdir($queueDir, 0777, true);

        // Handle both single and multiple file uploads
        $files = $_FILES['dwg'];
        if (!is_array($files['name'])) {
            // Single file - convert to array for uniform handling
            $files = [
                'name' => [$files['name']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']]
            ];
        }

        $uploaded = [];
        $errors = [];

        foreach ($files['name'] as $idx => $fileName) {
            if ($files['error'][$idx] !== UPLOAD_ERR_OK) {
                $errors[] = "{$fileName}: Upload error (code {$files['error'][$idx]})";
                continue;
            }

            // Validate extension
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($ext !== 'dwg') {
                $errors[] = "{$fileName}: Only .dwg files are allowed";
                continue;
            }

            // Generate filename with unique ID
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
            $uniqueId = uniqid('', true);
            $destName = "{$safeName}_{$uniqueId}.dwg";
            $destPath = $queueDir . '/' . $destName;

            if (!move_uploaded_file($files['tmp_name'][$idx], $destPath)) {
                $errors[] = "{$fileName}: Failed to save file";
                continue;
            }

            // Write metadata JSON sidecar
            file_put_contents(
                $queueDir . '/' . $safeName . "_{$timestamp}.json",
                json_encode([
                    'project_id' => $projectId,
                    'original_name' => $fileName,
                    'upload_date' => date('Y-m-d H:i:s'),
                    'upload_by' => $_SESSION['user_id'] ?? null
                ])
            );

            $uploaded[] = $destName;
        }

        echo json_encode([
            'success' => count($uploaded) > 0,
            'uploaded' => $uploaded,
            'errors' => $errors,
            'message' => count($uploaded) . ' file(s) queued for processing',
        ]);
    }

    // Delete BOQ item
    public function delete(): void
    {
        $pdo = Database::pdo();
        $id = isset($_POST['id']) ? (string) $_POST['id'] : '';

        if (empty($id)) {
            http_response_code(400);
            echo 'ID required';
            return;
        }

        Boq::delete($pdo, $id);
        header('Location: /boq');
    }
}
