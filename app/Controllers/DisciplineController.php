<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Discipline;
use App\Models\DisciplineBranch;

class DisciplineController extends Controller
{
    // List all disciplines
    public function index(): void
    {
        $pdo = Database::pdo();
        $disciplines = Discipline::all($pdo);
        $this->view('disciplines/index', ['disciplines' => $disciplines]);
    }

    // Show create form
    public function create(): void
    {
        $this->view('disciplines/create');
    }

    // Store new discipline
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $pdo = Database::pdo();

        try {
            $id = Discipline::create($pdo, $_POST);
            $_SESSION['success'] = 'Disiplin başarıyla oluşturuldu.';
            header('Location: /disciplines');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header('Location: /disciplines/create');
            exit;
        }
    }

    // Show edit form
    public function edit(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            return;
        }

        $discipline = Discipline::find($pdo, $id);
        if (!$discipline) {
            http_response_code(404);
            return;
        }

        $branches = DisciplineBranch::allByDiscipline($pdo, $id);
        $this->view('disciplines/edit', [
            'discipline' => $discipline,
            'branches' => $branches,
        ]);
    }

    // Update discipline
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            return;
        }

        try {
            Discipline::update($pdo, $id, $_POST);
            $_SESSION['success'] = 'Disiplin başarıyla güncellendi.';
            header('Location: /disciplines');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header('Location: /disciplines/edit?id=' . $id);
            exit;
        }
    }

    // Delete discipline
    public function destroy(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            return;
        }

        try {
            Discipline::delete($pdo, $id);
            $_SESSION['success'] = 'Disiplin başarıyla silindi.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
        }

        header('Location: /disciplines');
        exit;
    }

    // API: Get all disciplines (for dropdowns)
    public function list(): void
    {
        $pdo = Database::pdo();
        $disciplines = Discipline::all($pdo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($disciplines, JSON_UNESCAPED_UNICODE);
    }

    // API: Get branches for a discipline
    public function branches(): void
    {
        $pdo = Database::pdo();
        $disciplineId = (int)($_GET['discipline_id'] ?? 0);
        if ($disciplineId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'discipline_id gereklidir']);
            return;
        }

        $branches = DisciplineBranch::allByDiscipline($pdo, $disciplineId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($branches, JSON_UNESCAPED_UNICODE);
    }

    // Store new branch (for a discipline)
    public function storeBranch(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $pdo = Database::pdo();

        try {
            DisciplineBranch::create($pdo, $_POST);
            $_SESSION['success'] = 'Alt disiplin başarıyla oluşturuldu.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
        }

        $disciplineId = (int)($_POST['discipline_id'] ?? 0);
        header('Location: /disciplines/edit?id=' . $disciplineId);
        exit;
    }

    // API: Create discipline (JSON endpoint for modal/quick create)
    public function createApi(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'POST gereklidir']);
            return;
        }

        $pdo = Database::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $id = Discipline::create($pdo, $data);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $id,
                'name_tr' => $data['name_tr'] ?? null,
                'name_en' => $data['name_en'] ?? null,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // API: Create branch (JSON endpoint for modal/quick create)
    public function createBranchApi(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'POST gereklidir']);
            return;
        }

        $pdo = Database::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $id = DisciplineBranch::create($pdo, $data);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $id,
                'discipline_id' => $data['discipline_id'],
                'name_tr' => $data['name_tr'] ?? null,
                'name_en' => $data['name_en'] ?? null,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}
