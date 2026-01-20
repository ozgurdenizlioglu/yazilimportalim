<?php

use App\Core\Helpers;

$action = '/barter/update';
$submitLabel = 'Güncelle';
$title = $title ?? 'Barter Kaydını Düzenle';
$showIdHidden = true;
$backUrl = '/barter';

include __DIR__ . '/_form.php';
