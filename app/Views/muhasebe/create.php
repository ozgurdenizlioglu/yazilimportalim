<?php

use App\Core\Helpers;

$m = [];

$action = '/muhasebe/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Muhasebe Kaydı Ekle';

$showIdHidden = false;

$backUrl = '/muhasebe';

include __DIR__ . '/_form.php';
