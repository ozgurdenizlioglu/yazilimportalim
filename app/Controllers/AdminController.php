<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AdminController extends Controller
{
    public function updateMuhasebeAciklama(): void
    {
        // Check if user is admin (you can add auth check)
        header('Content-Type: application/json');

        try {
            $pdo = Database::pdo();

            // Mapping of cost_code to muhasebe_kodu_aciklama
            $mapping = [
                '01' => 'DIRECT EXPENSES',
                '01-01' => 'EXCAVATION&BACKFILL',
                '01-01-01' => 'EXCAVATION',
                '01-01-02' => 'BLASTING',
                '01-01-03' => 'WEAVING AREA COUNTRYSIDE',
                '01-01-04' => 'FILLING',
                '01-01-05' => 'COSTS OF THE PICTURE',
                '01-01-06' => 'FINAL PENALTY-EXCAVATION',
                '01-01-07' => 'BACKFILL & COMPACTION',
                '01-02' => 'TEMPORARY ROADS',
                '01-02-01' => 'TEMPORARY ROADS',
                '01-02-02' => 'DUST SUPPRESSION',
                '01-03' => 'DEWATERING',
                '01-03-01' => 'DEWATERING-PUMPING',
                '01-03-02' => 'TEMPORARY SUMP PITS',
                '01-04' => 'SITE ESTABLISHMENT',
                '01-04-01' => 'FENCING & SIGNS',
                '01-04-02' => 'TEMPORARY STRUCTURES',
                '01-04-03' => 'UTILITIES HOOKUP',
                '01-04-04' => 'TEMPORARY LIGHTING',
                '02' => 'SUPERSTRUCTURE',
                '02-01' => 'STEEL FRAME',
                '02-01-01' => 'STEEL FABRICATION',
                '02-01-02' => 'STEEL ERECTION',
                '02-02' => 'CONCRETE STRUCTURE',
                '02-02-01' => 'FORMWORK',
                '02-02-02' => 'REBAR',
                '02-02-03' => 'CONCRETE POUR',
                '02-02-04' => 'CURING',
                '02-03' => 'MASONRY',
                '02-03-01' => 'BRICK MASONRY',
                '02-03-02' => 'STONE MASONRY',
                '02-04' => 'STRUCTURAL TIMBER',
                '02-04-01' => 'TIMBER SUPPLY',
                '02-04-02' => 'TIMBER FRAMING',
            ];

            $updated = 0;
            $results = [];

            foreach ($mapping as $costCode => $description) {
                $sql = "UPDATE costcodes SET muhasebe_kodu_aciklama = :desc WHERE cost_code = :code AND (muhasebe_kodu_aciklama IS NULL OR muhasebe_kodu_aciklama = '')";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':code', $costCode);
                $stmt->bindValue(':desc', $description);
                $stmt->execute();

                $rowCount = $stmt->rowCount();
                if ($rowCount > 0) {
                    $updated += $rowCount;
                    $results[] = [
                        'costCode' => $costCode,
                        'description' => $description,
                        'updated' => $rowCount
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Successfully updated {$updated} records",
                'total' => count($mapping),
                'updated' => $updated,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
