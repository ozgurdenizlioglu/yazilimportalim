<?php

use App\Core\Helpers; ?>

<div class="mb-3">
    <a href="/muhasebe" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Geri Dön</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="/muhasebe/update" class="row g-3">

            <input type="hidden" name="id" value="<?= Helpers::e($record['id']) ?>">

            <div class="col-md-6">
                <label for="proje" class="form-label">Proje</label>
                <input type="text" class="form-control" id="proje" name="proje" value="<?= Helpers::e($record['proje'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label for="tahakkuk_tarihi" class="form-label">Tahakkuk Tarihi</label>
                <input type="date" class="form-control" id="tahakkuk_tarihi" name="tahakkuk_tarihi" value="<?= $record['tahakkuk_tarihi'] ?? '' ?>">
            </div>

            <div class="col-md-6">
                <label for="vade_tarihi" class="form-label">Vade Tarihi</label>
                <input type="date" class="form-control" id="vade_tarihi" name="vade_tarihi" value="<?= $record['vade_tarihi'] ?? '' ?>">
            </div>

            <div class="col-md-6">
                <label for="cek_no" class="form-label">Çek No</label>
                <input type="text" class="form-control" id="cek_no" name="cek_no" value="<?= Helpers::e($record['cek_no'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label for="aciklama" class="form-label">Açıklama</label>
                <textarea class="form-control" id="aciklama" name="aciklama" rows="3"><?= Helpers::e($record['aciklama'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label for="aciklama2" class="form-label">Açıklama 2</label>
                <textarea class="form-control" id="aciklama2" name="aciklama2" rows="2"><?= Helpers::e($record['aciklama2'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label for="aciklama3" class="form-label">Açıklama 3</label>
                <textarea class="form-control" id="aciklama3" name="aciklama3" rows="2"><?= Helpers::e($record['aciklama3'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="tutar_try" class="form-label">Tutar (TRY)</label>
                <input type="number" class="form-control" id="tutar_try" name="tutar_try" value="<?= $record['tutar_try'] ?? '' ?>" step="0.01">
            </div>

            <div class="col-md-6">
                <label for="usd_karsiligi" class="form-label">USD Karşılığı</label>
                <input type="number" class="form-control" id="usd_karsiligi" name="usd_karsiligi" value="<?= $record['usd_karsiligi'] ?? '' ?>" step="0.01">
            </div>

            <div class="col-md-6">
                <label for="cari_hesap_ismi" class="form-label">Cari Hesap İsmi</label>
                <input type="text" class="form-control" id="cari_hesap_ismi" name="cari_hesap_ismi" value="<?= Helpers::e($record['cari_hesap_ismi'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="wb" class="form-label">WB</label>
                <input type="text" class="form-control" id="wb" name="wb" value="<?= Helpers::e($record['wb'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="ws" class="form-label">WS</label>
                <input type="text" class="form-control" id="ws" name="ws" value="<?= Helpers::e($record['ws'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="row_col" class="form-label">Row</label>
                <input type="text" class="form-control" id="row_col" name="row_col" value="<?= Helpers::e($record['row_col'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="cost_code" class="form-label">Cost Code</label>
                <input type="text" class="form-control" id="cost_code" name="cost_code" value="<?= Helpers::e($record['cost_code'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label for="dikkate_alinmayacaklar" class="form-label">Dikkate Alınmayacaklar</label>
                <textarea class="form-control" id="dikkate_alinmayacaklar" name="dikkate_alinmayacaklar" rows="2"><?= Helpers::e($record['dikkate_alinmayacaklar'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="id_text" class="form-label">ID (Text)</label>
                <input type="text" class="form-control" id="id_text" name="id_text" value="<?= Helpers::e($record['id_text'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="id_veriler" class="form-label">ID Veriler</label>
                <input type="text" class="form-control" id="id_veriler" name="id_veriler" value="<?= Helpers::e($record['id_veriler'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label for="id_odeme_plan_satinalma_odeme_onay_listesi" class="form-label">ID - Ödeme Plan & Satın Alma Ödeme Onay Listesi</label>
                <input type="text" class="form-control" id="id_odeme_plan_satinalma_odeme_onay_listesi" name="id_odeme_plan_satinalma_odeme_onay_listesi" value="<?= Helpers::e($record['id_odeme_plan_satinalma_odeme_onay_listesi'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label for="not_field" class="form-label">Not</label>
                <textarea class="form-control" id="not_field" name="not_field" rows="2"><?= Helpers::e($record['not_field'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label for="not_ool_odeme_plani" class="form-label">Not OOL/Ödeme Planı</label>
                <textarea class="form-control" id="not_ool_odeme_plani" name="not_ool_odeme_plani" rows="2"><?= Helpers::e($record['not_ool_odeme_plani'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Güncelle
                </button>
                <a href="/muhasebe" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i>İptal
                </a>
            </div>
        </form>
    </div>
</div>