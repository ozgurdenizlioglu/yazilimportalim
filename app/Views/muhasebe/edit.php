<?php

use App\Core\Helpers;

ob_start();

$m = $record ?? [];

$action = '/muhasebe/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Muhasebe Kaydını Düzenle';

$showIdHidden = true;

$backUrl = '/muhasebe';

include __DIR__ . '/_form.php';

$content = ob_get_clean();

include __DIR__ . '/../layouts/base.php';
