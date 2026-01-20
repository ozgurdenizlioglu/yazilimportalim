<?php

use App\Core\Helpers;

?>

<form method="POST" action="<?= $action ?>" class="needs-validation">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Firma</label>
            <input type="text" class="form-control" name="firma" value="<?= Helpers::e($record['firma'] ?? '') ?>" required>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Proje</label>
            <select class="form-select" name="proje" required>
                <option value="">Seçiniz</option>
                <?php foreach ($projects as $proj): ?>
                    <option value="<?= Helpers::e($proj['name'] ?? '') ?>"
                        <?= (($record['proje'] ?? '') === ($proj['name'] ?? '')) ? 'selected' : '' ?>>
                        <?= Helpers::e($proj['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Tarih</label>
            <input type="date" class="form-control" name="tarih" value="<?= Helpers::e($record['tarih'] ?? '') ?>" required>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Karşı Hesap İsmi</label>
            <input type="text" class="form-control" name="karsi_hesap_ismi" value="<?= Helpers::e($record['karsi_hesap_ismi'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Cost Code</label>
            <input type="text" class="form-control" name="cost_code" value="<?= Helpers::e($record['cost_code'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Vergi Matrahı %20</label>
            <input type="number" step="0.01" class="form-control" name="vergi_matrahı" value="<?= Helpers::e($record['vergi_matrahı'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">K.D.V. (%) 20</label>
            <input type="number" step="0.01" class="form-control" name="kdv_orani" value="<?= Helpers::e($record['kdv_orani'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Tevkifat</label>
            <input type="number" step="0.01" class="form-control" name="tevkifat" value="<?= Helpers::e($record['tevkifat'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Tevkifat Oranı</label>
            <input type="number" step="0.01" class="form-control" name="tevkifat_orani" value="<?= Helpers::e($record['tevkifat_orani'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Toplam</label>
            <input type="number" step="0.01" class="form-control" name="toplam" value="<?= Helpers::e($record['toplam'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">KDV Dahil</label>
            <input type="number" step="0.01" class="form-control" name="kdv_dahil" value="<?= Helpers::e($record['kdv_dahil'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Tevkifat USD</label>
            <input type="number" step="0.01" class="form-control" name="tevkifat_usd" value="<?= Helpers::e($record['tevkifat_usd'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Dikkate Alınmayacaklar</label>
            <textarea class="form-control" name="dikkate_alinmayacaklar" rows="3"><?= Helpers::e($record['dikkate_alinmayacaklar'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="/tevkifat" class="btn btn-secondary">İptal</a>
    </div>
</form>