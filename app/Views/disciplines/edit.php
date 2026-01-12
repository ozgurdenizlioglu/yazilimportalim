<?php

/** @var array $discipline */
/** @var array $branches */
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Disiplin Düzenle: <?= htmlspecialchars($discipline['name']) ?></h1>
        </div>
    </div>

    <!-- Disiplin Bilgileri -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Disiplin Bilgileri</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="/disciplines/update">
                <?php include '_form.php'; ?>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Güncelle
                    </button>
                    <a href="/disciplines" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Geri
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Alt Disiplinler (Branches) -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alt Disiplinler (Branşlar)</h5>
            <a href="#" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="bi bi-plus-circle"></i> Alt Disiplin Ekle
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($branches)): ?>
                <p class="text-muted mb-0">Bu disipline henüz alt disiplin eklenmemiştir.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Adı (Türkçe)</th>
                                <th>Adı (İngilizce)</th>
                                <th style="width: 120px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branches as $branch): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$branch['id']) ?></td>
                                    <td><?= htmlspecialchars($branch['name_tr']) ?></td>
                                    <td><?= htmlspecialchars($branch['name_en']) ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-danger"
                                            onclick="confirmBranchDelete(<?= (int)$branch['id'] ?>)">
                                            <i class="bi bi-trash"></i> Sil
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alt Disiplin Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/disciplines/store-branch">
                <div class="modal-body">
                    <input type="hidden" name="discipline_id" value="<?= (int)$discipline['id'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="branch_name_tr" class="form-label">Alt Disiplin Adı (Türkçe) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_name_tr" name="name_tr"
                                    placeholder="Örn: Beton İşleri, Demir" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="branch_name_en" class="form-label">Alt Disiplin Adı (İngilizce) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_name_en" name="name_en"
                                    placeholder="Örn: Concrete, Steel" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmBranchDelete(id) {
        if (confirm('Bu alt disiplini silmek istediğinizden emin misiniz?')) {
            // TODO: Implement branch delete
            console.log('Delete branch:', id);
        }
    }
</script>