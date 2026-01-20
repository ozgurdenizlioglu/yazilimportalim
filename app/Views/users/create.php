<?php

use App\Core\Helpers;

// Create için varsayılan/boş değerler

$c = [

    'is_active' => true,

];

$action = '/users/store';

$submitLabel = 'Kaydet';

$title = $title ?? 'Kullanıcı Ekle';

$showIdHidden = false;

$backUrl = '/users';

// $companies zaten controller’dan gelebilir; partial'a geçeceğiz.

$companies = $companies ?? [];

include __DIR__ . '/_form.php';
