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

// WEB rapor ve index metotlarınız aynı kalsın...

// API: POST /api/attendance/scan

public function store(): void

{

header('Content-Type: application/json; charset=utf-8');

// İçerik tipini tespit edelim

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

$isMultipart = stripos($contentType, 'multipart/form-data') !== false;

$isJson = stripos($contentType, 'application/json') !== false;

// Ortak değişkenler

$token = null;

$uid = null;

$type = null;

$meta = null;

// Fotoğrafla ilgili

$savedImagePath = null; // sunucudaki tam path

$savedImageUrl = null; // public erişim URL'si kullanıyorsanız doldurabilirsiniz

try {

if ($isMultipart) {

// 1) multipart/form-data

$token = $_POST['token'] ?? null;

$type = $_POST['type'] ?? null;

// meta opsiyonel

if (!empty($_POST['meta'])) {

$decoded = json_decode((string)$_POST['meta'], true);

if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

$meta = $decoded;

} else {

throw new Exception('meta alanı geçerli JSON değil');

}

}

// Fotoğraf zorunlu

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {

throw new Exception('image dosyası gerekli');

}

// Görsel doğrulama ve kaydetme

[$savedImagePath, $savedImageUrl] = $this->saveUploadedImage($_FILES['image']);

} else {

// 2) JSON (mevcut davranış)

$raw = file_get_contents('php://input') ?: '';

$body = json_decode($raw, true);

if (!is_array($body)) {

http_response_code(400);

echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);

return;

}

$type = $body['type'] ?? null;

$token = $body['token'] ?? null;

$uid = $body['uid'] ?? null;

$meta = $body['meta'] ?? null;

}

// Kimlik doğrulama: token öncelikli

if ($token) {

$payload = $this->tokens->verifyToken($token, 120);

$this->tokens->consumeNonce($payload['uid'], $payload['nonce']);

$uid = (int)$payload['uid'];

} elseif ($uid && ctype_digit((string)$uid)) {

$uid = (int)$uid;

} else {

throw new Exception('token veya uid gerekli');

}

$user = $this->getUser($uid);

if (!$user) {

throw new Exception('User not found');

}

// Tür belirleme

if ($type === null || $type === '') {

$type = $this->nextTypeForUser($uid);

} else {

$type = strtolower((string)$type);

if ($type !== 'in' && $type !== 'out') {

throw new Exception('Invalid type');

}

}

// Cihaz ve konum bilgisini notlara/kolonlara işlemek

$sourceDevice = $this->buildSourceDevice($meta);

$notesJson = $this->buildNotesJson($meta, $savedImagePath, $savedImageUrl);

$attendanceId = $this->createAttendance($uid, $type, $sourceDevice, $notesJson);

echo json_encode([

'ok' => true,

'user' => [

'id' => (int)$user['id'],

'full_name' => $user['full_name'] ?? null,

],

'attendance_id' => $attendanceId,

'type' => $type,

'scanned_at' => gmdate('c'),

'image_saved' => (bool)$savedImagePath,

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

private function createAttendance(int $uid, string $type, ?string $sourceDevice = null, ?string $notes = null): int

{

$stmt = $this->db->prepare('

INSERT INTO attendance (user_id, type, source_device, notes)

VALUES (:u, :t, :d, :n)

RETURNING id

');

$stmt->execute([

':u' => $uid,

':t' => $type,

':d' => $sourceDevice,

':n' => $notes

]);

return (int)$stmt->fetchColumn();

}

private function saveUploadedImage(array $file): array

{

// Boyut limiti: 6 MB

$maxBytes = 6 * 1024 * 1024;

if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {

throw new Exception('image boyutu geçersiz (maks 6 MB)');

}

// MIME doğrulama (server-side)

$finfo = new \finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($file['tmp_name']);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

if (!isset($allowed[$mime])) {

throw new Exception('Sadece JPEG/PNG kabul edilir');

}

// Yıl/Ay klasörü

$y = date('Y'); $m = date('m');

$baseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'attendance_photos' . DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m;

if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true)) {

throw new Exception('Fotoğraf dizini oluşturulamadı');

}

$ext = $allowed[$mime];

$filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

$destPath = $baseDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {

throw new Exception('Fotoğraf kaydedilemedi');

}

// İsterseniz public URL üretin; şimdilik null

$publicUrl = null;

return [$destPath, $publicUrl];

}

private function buildSourceDevice(?array $meta): ?string

{

if (!$meta || empty($meta['device'])) {

return null;

}

$d = $meta['device'];

$ua = $d['userAgent'] ?? '';

$platform = $d['platform'] ?? '';

$lang = $d['language'] ?? '';

// Kısa bir özet

$summary = trim($platform . ' | ' . $ua . ' | ' . $lang);

// Çok uzunsa kısalt

if (strlen($summary) > 500) {

$summary = substr($summary, 0, 500);

}

return $summary ?: null;

}

private function buildNotesJson(?array $meta, ?string $imagePath, ?string $imageUrl): ?string

{

$payload = [];

if ($meta) {

$payload['meta'] = $meta;

}

if ($imagePath || $imageUrl) {

$payload['photo'] = [

'path' => $imagePath,

'url' => $imageUrl

];

}

if (empty($payload)) {

return null;

}

return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

}

}