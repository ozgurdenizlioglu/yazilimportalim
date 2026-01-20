<?php

use App\Core\Helpers;

$m = $record ?? [];

$action = '/muhasebe/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Muhasebe Kaydını Düzenle';

$showIdHidden = true;

$backUrl = '/muhasebe';

include __DIR__ . '/_form.php';
