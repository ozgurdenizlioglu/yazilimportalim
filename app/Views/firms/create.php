<?php

use App\Core\Helpers;

// Create için boş/varsayılan değerler

$c = [

    'status' => 'active',

    'is_active' => true,

];

$action = '/firms/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Firma Ekle';

$showIdHidden = false;

$backUrl = '/firms';

include __DIR__ . '/_form.php';
