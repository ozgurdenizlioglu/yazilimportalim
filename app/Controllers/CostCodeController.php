<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\CostCode;

class CostCodeController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $rows = CostCode::all($pdo);

        $this->view('costcodes/index', [
            'title' => 'Maliyet Kodları',
            'costcodes' => $rows,
        ], 'layouts/base');
    }

    public function apiRecords(): void
    {
        $pdo = Database::pdo();
        $page = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['pageSize'] ?? 20);

        if ($page < 1) $page = 1;
        if ($pageSize < 1) $pageSize = 20;

        $total = CostCode::count($pdo);
        $totalPages = ceil($total / $pageSize);

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT * FROM costcodes ORDER BY id DESC LIMIT :pageSize OFFSET :offset";
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

    public function create(): void
    {
        $this->view('costcodes/create', [
            'title' => 'Yeni Maliyet Kodu Oluştur',
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
        CostCode::create($pdo, $data);

        header('Location: /costcodes');
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

        $row = CostCode::find($pdo, $id);
        if (!$row) {
            http_response_code(404);
            echo 'Maliyet kodu kaydı bulunamadı';
            return;
        }

        $this->view('costcodes/edit', [
            'title' => 'Maliyet Kodu Düzenle',
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
            header('Location: /costcodes');
            exit;
        }

        $data = $_POST;
        CostCode::update($pdo, $id, $data);

        header('Location: /costcodes');
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
            CostCode::delete($pdo, $id);
        }

        header('Location: /costcodes');
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
            if (in_array($normalized, ['level', 'seviye'])) {
                $colMap[$idx] = 'level';
            } elseif (in_array($normalized, ['ust baslik', 'ust baslik / veri', 'ust_baslik_veri'])) {
                $colMap[$idx] = 'ust_baslik_veri';
            } elseif (in_array($normalized, ['ortalama gider', 'ortalama_gider', 'average cost'])) {
                $colMap[$idx] = 'ortalama_gider';
            } elseif (in_array($normalized, ['cost code', 'cost_code', 'kod', 'code']) || (strpos($normalized, 'cost code') === 0 && strpos($normalized, 'description') === false)) {
                $colMap[$idx] = 'cost_code';
            } elseif (in_array($normalized, ['direct/indirect', 'direct_indirect', 'tip'])) {
                $colMap[$idx] = 'direct_indirect';
            } elseif (strpos($normalized, 'muhasebe') !== false && strpos($normalized, 'aciklama') !== false) {
                $colMap[$idx] = 'muhasebe_kodu_aciklama';
            } elseif (in_array($normalized, ['cost code description', 'cost_code_description', 'aciklama']) || strpos($normalized, 'description') !== false) {
                $colMap[$idx] = 'cost_code_description';
            }
        }

        // If no cost_code column was detected, try to auto-detect from column content
        if (!array_search('cost_code', $colMap)) {
            foreach ($headers as $idx => $headerName) {
                $normalized = strtolower(trim($headerName));
                // If any unmapped column looks like it contains cost codes, mark it as such
                if (!isset($colMap[$idx]) && (strlen($normalized) <= 15 && preg_match('/^[a-z0-9\-\.]+$/i', $normalized))) {
                    $colMap[$idx] = 'cost_code';
                    break;
                }
            }
        }

        $inserted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            if (!is_array($row) || empty(implode('', $row))) {
                continue;
            }

            $data = [];
            foreach ($colMap as $idx => $field) {
                $data[$field] = $row[$idx] ?? null;
            }

            if (!empty($data)) {
                // If cost_code is not set but we have data, try to infer which field is the cost code
                if (empty($data['cost_code']) && count($data) > 0) {
                    // Try to find a non-null value that looks like a cost code
                    foreach ($data as $field => $value) {
                        if (!empty($value) && $field !== 'level' && $field !== 'ust_baslik_veri' && $field !== 'ortalama_gider' && $field !== 'direct_indirect') {
                            // This might be the cost code
                            if (is_numeric($value) || strpos($value, '-') !== false || strlen($value) <= 10) {
                                $data['cost_code'] = $value;
                                break;
                            }
                        }
                    }
                }

                if (!empty($data['cost_code'])) {
                    // Check if cost code already exists
                    $existing = CostCode::findByCostCode($pdo, (string)$data['cost_code']);
                    if (!$existing) {
                        CostCode::create($pdo, $data);
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $errors[] = 'Row has no cost_code: ' . json_encode($data);
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors]);
        exit;
    }

    public function removeDuplicates(): void
    {
        $pdo = Database::pdo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        try {
            $result = CostCode::removeDuplicates($pdo);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
