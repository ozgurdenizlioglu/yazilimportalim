<?php

// C:\Users\ozgur\myapp\public\index.php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Controllers\FirmController;
use App\Controllers\ScanController;
use App\Controllers\AttendanceController;

// .env yükle
$root = dirname(__DIR__);
if (file_exists($root . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

// Hata gösterimi
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : 0);

// Config (gerekliyse)
$config = require __DIR__ . '/../app/config.php';

// Router
$router = new Router();

// Root
$router->get('/', [HomeController::class, 'index']);

// Rotalar - users
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/store', [UserController::class, 'store']);
$router->get('/users/edit', [UserController::class, 'edit']); // ?id=...
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/delete', [UserController::class, 'destroy']);

// QR/Scan/Attendance
$router->get('/scan', [ScanController::class, 'show']);
$router->get('/attendance', [AttendanceController::class, 'index']);
$router->get('/attendance/report', [AttendanceController::class, 'report']); // <-- BUNU EKLE
$router->post('/api/attendance/scan', [AttendanceController::class, 'store']);

// Geçici: token üretimi (sadece geliştirme)
$router->get('/users/token', [UserController::class, 'token']);

// Rotalar - firms
$router->get('/firms', [FirmController::class, 'index']);
$router->get('/firms/create', [FirmController::class, 'create']);
$router->post('/firms/store', [FirmController::class, 'store']);
$router->get('/firms/edit', [FirmController::class, 'edit']); // ?id=...
$router->post('/firms/update', [FirmController::class, 'update']);
$router->post('/firms/delete', [FirmController::class, 'destroy']);

// İsteği çalıştır
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$router->dispatch($method, $path);
