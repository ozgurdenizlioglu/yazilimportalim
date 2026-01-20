<?php

use App\Core\Helpers;

$record = [
    'is_active' => true,
];

$action = '/costcodes/store';
$submitLabel = 'Kaydet';
$title = $title ?? 'Yeni Maliyet Kodu Oluştur';
$showIdHidden = false;
$backUrl = '/costcodes';

include __DIR__ . '/_form.php';
