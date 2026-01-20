<?php

use App\Core\Helpers;

// Expected: $record, $action, $submitLabel, $title, $showIdHidden, $backUrl

?>

<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Helpers::e($backUrl ?? '/barter') ?>" class="btn btn-outline-secondary">
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
            <!-- Proje ve Cost Code -->
            <div class="card form-card">
                <div class="card-header">Temel Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Proje</label>
                    <input type="text" class="form-control" name="proje" list="projectList" value="<?= Helpers::e($record['proje'] ?? '') ?>">
                    <datalist id="projectList"></datalist>

                    <label class="form-label">Cost Code</label>
                    <input type="text" class="form-control" name="cost_code" value="<?= Helpers::e($record['cost_code'] ?? '') ?>">

                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" name="aciklama" rows="3"><?= Helpers::e($record['aciklama'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Barter Tutarı ve Currency -->
            <div class="card form-card">
                <div class="card-header">Barter Tutarı</div>
                <div class="card-body">
                    <label class="form-label">Barter Tutarı</label>
                    <input type="number" class="form-control" name="barter_tutari" step="0.01" value="<?= Helpers::e($record['barter_tutari'] ?? '') ?>">

                    <label class="form-label">Barter Currency</label>
                    <input type="text" class="form-control" name="barter_currency" value="<?= Helpers::e($record['barter_currency'] ?? '') ?>">

                    <label class="form-label">Barter Gerçekleşen</label>
                    <input type="number" class="form-control" name="barter_gerceklesen" step="0.01" value="<?= Helpers::e($record['barter_gerceklesen'] ?? '') ?>">
                </div>
            </div>

            <!-- Barter Planlanan -->
            <div class="card form-card">
                <div class="card-header">Barter Planlanan</div>
                <div class="card-body">
                    <label class="form-label">Barter - Planlanan Oran</label>
                    <input type="text" class="form-control" name="barter_planlanan_oran" value="<?= Helpers::e($record['barter_planlanan_oran'] ?? '') ?>">

                    <label class="form-label">Barter - Planlanan Tutar</label>
                    <input type="number" class="form-control" name="barter_planlanan_tutar" step="0.01" value="<?= Helpers::e($record['barter_planlanan_tutar'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Finansal Bilgiler -->
            <div class="card form-card">
                <div class="card-header">Finansal Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Sözleşme Tarihi</label>
                    <input type="date" class="form-control" name="sozlesme_tarihi" value="<?= Helpers::e($record['sozlesme_tarihi'] ?? '') ?>">

                    <label class="form-label">Kur</label>
                    <input type="number" class="form-control" name="kur" step="0.0001" value="<?= Helpers::e($record['kur'] ?? '') ?>">

                    <label class="form-label">USD Karşılığı</label>
                    <input type="number" class="form-control" name="usd_karsiligi" step="0.01" value="<?= Helpers::e($record['usd_karsiligi'] ?? '') ?>">

                    <label class="form-label">Tutar TRY</label>
                    <input type="number" class="form-control" name="tutar_try" step="0.01" value="<?= Helpers::e($record['tutar_try'] ?? '') ?>">
                </div>
            </div>

            <!-- Ek Bilgiler -->
            <div class="card form-card">
                <div class="card-header">Ek Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Not</label>
                    <textarea class="form-control" name="not_field" rows="3"><?= Helpers::e($record['not_field'] ?? '') ?></textarea>

                    <label class="form-label">Path</label>
                    <input type="text" class="form-control" name="path" value="<?= Helpers::e($record['path'] ?? '') ?>">

                    <label class="form-label">Yüklenici</label>
                    <input type="text" class="form-control" name="yuklenici" value="<?= Helpers::e($record['yuklenici'] ?? '') ?>">

                    <label class="form-label">Karşı Hesap İsmi</label>
                    <input type="text" class="form-control" name="karsi_hesap_ismi" value="<?= Helpers::e($record['karsi_hesap_ismi'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="d-flex gap-2 justify-content-end mt-4 mb-3">
        <a href="<?= Helpers::e($backUrl ?? '/barter') ?>" class="btn btn-secondary">İptal</a>
        <button type="submit" class="btn btn-primary"><?= Helpers::e($submitLabel ?? 'Kaydet') ?></button>
    </div>
</form>

<script>
    async function loadProjects() {
        try {
            const resp = await fetch('/barter/get-projects');
            if (resp.ok) {
                const projects = await resp.json();
                const datalist = document.getElementById('projectList');
                datalist.innerHTML = projects.map(p => `<option value="${p}">`).join('');
            }
        } catch (e) {
            console.warn('Could not load projects:', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadProjects);
    } else {
        loadProjects();
    }
</script>