<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\TokenService;
use PDO;
use Exception;

final class AttendanceController extends Controller
{
    private PDO $db;
    private TokenService $tokens;

    public function __construct()
    {
        $this->db = Database::pdo();
        $this->tokens = new TokenService($this->db);
    }

    // POST /api/attendance/scan
    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);

        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
            return;
        }

        $type  = $body['type']  ?? null; // 'in' | 'out' (opsiyonel; yoksa otomatik)
        $token = $body['token'] ?? null;
        $uid   = $body['uid']   ?? null;

        try {
            if ($token) {
                // Token doğrula (daha sıkı pencere, örn. 120 saniye)
                $payload = $this->tokens->verifyToken($token, 120); // ['uid','ts','nonce']
                // Replay engelle
                $this->tokens->consumeNonce($payload['uid'], $payload['nonce']);
                $uid = $payload['uid'];
            } elseif ($uid && ctype_digit((string)$uid)) {
                $uid = (int)$uid;
            } else {
                throw new Exception('token veya uid gerekli');
            }

            // Kullanıcı var mı?
            $user = $this->getUser($uid);
            if (!$user) {
                throw new Exception('User not found');
            }

            // type yoksa otomatik belirle (son kayda göre toggle)
            if ($type === null || $type === '') {
                $type = $this->nextTypeForUser($uid); // 'in' veya 'out'
            } else {
                // Güvenlik: beklenmeyen değer gelirse normalize et
                $type = strtolower((string)$type);
                if ($type !== 'in' && $type !== 'out') {
                    throw new Exception('Invalid type');
                }
            }

            // Katılım kaydı (PostgreSQL: RETURNING id)
            $attendanceId = $this->createAttendance($uid, $type);

            echo json_encode([
                'ok' => true,
                'user' => [
                    'id'        => (int)$user['id'],
                    'full_name' => $user['full_name'] ?? null,
                ],
                'attendance_id' => $attendanceId,
                'type' => $type,
                'scanned_at' => gmdate('c'),
            ]);
        } catch (Exception $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
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

    private function nextTypeForUser(int $uid): string
    {
        $stmt = $this->db->prepare("
          SELECT type
          FROM attendance
          WHERE user_id = :u
          ORDER BY scanned_at DESC, id DESC
          LIMIT 1
        ");
        $stmt->execute([':u' => $uid]);
        $last = $stmt->fetchColumn();
        return ($last === 'in') ? 'out' : 'in';
    }

    private function createAttendance(int $uid, string $type): int
    {
        // DİKKAT: tablo adı senin ortamında 'attendance'
        $stmt = $this->db->prepare('INSERT INTO attendance (user_id, type) VALUES (:u, :t) RETURNING id');
        $stmt->execute([':u' => $uid, ':t' => $type]);
        return (int)$stmt->fetchColumn();
    }
}
