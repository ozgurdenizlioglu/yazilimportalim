<?php

use App\Core\Helpers;

$m = $record ?? [];

$action = '/bakiye/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Bakiye Kaydını Düzenle';

$showIdHidden = true;

$backUrl = '/bakiye';

include __DIR__ . '/_form.php';
