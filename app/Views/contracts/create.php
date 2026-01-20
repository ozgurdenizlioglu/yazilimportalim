<?php

use App\Core\Helpers;

$c = [

    'is_active' => true,

];

$action = '/contracts/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Sözleşme Ekle';

$showIdHidden = false;

$backUrl = '/contracts';

include __DIR__ . '/_form.php';
