<?php

use App\Core\Helpers;

$record = [
    'is_active' => true,
];

$action = '/barter/store';
$submitLabel = 'Kaydet';
$title = $title ?? 'Yeni Barter Kaydı Oluştur';
$showIdHidden = false;
$backUrl = '/barter';

include __DIR__ . '/_form.php';
