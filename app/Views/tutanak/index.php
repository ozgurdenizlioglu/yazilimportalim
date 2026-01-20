<?php

use App\Core\Helpers;

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Tutanaklar') ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/tutanak/create"><i class="bi bi-plus-lg me-1"></i>Yeni Tutanak</a>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excele Aktar</button>
    </div>
</div>

<?php if (!empty($tutanaks)): ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tutanakTable">
                    <thead class="table-light">
                        <tr>
                            <th>Tutanak No</th>
                            <th>Tutanak Başlık</th>
                            <th>Proje</th>
                            <th>Tarih</th>
                            <th>Konu</th>
                            <th>Tür</th>
                            <th>Miktar</th>
                            <th>Birim</th>
                            <th>Birim Fiyat</th>
                            <th>Tutar</th>
                            <th>Ödeme Firma</th>
                            <th>Kesinti Firma</th>
                            <th class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutanaks as $t): ?>
                            <tr>
                                <td><?= Helpers::e($t['tutanak_no'] ?? '') ?></td>
                                <td><strong><?= Helpers::e($t['tutanak_title'] ?? '') ?></strong></td>
                                <td><?= Helpers::e($t['project_name'] ?? '') ?></td>
                                <td><?= $t['tarih'] ? date('d.m.Y', strtotime($t['tarih'])) : '' ?></td>
                                <td><?= Helpers::e($t['konu'] ?? '') ?></td>
                                <td><?= Helpers::e($t['tur'] ?? '') ?></td>
                                <td class="text-end"><?= number_format((float)($t['miktar'] ?? 0), 2, ',', '.') ?></td>
                                <td><?= Helpers::e($t['birim_name'] ?? '') ?></td>
                                <td class="text-end"><?= number_format((float)($t['birim_fiyat'] ?? 0), 2, ',', '.') ?> ₺</td>
                                <td class="text-end"><strong><?= number_format((float)($t['tutar'] ?? 0), 2, ',', '.') ?> ₺</strong></td>
                                <td><?= Helpers::e($t['odeme_yapilacak_firma'] ?? '') ?></td>
                                <td><?= Helpers::e($t['kesinti_yapilacak_firma'] ?? '') ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/tutanak/edit?id=<?= $t['id'] ?>" class="btn btn-outline-primary" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btnDelete" data-id="<?= $t['id'] ?>" title="Sil">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Delete handlers
            document.querySelectorAll('.btnDelete').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = e.currentTarget.dataset.id;
                    if (!confirm('Bu tutanağı silmek istediğinize emin misiniz?')) return;

                    try {
                        const formData = new FormData();
                        formData.append('id', id);

                        const response = await fetch('/tutanak/delete', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Tutanak silindi');
                            location.reload();
                        } else {
                            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Hata: ' + err.message);
                    }
                });
            });

            // Export to Excel
            document.getElementById('exportExcel')?.addEventListener('click', () => {
                const table = document.getElementById('tutanakTable');
                const workbook = XLSX.utils.table_to_book(table, {
                    sheet: "Tutanaklar"
                });
                XLSX.writeFile(workbook, 'Tutanaklar_' + new Date().toISOString().split('T')[0] + '.xlsx');
            });
        });
    </script>

<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>Henüz tutanak kaydı bulunmamaktadır.
    </div>
<?php endif; ?>