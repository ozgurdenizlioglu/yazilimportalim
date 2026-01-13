<?php

use App\Core\Helpers;

ob_start();

$m = [];

$action = '/muhasebe/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Muhasebe Kaydı Ekle';

$showIdHidden = false;

$backUrl = '/muhasebe';

include __DIR__ . '/_form.php';

$content = ob_get_clean();

include __DIR__ . '/../layouts/base.php';
