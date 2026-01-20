<?php

use App\Core\Helpers;

// Expected: $record, $action, $submitLabel, $title, $showIdHidden, $backUrl

?>

<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Helpers::e($backUrl ?? '/costcodes') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Geri
        </a>
    </div>
</div>

<form method="post" action="<?= Helpers::e($action) ?>" novalidate>
    <?php if (!empty($showIdHidden)): ?>
        <input type="hidden" name="id" value="<?= Helpers::e((string)($record['id'] ?? '')) ?>">
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
            <!-- Temel Bilgiler -->
            <div class="card form-card">
                <div class="card-header">Temel Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Level</label>
                    <input type="text" class="form-control" name="level" value="<?= Helpers::e($record['level'] ?? '') ?>">

                    <label class="form-label">Üst Başlık / Veri</label>
                    <input type="text" class="form-control" name="ust_baslik_veri" value="<?= Helpers::e($record['ust_baslik_veri'] ?? '') ?>">

                    <label class="form-label">Cost Code</label>
                    <input type="text" class="form-control" name="cost_code" value="<?= Helpers::e($record['cost_code'] ?? '') ?>">
                </div>
            </div>

            <!-- Mali Bilgiler -->
            <div class="card form-card mt-4">
                <div class="card-header">Mali Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Ortalama Gider</label>
                    <input type="number" step="0.01" class="form-control" name="ortalama_gider" value="<?= Helpers::e($record['ortalama_gider'] ?? '') ?>">

                    <label class="form-label">Direct / Indirect</label>
                    <select class="form-select" name="direct_indirect">
                        <option value="">-- Seçiniz --</option>
                        <option value="DIRECT" <?= ($record['direct_indirect'] ?? '') === 'DIRECT' ? 'selected' : '' ?>>DIRECT</option>
                        <option value="INDIRECT" <?= ($record['direct_indirect'] ?? '') === 'INDIRECT' ? 'selected' : '' ?>>INDIRECT</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Açıklama Bilgileri -->
            <div class="card form-card">
                <div class="card-header">Açıklama Bilgileri</div>
                <div class="card-body">
                    <label class="form-label">Muhasebe Kodu Açıklama</label>
                    <input type="text" class="form-control" name="muhasebe_kodu_aciklama" value="<?= Helpers::e($record['muhasebe_kodu_aciklama'] ?? '') ?>">

                    <label class="form-label">Cost Code Description</label>
                    <textarea class="form-control" name="cost_code_description" rows="4"><?= Helpers::e($record['cost_code_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="d-flex justify-content-between align-items-center mt-5">
        <a href="<?= Helpers::e($backUrl ?? '/costcodes') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>İptal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i><?= Helpers::e($submitLabel ?? 'Kaydet') ?>
        </button>
    </div>
</form>