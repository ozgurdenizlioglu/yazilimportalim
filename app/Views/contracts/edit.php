<?php

use App\Core\Helpers;

$raw = $contract ?? [];

$c = is_array($raw) ? $raw : (array)$raw;

$action = '/contracts/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Sözleşmeyi Düzenle';

$showIdHidden = true;

$backUrl = '/contracts';

include __DIR__ . '/_form.php';
