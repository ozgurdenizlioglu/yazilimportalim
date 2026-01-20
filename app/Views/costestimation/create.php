<?php

use App\Core\Helpers;

$m = [];

$action = '/costestimation/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Cost Estimation - Yeni Kayıt';

$showIdHidden = false;

$backUrl = '/costestimation';

include __DIR__ . '/_form.php';
