<?php
// Genel yapılandırma
define('BASE_PATH', realpath(__DIR__ . '/../../'));
define('BASE_URL', 'https://seninsite.com/');
define('PROJECTS_PATH', BASE_PATH . '/projects/');
define('INCLUDES_PATH', BASE_PATH . '/includes/core/');

// Veritabanı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'parolan');
define('DB_NAME', 'myportfolio');

error_reporting(E_ALL);
ini_set('display_errors', 1);