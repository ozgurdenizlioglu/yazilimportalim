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
}
