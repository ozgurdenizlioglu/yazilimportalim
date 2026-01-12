<?php

use App\Core\Helpers;

ob_start();

$c = [

    'is_active' => true,

];

$action = '/contracts/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Sözleşme Ekle';

$showIdHidden = false;

$backUrl = '/contracts';

include __DIR__ . '/_form.php';

$content = ob_get_clean();

include __DIR__ . '/../layouts/base.php';
