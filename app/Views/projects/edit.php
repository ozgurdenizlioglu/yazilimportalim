<?php

use App\Core\Helpers;

$raw = $project ?? ($proj ?? ($c ?? []));
$c = is_array($raw) ? $raw : (array)$raw;

// Partial’a parametreler
$action = '/projects/update';
$submitLabel = 'Güncelle';
$title = $title ?? 'Projeyi Düzenle';
$showIdHidden = true;
$backUrl = '/projects';

// (Opsiyonel) Controller firma listesini sağlayabilir:
// $companies = [ 1 => 'ABC Teknoloji A.Ş.', 2 => 'XYZ Yazılım Ltd. Şti.' ];

include __DIR__ . '/_form.php';
