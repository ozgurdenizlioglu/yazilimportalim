<?php

use App\Core\Helpers;

// Expected: $m, $action, $submitLabel, $title, $showIdHidden, $backUrl

?>
<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Helpers::e($backUrl ?? '/bakiye') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Geri
        </a>
    </div>
</div>

<form method="post" action="<?= Helpers::e($action) ?>" novalidate>
    <?php if (!empty($showIdHidden)): ?>
        <input type="hidden" name="id" value="<?= Helpers::e((string)($m['id'] ?? '')) ?>">
    <?php endif; ?>

    <style>
        .form-label {
            display: block;
            margin-bottom: .5rem;
            line-height: 1.3;
            font-weight: 600;
            font-size: 1rem;
        }

        .card .form-control,
        .card .form-select,
        .card textarea {
            margin-bottom: 1rem;
            padding: .75rem 1rem;
            font-size: 1rem;
        }

        .card {
            border-radius: .6rem;
        }

        .card-header {
            font-weight: 700;
            background: #f8f9fb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 992px) {
            .form-grid {
                grid-template-columns: minmax(640px, 1fr) minmax(640px, 1fr);
                gap: 2.5rem;
            }
        }

        .form-card {
            width: 100%;
        }
    </style>

    <div class="form-grid">
        <!-- Left Column -->
        <div>
            <!-- Basic Info -->
            <div class="card form-card">
                <div class="card-header">Temel Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Proje</label>
                    <input type="text" class="form-control" name="proje" value="<?= Helpers::e($m['proje'] ?? '') ?>" required>

                    <label class="form-label">Tahakkuk Tarihi</label>
                    <input type="date" class="form-control" name="tahakkuk_tarihi" value="<?= Helpers::e($m['tahakkuk_tarihi'] ?? '') ?>">

                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" class="form-control" name="vade_tarihi" value="<?= Helpers::e($m['vade_tarihi'] ?? '') ?>">

                    <label class="form-label">Çek No</label>
                    <input type="text" class="form-control" name="cek_no" value="<?= Helpers::e($m['cek_no'] ?? '') ?>">

                    <label class="form-label">Cari Hesap İsmi</label>
                    <input type="text" class="form-control" name="cari_hesap_ismi" value="<?= Helpers::e($m['cari_hesap_ismi'] ?? '') ?>">
                </div>
            </div>

            <!-- Amounts -->
            <div class="card form-card">
                <div class="card-header">Tutarlar</div>
                <div class="card-body">
                    <label class="form-label">Tutar (TRY)</label>
                    <input type="number" class="form-control" name="tutar_try" value="<?= Helpers::e($m['tutar_try'] ?? '') ?>" step="0.01">

                    <label class="form-label">USD Karşılığı</label>
                    <input type="number" class="form-control" name="usd_karsiligi" value="<?= Helpers::e($m['usd_karsiligi'] ?? '') ?>" step="0.01">
                </div>
            </div>

            <!-- WB/WS/Row -->
            <div class="card form-card">
                <div class="card-header">Kod Bilgileri</div>
                <div class="card-body">
                    <label class="form-label">WB</label>
                    <input type="text" class="form-control" name="wb" value="<?= Helpers::e($m['wb'] ?? '') ?>">

                    <label class="form-label">WS</label>
                    <input type="text" class="form-control" name="ws" value="<?= Helpers::e($m['ws'] ?? '') ?>">

                    <label class="form-label">Row</label>
                    <input type="text" class="form-control" name="row_col" value="<?= Helpers::e($m['row_col'] ?? '') ?>">

                    <label class="form-label">Cost Code</label>
                    <input type="text" class="form-control" name="cost_code" value="<?= Helpers::e($m['cost_code'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Descriptions -->
            <div class="card form-card">
                <div class="card-header">Açıklamalar</div>
                <div class="card-body">
                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" name="aciklama" rows="3"><?= Helpers::e($m['aciklama'] ?? '') ?></textarea>

                    <label class="form-label">Açıklama 2</label>
                    <textarea class="form-control" name="aciklama2" rows="2"><?= Helpers::e($m['aciklama2'] ?? '') ?></textarea>

                    <label class="form-label">Açıklama 3</label>
                    <textarea class="form-control" name="aciklama3" rows="2"><?= Helpers::e($m['aciklama3'] ?? '') ?></textarea>

                    <label class="form-label">Dikkate Alınmayacaklar</label>
                    <textarea class="form-control" name="dikkate_alinmayacaklar" rows="2"><?= Helpers::e($m['dikkate_alinmayacaklar'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- ID References -->
            <div class="card form-card">
                <div class="card-header">ID Referansları</div>
                <div class="card-body">
                    <label class="form-label">ID (Text)</label>
                    <input type="text" class="form-control" name="id_text" value="<?= Helpers::e($m['id_text'] ?? '') ?>">

                    <label class="form-label">ID Veriler</label>
                    <input type="text" class="form-control" name="id_veriler" value="<?= Helpers::e($m['id_veriler'] ?? '') ?>">

                    <label class="form-label">ID - Ödeme Plan & Satın Alma Ödeme Onay Listesi</label>
                    <input type="text" class="form-control" name="id_odeme_plan_satinalma_odeme_onay_listesi" value="<?= Helpers::e($m['id_odeme_plan_satinalma_odeme_onay_listesi'] ?? '') ?>">
                </div>
            </div>

            <!-- Notes -->
            <div class="card form-card">
                <div class="card-header">Notlar</div>
                <div class="card-body">
                    <label class="form-label">Not</label>
                    <textarea class="form-control" name="not_field" rows="2"><?= Helpers::e($m['not_field'] ?? '') ?></textarea>

                    <label class="form-label">Not OOL/Ödeme Planı</label>
                    <textarea class="form-control" name="not_ool_odeme_plani" rows="2"><?= Helpers::e($m['not_ool_odeme_plani'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><?= Helpers::e($submitLabel) ?>
        </button>
        <a href="<?= Helpers::e($backUrl ?? '/bakiye') ?>" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
        </a>
    </div>
</form>