<?php

use App\Core\Helpers;

$action = '/costcodes/update';
$submitLabel = 'Güncelle';
$title = $title ?? 'Maliyet Kodu Düzenle';
$showIdHidden = true;
$backUrl = '/costcodes';

include __DIR__ . '/_form.php';
