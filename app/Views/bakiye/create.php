<?php

use App\Core\Helpers;

$m = [];

$action = '/bakiye/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Bakiye Kaydı Ekle';

$showIdHidden = false;

$backUrl = '/bakiye';

include __DIR__ . '/_form.php';
