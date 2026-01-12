<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class ExcelContractService
{
    /**
     * Generate contract import template with reference sheets
     * 
     * Sheets included:
     * 1. Data - For user to enter contract data (cleaned fields)
     * 2. Projects - Reference sheet with all active projects
     * 3. Disciplines & Branches - Reference sheet with discipline/branch hierarchy
     * 4. Instructions - Help guide for users
     */
    public static function generateContractTemplate(PDO $pdo): array
    {
        $projects = self::getActiveProjects($pdo);
        $disciplines = self::getDisciplinesWithBranches($pdo);

        // Template data sheet structure - only essential columns for user to fill
        $dataHeaders = [
            'contract_date',      // Required: YYYY-MM-DD
            'end_date',           // Optional: YYYY-MM-DD
            'subject',            // Required: Contract subject
            'project_name',       // Required: Project name (will be converted to ID)
            'discipline_name',    // Optional: Discipline name (will be converted to ID)
            'branch_name',        // Optional: Branch name (will be converted to ID)
            'contract_title',     // Optional: Auto-generated if empty
            'amount',             // Required: Number format
            'currency_code'       // Optional: TRY, USD, EUR (default TRY)
        ];

        // Sample rows
        $sample1 = [
            '2026-01-02',         // contract_date
            '2026-12-31',         // end_date
            'Yazılım Geliştirme', // subject
            'Portal Projesi',     // project_name (user enters project name)
            '',                   // discipline_name
            '',                   // branch_name
            '',                   // contract_title (auto-generated)
            '100000.00',          // amount
            'TRY'                 // currency_code
        ];

        $sample2 = [
            '2026-02-01',
            '2026-06-30',
            'Yazılım Bakımı',
            'Mobil Uygulama',     // project_name
            'BİLİŞİM',            // discipline_name
            'YAZILIM GELİŞTİRME', // branch_name
            '',
            '50000.00',
            'USD'
        ];

        return [
            'dataHeaders' => $dataHeaders,
            'sampleRows' => [$sample1, $sample2],
            'projects' => $projects,
            'disciplines' => $disciplines
        ];
    }

    /**
     * Get all active projects for reference sheet
     */
    private static function getActiveProjects(PDO $pdo): array
    {
        $stmt = $pdo->prepare("
            SELECT id, name, short_name, company_id
            FROM public.project
            WHERE deleted_at IS NULL
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get disciplines with their branches for reference sheet
     */
    private static function getDisciplinesWithBranches(PDO $pdo): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                d.id as discipline_id,
                d.name as discipline_name,
                b.id as branch_id,
                b.name_en as branch_name_en,
                b.name_tr as branch_name_tr
            FROM public.discipline d
            LEFT JOIN public.discipline_branch b ON b.discipline_id = d.id
            WHERE d.is_active = true
            ORDER BY d.name ASC, b.name_tr ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Validate and process uploaded contract data
     * Returns array with validated contracts and errors
     * Converts project/discipline/branch names to IDs
     */
    public static function validateContractData(array $rows, PDO $pdo): array
    {
        $validated = [];
        $errors = [];

        // Build lookup maps for name-to-ID conversion
        $projectMap = self::buildProjectNameMap($pdo);
        $disciplineMap = self::buildDisciplineNameMap($pdo);
        $branchMap = self::buildBranchNameMap($pdo);

        // Skip header row (index 0)
        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $actualRowNum = $rowIndex + 2; // +2 because of header and 1-based indexing

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $contract = [
                'contract_date'   => $row[0] ?? null,
                'end_date'        => $row[1] ?? null,
                'subject'         => $row[2] ?? null,
                'project_name'    => $row[3] ?? null,
                'discipline_name' => $row[4] ?? null,
                'branch_name'     => $row[5] ?? null,
                'contract_title'  => $row[6] ?? null,
                'amount'          => $row[7] ?? null,
                'currency_code'   => $row[8] ?? 'TRY'
            ];

            $rowErrors = self::validateContractRow($contract, $actualRowNum, $projectMap, $disciplineMap, $branchMap);
            if ($rowErrors['errors']) {
                $errors[$actualRowNum] = $rowErrors['errors'];
            } else {
                // Convert names to IDs and remove name fields
                $validated[] = $rowErrors['contract'];
            }
        }

        return [
            'valid' => $validated,
            'errors' => $errors,
            'totalRows' => count($rows) - 1 // Exclude header
        ];
    }

    /**
     * Validate individual contract row
     * Converts project/discipline/branch names to IDs
     */
    private static function validateContractRow(array $contract, int $rowNum, array $projectMap, array $disciplineMap, array $branchMap): array
    {
        $errors = [];

        // Validate contract_date
        if (empty($contract['contract_date'])) {
            $errors[] = 'contract_date boş bırakılamaz';
        } elseif (!self::isValidDate($contract['contract_date'])) {
            $errors[] = 'contract_date geçersiz format (YYYY-MM-DD gerekli)';
        }

        // Validate end_date if provided
        if (!empty($contract['end_date']) && !self::isValidDate($contract['end_date'])) {
            $errors[] = 'end_date geçersiz format (YYYY-MM-DD gerekli)';
        }

        // Validate subject
        if (empty($contract['subject'])) {
            $errors[] = 'subject boş bırakılamaz';
        }

        // Validate and convert project_name to project_id
        $projectId = null;
        if (empty($contract['project_name'])) {
            $errors[] = 'project_name boş bırakılamaz';
        } else {
            $projectNameKey = strtoupper(trim((string)$contract['project_name']));
            if (!isset($projectMap[$projectNameKey])) {
                $errors[] = "project_name '{$contract['project_name']}' bulunamadı";
            } else {
                $projectId = $projectMap[$projectNameKey];
            }
        }

        // Validate amount
        if (empty($contract['amount'])) {
            $errors[] = 'amount boş bırakılamaz';
        } else {
            $amount = str_replace(',', '.', (string)$contract['amount']);
            if (!is_numeric($amount) || (float)$amount < 0) {
                $errors[] = 'amount geçersiz sayı formatı';
            }
        }

        // Validate and convert discipline_name to discipline_id
        $disciplineId = null;
        if (!empty($contract['discipline_name'])) {
            $discNameKey = strtoupper(trim((string)$contract['discipline_name']));
            if (!isset($disciplineMap[$discNameKey])) {
                $errors[] = "discipline_name '{$contract['discipline_name']}' bulunamadı";
            } else {
                $disciplineId = $disciplineMap[$discNameKey];
            }
        }

        // Validate and convert branch_name to branch_id
        $branchId = null;
        if (!empty($contract['branch_name'])) {
            if ($disciplineId === null) {
                $errors[] = 'branch_name için discipline_name belirtilmelidir';
            } else {
                $branchNameKey = strtoupper(trim((string)$contract['branch_name']));
                $branchLookupKey = "{$disciplineId}:{$branchNameKey}";
                if (!isset($branchMap[$branchLookupKey])) {
                    $errors[] = "branch_name '{$contract['branch_name']}' bu disiplin için bulunamadı";
                } else {
                    $branchId = $branchMap[$branchLookupKey];
                }
            }
        }

        if ($errors) {
            return ['errors' => $errors, 'contract' => null];
        }

        // Return converted contract with IDs instead of names
        return [
            'errors' => [],
            'contract' => [
                'contract_date'   => $contract['contract_date'],
                'end_date'        => $contract['end_date'],
                'subject'         => $contract['subject'],
                'project_id'      => $projectId,
                'discipline_id'   => $disciplineId,
                'branch_id'       => $branchId,
                'contract_title'  => $contract['contract_title'],
                'amount'          => str_replace(',', '.', (string)$contract['amount']),
                'currency_code'   => $contract['currency_code'] ?? 'TRY'
            ]
        ];
    }

    /**
     * Build project name to ID map
     * Keys are uppercase project names, values are IDs
     */
    private static function buildProjectNameMap(PDO $pdo): array
    {
        $map = [];
        $stmt = $pdo->prepare("
            SELECT id, name
            FROM public.project
            WHERE deleted_at IS NULL
            ORDER BY name ASC
        ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = strtoupper(trim($row['name']));
            $map[$key] = $row['id'];
        }

        return $map;
    }

    /**
     * Build discipline name to ID map
     * Keys are uppercase discipline names, values are IDs
     */
    private static function buildDisciplineNameMap(PDO $pdo): array
    {
        $map = [];
        $stmt = $pdo->prepare("
            SELECT id, name_tr
            FROM public.discipline
            ORDER BY name_tr ASC
        ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = strtoupper(trim($row['name_tr'] ?? ''));
            if ($key !== '') {
                $map[$key] = $row['id'];
            }
        }

        return $map;
    }

    /**
     * Build branch name to ID map
     * Keys are "discipline_id:branch_name_uppercase", values are branch IDs
     */
    private static function buildBranchNameMap(PDO $pdo): array
    {
        $map = [];
        $stmt = $pdo->prepare("
            SELECT id, discipline_id, name_tr
            FROM public.discipline_branch
            ORDER BY discipline_id ASC, name_tr ASC
        ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = strtoupper($row['discipline_id'] . ':' . trim($row['name_tr'] ?? ''));
            if (!str_ends_with($key, ':')) {
                $map[$key] = $row['id'];
            }
        }

        return $map;
    }

    /**
     * Check if string is valid date format YYYY-MM-DD
     */
    private static function isValidDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        $date = trim($date);

        // Check format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        // Check if valid date
        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }
}
