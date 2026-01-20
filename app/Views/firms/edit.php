<?php

use App\Core\Helpers;

$raw = $firm ?? ($company ?? []);

$c = is_array($raw) ? $raw : (array)$raw;

// Partial’a parametreler

$action = '/firms/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Firmayı Düzenle';

$showIdHidden = true;

$backUrl = '/firms';

include __DIR__ . '/_form.php';
