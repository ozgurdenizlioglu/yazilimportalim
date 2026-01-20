<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class DiagnosticController extends Controller
{
    public function muhasebeStatus(): void
    {
        $pdo = Database::pdo();

        // Sample aggregation logic test
        $reportDate = '2026-01-15'; // Today
        $costCode = '02-05';

        // Get muhasebe data that should match
        $stmt = $pdo->query('SELECT cost_code, tutar_try, vade_tarihi FROM muhasebe WHERE cost_code LIKE \'02-05%\' LIMIT 5');
        $samples = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Test aggregation logic
        $odenmis = 0;
        $odenecek = 0;
        $tahakkuk = 0;

        foreach ($samples as $m) {
            $mCostCode = $m['cost_code'] ?? '';
            // Match if exact match OR if the muhasebe cost code starts with the row's cost code followed by a separator
            if (
                $mCostCode === $costCode ||
                (strpos($mCostCode, $costCode . '-') === 0) ||
                (strpos($mCostCode, $costCode . '.') === 0)
            ) {
                $amount = floatval($m['tutar_try'] ?? 0);
                $vadeTarihi = $m['vade_tarihi'] ?? null;

                // If no reportDate provided, put everything in tahakkuk_edilen
                if (!$reportDate) {
                    $tahakkuk += $amount;
                }
                // If no vade_tarihi, put in tahakkuk_edilen
                elseif (!$vadeTarihi) {
                    $tahakkuk += $amount;
                }
                // ODENMIS GUNLUK: vade_tarihi <= reportDate (paid/due by report date)
                elseif (strtotime($vadeTarihi) <= strtotime($reportDate)) {
                    $odenmis += $amount;
                }
                // ODENECEK GUNLUK: vade_tarihi > reportDate (due after report date)
                else {
                    $odenecek += $amount;
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'test_cost_code' => $costCode,
            'report_date' => $reportDate,
            'samples' => $samples,
            'aggregation_result' => [
                'odenmis_gunluk' => $odenmis,
                'odenecek_gunluk' => $odenecek,
                'tahakkuk_edilen' => $tahakkuk,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
