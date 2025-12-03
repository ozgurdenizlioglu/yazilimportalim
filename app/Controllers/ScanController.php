<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\TokenService;
use PDO;

class ScanController extends Controller
{
    private PDO $db;
    private TokenService $tokens;

    public function __construct()
    {
        $this->db = Database::pdo();
        $this->tokens = new TokenService($this->db);
    }

    // GET /scan?uid=...  (önerilen akış)
    // Opsiyonel destek: /scan?token=...
    public function show()
    {
        $queryToken = isset($_GET['token']) ? (string)$_GET['token'] : null;
        $uidParam   = isset($_GET['uid']) ? (string)$_GET['uid'] : null;

        $error = null;
        $user  = null;
        $uid   = null;
        $ephemeralToken = null; // sayfa için anlık üretilecek tek kullanımlık token

        if ($uidParam !== null && ctype_digit($uidParam)) {
            // Sabit QR akışı: uid ile geldi; kullanıcıyı bul, tek kullanımlık token üret
            $uid = (int)$uidParam;
            $user = $this->getUser($uid);
            if (!$user) {
                $error = "Kullanıcı bulunamadı.";
            } else {
                // Seçenek 2: her sayfa yüklemede tek kullanımlık token üret
                try {
                    $ephemeralToken = $this->tokens->createTokenForUser($uid);
                } catch (\Exception $e) {
                    $error = "Token üretilemedi: " . $e->getMessage();
                }
            }
        } elseif (!empty($queryToken)) {
            // Opsiyonel: token ile gelindiyse doğrula ve kullanıcıyı göster
            try {
                // Burada daha esnek bir maxAge istersen ikinci parametre verebilirsin: verifyToken($queryToken, 300)
                $payload = $this->tokens->verifyToken($queryToken);
                $uid = (int)$payload['uid'];
                $user = $this->getUser($uid);
                if (!$user) {
                    $error = "Kullanıcı bulunamadı.";
                } else {
                    // Token zaten var; yeniden üretmeye gerek yok. İstersen yine de yeni üretip onu kullanabilirsin.
                    $ephemeralToken = $queryToken;
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Geçersiz istek. uid parametresi gerekli.";
        }

        return $this->view('scan/show', [
            'error'           => $error,
            'user'            => $user,
            'uid'             => $uid,
            // View sayfa yüklenince otomatik POST için bu token'ı kullanacak:
            'token'           => $ephemeralToken,
            // İstersen ayrıca sorgudan gelen ham token'ı da geçirirsin:
            'queryToken'      => $queryToken,
        ]);
    }

    private function getUser(int $uid): ?array
    {
        $sql = "
          SELECT
            id,
            TRIM(
              COALESCE(first_name, '') || ' ' ||
              COALESCE(middle_name, '') || ' ' ||
              COALESCE(last_name, '')
            ) AS full_name
          FROM users
          WHERE id = :id
          LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
