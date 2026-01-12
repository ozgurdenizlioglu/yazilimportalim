<?php

/** @var array|null $discipline */
$discipline = $discipline ?? null;
$isEdit = $discipline !== null;
?>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="name_tr" class="form-label">Disiplin Adı (Türkçe) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name_tr" name="name_tr"
                value="<?= htmlspecialchars($discipline['name_tr'] ?? '') ?>"
                placeholder="Örn: İnşaat, Elektrik, Tesisatçı" required>
            <small class="text-muted">Türkçe adını yazın (zorunlu)</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="name_en" class="form-label">Disiplin Adı (İngilizce) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name_en" name="name_en"
                value="<?= htmlspecialchars($discipline['name_en'] ?? '') ?>"
                placeholder="Örn: Construction, Electrical, Plumbing" required>
            <small class="text-muted">İngilizce adını yazın (zorunlu)</small>
        </div>
    </div>
</div>

<?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int)$discipline['id'] ?>">
<?php endif; ?>