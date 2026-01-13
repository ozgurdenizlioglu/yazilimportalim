<?php

use App\Core\Helpers;

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Muhasebe') ?></h1>
    <a class="btn btn-primary" href="/muhasebe/create"><i class="bi bi-plus-lg me-1"></i>Yeni Kayıt</a>
</div>

<?php if (!empty($records)): ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Proje</th>
                            <th>Tahakkuk Tarihi</th>
                            <th>Vade Tarihi</th>
                            <th>Çek No</th>
                            <th>Açıklama</th>
                            <th>Tutar (TRY)</th>
                            <th>USD Karşılığı</th>
                            <th>Cari Hesap</th>
                            <th style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= Helpers::e($record['id']) ?></td>
                                <td><?= Helpers::e($record['proje']) ?></td>
                                <td><?= $record['tahakkuk_tarihi'] ? date('d.m.Y', strtotime($record['tahakkuk_tarihi'])) : '-' ?></td>
                                <td><?= $record['vade_tarihi'] ? date('d.m.Y', strtotime($record['vade_tarihi'])) : '-' ?></td>
                                <td><?= Helpers::e($record['cek_no']) ?></td>
                                <td><?= Helpers::e($record['aciklama']) ?></td>
                                <td class="text-end"><?= $record['tutar_try'] ? number_format($record['tutar_try'], 2, ',', '.') : '-' ?></td>
                                <td class="text-end"><?= $record['usd_karsiligi'] ? number_format($record['usd_karsiligi'], 2, ',', '.') : '-' ?></td>
                                <td><?= Helpers::e($record['cari_hesap_ismi']) ?></td>
                                <td>
                                    <a href="/muhasebe/edit?id=<?= $record['id'] ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?= $record['id'] ?>)" title="Sil">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info" role="alert">
        <i class="bi bi-info-circle me-2"></i>Henüz kayıt bulunmamaktadır. <a href="/muhasebe/create" class="alert-link">Yeni kayıt ekleyin</a>
    </div>
<?php endif; ?>

<script>
    function deleteRecord(id) {
        if (confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/muhasebe/delete';
            form.innerHTML = '<input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>