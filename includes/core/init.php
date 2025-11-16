<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Basit sınıf otomat yükleyici
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Veritabanı bağlantısı
require_once __DIR__ . '/db_connect.php';