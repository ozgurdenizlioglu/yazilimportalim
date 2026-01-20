<?php

use App\Core\Helpers;

$m = $record ?? [];

$action = '/costestimation/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Cost Estimation - Düzenle';

$showIdHidden = true;

$backUrl = '/costestimation';

include __DIR__ . '/_form.php';
