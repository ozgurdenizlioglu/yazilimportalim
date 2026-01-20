<?php

use App\Core\Helpers;

// Expected: $m, $action, $submitLabel, $title, $showIdHidden, $backUrl

?>
<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= Helpers::e($backUrl ?? '/costestimation') ?>" class="btn btn-outline-secondary">
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
            <!-- Proje Bilgileri -->
            <div class="card form-card">
                <div class="card-header">Proje Bilgileri</div>
                <div class="card-body">
                    <label class="form-label">Proje</label>
                    <input type="text" class="form-control" name="proje" value="<?= Helpers::e($m['proje'] ?? '') ?>" list="projectList" required>
                    <datalist id="projectList"></datalist>

                    <label class="form-label">Cost Code</label>
                    <input type="text" class="form-control" name="cost_code" value="<?= Helpers::e($m['cost_code'] ?? '') ?>">

                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" name="aciklama" rows="3"><?= Helpers::e($m['aciklama'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Maliyet Bilgileri -->
            <div class="card form-card">
                <div class="card-header">Maliyet Bilgileri</div>
                <div class="card-body">
                    <label class="form-label">Tür</label>
                    <input type="text" class="form-control" name="tur" value="<?= Helpers::e($m['tur'] ?? '') ?>">

                    <label class="form-label">Birim Maliyet</label>
                    <input type="number" class="form-control" name="birim_maliyet" value="<?= Helpers::e($m['birim_maliyet'] ?? '') ?>" step="0.01">

                    <label class="form-label">Currency</label>
                    <input type="text" class="form-control" name="currency" value="<?= Helpers::e($m['currency'] ?? '') ?>">

                    <label class="form-label">Date</label>
                    <input type="number" class="form-control" name="date" value="<?= Helpers::e($m['date'] ?? '') ?>" step="0.01" placeholder="Timestamp">

                    <label class="form-label">Kur</label>
                    <input type="number" class="form-control" name="kur" value="<?= Helpers::e($m['kur'] ?? '') ?>" step="0.01">
                </div>
            </div>

            <!-- Tutarlar -->
            <div class="card form-card">
                <div class="card-header">Tutarlar</div>
                <div class="card-body">
                    <label class="form-label">Tutar TRY (KDV Hariç)</label>
                    <input type="number" class="form-control" name="tutar_try_kdv_haric" value="<?= Helpers::e($m['tutar_try_kdv_haric'] ?? '') ?>" step="0.01">

                    <label class="form-label">KDV Oranı</label>
                    <input type="text" class="form-control" name="kdv_orani" value="<?= Helpers::e($m['kdv_orani'] ?? '') ?>">

                    <label class="form-label">Tutar TRY (KDV Dahil)</label>
                    <input type="number" class="form-control" name="tutar_try_kdv_dahil" value="<?= Helpers::e($m['tutar_try_kdv_dahil'] ?? '') ?>" step="0.01">
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Birim ve Kapsam -->
            <div class="card form-card">
                <div class="card-header">Birim ve Kapsam</div>
                <div class="card-body">
                    <label class="form-label">Birim</label>
                    <input type="text" class="form-control" name="birim" value="<?= Helpers::e($m['birim'] ?? '') ?>">

                    <label class="form-label">Kapsam</label>
                    <textarea class="form-control" name="kapsam" rows="3"><?= Helpers::e($m['kapsam'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Ek Bilgiler -->
            <div class="card form-card">
                <div class="card-header">Ek Bilgiler</div>
                <div class="card-body">
                    <label class="form-label">Yüklenici</label>
                    <input type="text" class="form-control" name="yuklenici" value="<?= Helpers::e($m['yuklenici'] ?? '') ?>">

                    <label class="form-label">Karşı Hesap İsmi</label>
                    <input type="text" class="form-control" name="karsi_hesap_ismi" value="<?= Helpers::e($m['karsi_hesap_ismi'] ?? '') ?>">

                    <label class="form-label">Sözleşme Durumu</label>
                    <input type="text" class="form-control" name="sozlesme_durumu" value="<?= Helpers::e($m['sozlesme_durumu'] ?? '') ?>">

                    <label class="form-label">Path</label>
                    <input type="text" class="form-control" name="path" value="<?= Helpers::e($m['path'] ?? '') ?>">
                </div>
            </div>

            <!-- Notlar -->
            <div class="card form-card">
                <div class="card-header">Notlar</div>
                <div class="card-body">
                    <label class="form-label">Not</label>
                    <textarea class="form-control" name="not_field" rows="3"><?= Helpers::e($m['not_field'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><?= Helpers::e($submitLabel) ?>
        </button>
        <a href="<?= Helpers::e($backUrl ?? '/costestimation') ?>" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
        </a>
    </div>
</form>

<script>
    // Load projects from API
    async function loadProjects() {
        try {
            const response = await fetch('/costestimation/get-projects');
            const data = await response.json();
            const datalist = document.getElementById('projectList');

            if (data.data && Array.isArray(data.data)) {
                datalist.innerHTML = '';
                data.data.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.name;
                    datalist.appendChild(option);
                });
            }
        } catch (error) {
            console.warn('Could not load projects:', error);
        }
    }

    // Load projects when form loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadProjects);
    } else {
        loadProjects();
    }
</script>