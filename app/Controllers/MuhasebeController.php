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
        $pdo = Database::pdo();
        header('Content-Type: application/json');

        try {
            $recordsJson = $_POST['records'] ?? '[]';
            $records = json_decode($recordsJson, true);

            if (!is_array($records) || empty($records)) {
                http_response_code(400);
                echo json_encode(['error' => 'No records to upload']);
                return;
            }

            $success = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $idx => $record) {
                try {
                    $data = [
                        'proje' => trim($record['Proje'] ?? ''),
                        'tahakkuk_tarihi' => $record['Tahakkuk Tarihi'] ?? null,
                        'vade_tarihi' => $record['Vade Tarihi'] ?? null,
                        'cek_no' => trim($record['Çek No'] ?? ''),
                        'aciklama' => trim($record['Açıklama'] ?? ''),
                        'aciklama2' => trim($record['Açıklama 2'] ?? ''),
                        'aciklama3' => trim($record['Açıklama 3'] ?? ''),
                        'tutar_try' => $record['Tutar (TRY)'] ?? null,
                        'cari_hesap_ismi' => trim($record['Cari Hesap'] ?? ''),
                        'wb' => trim($record['WB'] ?? ''),
                        'ws' => trim($record['WS'] ?? ''),
                        'row_col' => trim($record['Row'] ?? ''),
                        'cost_code' => trim($record['Cost Code'] ?? ''),
                        'dikkate_alinmayacaklar' => trim($record['Dikkate Alınmayacaklar'] ?? ''),
                        'usd_karsiligi' => $record['USD Karşılığı'] ?? null,
                        'id_text' => trim($record['ID (Text)'] ?? ''),
                        'id_veriler' => trim($record['ID Veriler'] ?? ''),
                        'id_odeme_plan_satinalma_odeme_onay_listesi' => trim($record['ID Ödeme Plan'] ?? ''),
                        'not_field' => trim($record['Not'] ?? ''),
                        'not_ool_odeme_plani' => trim($record['Not OOL/Ödeme'] ?? ''),
                    ];

                    Muhasebe::create($pdo, $data);
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Satır " . ($idx + 2) . ": " . $e->getMessage();
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "$success kayıt başarıyla yüklendi. $failed hata.",
                'uploaded' => $success,
                'failed' => $failed,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
