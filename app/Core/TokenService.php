<?php

namespace App\Core;

use PDO;
use Exception;

class TokenService
{
    private PDO $db;
    private string $appSecret;
    private int $tolerance; // kullanılmıyorsa ileride kaldırılabilir
    private int $maxAge;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->appSecret = getenv('APP_SECRET') ?: 'dev_secret_change_me';
        $this->tolerance = (int)(getenv('TOKEN_TOLERANCE_SECONDS') ?: 600);
        $this->maxAge = (int)(getenv('TOKEN_MAX_AGE_SECONDS') ?: 604800); // 7 gün
    }

    public static function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $data .= str_repeat('=', $padLen);
        }
        // base64_decode false dönebilir; çağıran taraf gerekirse kontrol etmeli
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // Kullanıcının QR gizli anahtarını garantiler (yoksa üretir ve kaydeder)
    public function ensureUserQrSecret(int $userId): string
    {
        $stmt = $this->db->prepare("SELECT qr_secret FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("User not found");
        }
        if (!empty($row['qr_secret'])) {
            return $row['qr_secret'];
        }
        $secret = bin2hex(random_bytes(32)); // 64 hex
        $up = $this->db->prepare("UPDATE users SET qr_secret = :s WHERE id = :id");
        $up->execute([':s' => $secret, ':id' => $userId]);
        return $secret;
    }

    // İmza: HMAC-SHA256(appSecret || userSecret, "uid|ts|nonce")
    private function signToken(int $uid, int $ts, string $nonce, string $userSecret): string
    {
        $data = $uid . '|' . $ts . '|' . $nonce;
        $key = hash('sha256', $this->appSecret . $userSecret, true);
        $sig = hash_hmac('sha256', $data, $key, true); // ham bytes
        return self::base64url_encode($sig); // printable base64url
    }

    // Tek kullanımlık token: uid.ts.nonce.sig
    public function createTokenForUser(int $uid): string
    {
        $userSecret = $this->ensureUserQrSecret($uid);
        $ts = time();
        $nonce = bin2hex(random_bytes(8)); // 16 hex
        $sig = $this->signToken($uid, $ts, $nonce, $userSecret);
        return "{$uid}.{$ts}.{$nonce}.{$sig}";
    }

    // $maxAgeSeconds null değilse onu kullan; null ise varsayılan property
    public function verifyToken(string $token, ?int $maxAgeSeconds = null): array
    {
        // Token format: uid.ts.nonce.sig
        $parts = explode('.', $token);
        if (count($parts) !== 4) {
            throw new Exception("Invalid token format");
        }
        [$uidStr, $tsStr, $nonce, $sig] = $parts;

        if (!ctype_digit($uidStr) || !ctype_digit($tsStr)) {
            throw new Exception("Invalid token parts");
        }
        // Nonce: createTokenForUser 16 hex üretir; onu doğrulayalım:
        if (!preg_match('/^[a-f0-9]{16}$/', $nonce)) {
            throw new Exception("Invalid nonce");
        }
        // Sig base64url olmalı; temel bir uzunluk kontrolü opsiyonel

        $uid = (int)$uidStr;
        $ts = (int)$tsStr;

        // Kullanıcı ve secret
        $stmt = $this->db->prepare("SELECT id, qr_secret FROM users WHERE id = :id");
        $stmt->execute([':id' => $uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception("User not found");
        }
        if (empty($user['qr_secret'])) {
            throw new Exception("User QR secret missing");
        }

        // İmza doğrulama
        $expectedSig = $this->signToken($uid, $ts, $nonce, $user['qr_secret']);
        if (!hash_equals($expectedSig, $sig)) {
            throw new Exception("Signature mismatch");
        }

        // Zaman kontrolü
        $now = time();
        $limit = $maxAgeSeconds ?? $this->maxAge;
        if (abs($now - $ts) > $limit) {
            throw new Exception("Token too old");
        }

        return ['uid' => $uid, 'ts' => $ts, 'nonce' => $nonce];
    }

    // Nonce tüketimi: aynı (user_id, nonce) ikilisi ikinci kez eklenirse UNIQUE ihlali (PG: 23505)
    public function consumeNonce(int $uid, string $nonce): void
    {
        $stmt = $this->db->prepare("INSERT INTO used_nonces (user_id, nonce) VALUES (:u, :n)");
        try {
            $stmt->execute([':u' => $uid, ':n' => $nonce]);
        } catch (\PDOException $e) {
            $sqlState = $e->getCode(); // PostgreSQL: 23505, MySQL: 23000
            if ($sqlState === '23505' || $sqlState === '23000') {
                throw new Exception("Replay detected");
            }
            throw $e;
        }
        // Temizlik (30 günden eski nonceler)
        $cleanup = $this->db->prepare("DELETE FROM used_nonces WHERE used_at < NOW() - INTERVAL '30 days'");
        $cleanup->execute();
    }
}
