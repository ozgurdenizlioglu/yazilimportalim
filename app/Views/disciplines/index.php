<?php

/** @var array $disciplines */
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Disiplin Yönetimi</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="/disciplines/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Yeni Disiplin Ekle
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Adı</th>
                        <th>Kod</th>
                        <th style="width: 100px;">Durum</th>
                        <th style="width: 150px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($disciplines)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Hiç disiplin bulunamadı. <a href="/disciplines/create">Yeni disiplin ekleyin.</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($disciplines as $discipline): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$discipline['id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($discipline['name']) ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($discipline['code'])): ?>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($discipline['code']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($discipline['is_active']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/disciplines/edit?id=<?= htmlspecialchars((string)$discipline['id']) ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Düzenle
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete(<?= (int)$discipline['id'] ?>)">
                                        <i class="bi bi-trash"></i> Sil
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Disiplin Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu disiplini silmek istediğinizden emin misiniz?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Evet, Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').action = '/disciplines/delete';
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>