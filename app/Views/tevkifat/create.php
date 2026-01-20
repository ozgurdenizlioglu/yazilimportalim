<?php

use App\Core\Helpers;

$record = [];
$action = '/tevkifat/store';
$title = $title ?? 'Tevkifat Kaydı Ekle';
$backUrl = '/tevkifat';

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 m-0"><?= Helpers::e($title) ?></h1>
        <a href="<?= $backUrl ?>" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php include '_form.php'; ?>
        </div>
    </div>
</div>