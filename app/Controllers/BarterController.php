<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Barter;
use App\Models\Project;

class BarterController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $rows = Barter::all($pdo);

        $this->view('barter/index', [
            'title' => 'Barter',
            'barters' => $rows,
        ], 'layouts/base');
    }

    public function apiRecords(): void
    {
        $pdo = Database::pdo();
        $page = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['pageSize'] ?? 20);

        if ($page < 1) $page = 1;
        if ($pageSize < 1) $pageSize = 20;

        $total = Barter::count($pdo);
        $totalPages = ceil($total / $pageSize);

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT * FROM barter ORDER BY id DESC LIMIT :pageSize OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':pageSize', $pageSize, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $records,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => $totalPages
            ]
        ]);
        exit;
    }

    public function getProjects(): void
    {
        $pdo = Database::pdo();
        $projects = Project::all($pdo);
        $projectNames = array_map(fn($p) => $p['name'] ?? '', $projects);
        $projectNames = array_values(array_filter($projectNames));

        header('Content-Type: application/json');
        echo json_encode($projectNames);
        exit;
    }

    public function create(): void
    {
        $this->view('barter/create', [
            'title' => 'Yeni Barter Kaydı Oluştur',
        ]);
    }

    public function store(): void
    {
        $pdo = Database::pdo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $data = $_POST;
        Barter::create($pdo, $data);

        header('Location: /barter');
        exit;
    }

    public function edit(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz id';
            return;
        }

        $row = Barter::find($pdo, $id);
        if (!$row) {
            http_response_code(404);
            echo 'Barter kaydı bulunamadı';
            return;
        }

        $this->view('barter/edit', [
            'title' => 'Barter Kaydını Düzenle',
            'record' => $row,
        ]);
    }

    public function update(): void
    {
        $pdo = Database::pdo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /barter');
            exit;
        }

        $data = $_POST;
        Barter::update($pdo, $id, $data);

        header('Location: /barter');
        exit;
    }

    public function destroy(): void
    {
        $pdo = Database::pdo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Barter::delete($pdo, $id);
        }

        header('Location: /barter');
        exit;
    }

    public function bulkUpload(): void
    {
        $pdo = Database::pdo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $payloadStr = $_POST['payload'] ?? '{}';
        $payload = json_decode($payloadStr, true);

        if (!isset($payload['rows']) || !is_array($payload['rows'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit;
        }

        $rows = $payload['rows'];
        $headers = array_shift($rows);

        if (!$headers || !is_array($headers)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'No headers found']);
            exit;
        }

        // Map columns
        $colMap = [];
        foreach ($headers as $idx => $headerName) {
            $normalized = strtolower(trim($headerName));
            // Map column names from Excel to database
            if (in_array($normalized, ['proje', 'project', 'proje adı'])) {
                $colMap[$idx] = 'proje';
            } elseif (in_array($normalized, ['cost code', 'cost_code', 'kod'])) {
                $colMap[$idx] = 'cost_code';
            } elseif (in_array($normalized, ['aciklama', 'description', 'açıklama'])) {
                $colMap[$idx] = 'aciklama';
            } elseif (in_array($normalized, ['barter tutari', 'barter_tutari', 'barter tutarı'])) {
                $colMap[$idx] = 'barter_tutari';
            } elseif (in_array($normalized, ['barter currency', 'barter_currency'])) {
                $colMap[$idx] = 'barter_currency';
            } elseif (in_array($normalized, ['barter gerceklesen', 'barter_gerceklesen', 'barter gerçekleşen'])) {
                $colMap[$idx] = 'barter_gerceklesen';
            } elseif (in_array($normalized, ['barter planlanan oran', 'barter_planlanan_oran', 'barter - planlanan oran'])) {
                $colMap[$idx] = 'barter_planlanan_oran';
            } elseif (in_array($normalized, ['barter planlanan tutar', 'barter_planlanan_tutar', 'barter - planlanan tutar'])) {
                $colMap[$idx] = 'barter_planlanan_tutar';
            } elseif (in_array($normalized, ['sozlesme tarihi', 'sozlesme_tarihi', 'sözleşme tarihi', 'agreement date'])) {
                $colMap[$idx] = 'sozlesme_tarihi';
            } elseif (in_array($normalized, ['kur', 'rate', 'exchange rate'])) {
                $colMap[$idx] = 'kur';
            } elseif (in_array($normalized, ['usd karsiligi', 'usd_karsiligi', 'usd karşılığı'])) {
                $colMap[$idx] = 'usd_karsiligi';
            } elseif (in_array($normalized, ['tutar try', 'tutar_try', 'amount try'])) {
                $colMap[$idx] = 'tutar_try';
            } elseif (in_array($normalized, ['not', 'notes', 'nota'])) {
                $colMap[$idx] = 'not_field';
            } elseif (in_array($normalized, ['path', 'dosya yolu'])) {
                $colMap[$idx] = 'path';
            } elseif (in_array($normalized, ['yuklenici', 'contractor'])) {
                $colMap[$idx] = 'yuklenici';
            } elseif (in_array($normalized, ['karsi hesap ismi', 'karsi_hesap_ismi', 'karşı hesap ismi', 'counterparty account'])) {
                $colMap[$idx] = 'karsi_hesap_ismi';
            }
        }

        $inserted = 0;
        foreach ($rows as $row) {
            if (!is_array($row) || empty(implode('', $row))) {
                continue;
            }

            $data = [];
            foreach ($colMap as $idx => $field) {
                $data[$field] = $row[$idx] ?? null;
            }

            if (!empty($data)) {
                Barter::create($pdo, $data);
                $inserted++;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['inserted' => $inserted]);
        exit;
    }
}
