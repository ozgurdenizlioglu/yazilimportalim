<?php

use App\Core\Helpers;

// Create için boş/varsayılan değerler

$c = [

    'status' => 'active',

    'is_active' => true,

];

$action = '/project/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Proje Ekle';

$showIdHidden = false;

$backUrl = '/project';

include __DIR__ . '/_form.php';
