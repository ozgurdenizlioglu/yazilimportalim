<?php

use App\Core\Helpers;

$t = $tutanak ?? [];

?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Tutanak Düzenle') ?></h1>
    <a href="/tutanak" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <!-- Tutanak Title Display -->
        <div class="alert alert-info mb-3">
            <strong>Tutanak Başlık:</strong> <?= Helpers::e($t['tutanak_title'] ?? '') ?>
        </div>

        <form id="tutanakForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $t['id'] ?? '' ?>">

            <div class="row g-3">

                <!-- Project Selection -->
                <div class="col-md-6">
                    <label for="project_id" class="form-label">Proje <span class="text-danger">*</span></label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Proje Seçin</option>
                        <?php foreach ($projects ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($t['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                <?= Helpers::e($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tutanak No -->
                <div class="col-md-6">
                    <label for="tutanak_no" class="form-label">Tutanak No</label>
                    <input type="text" class="form-control" id="tutanak_no" name="tutanak_no" value="<?= Helpers::e($t['tutanak_no'] ?? '') ?>">
                </div>

                <!-- Tarih -->
                <div class="col-md-4">
                    <label for="tarih" class="form-label">Tarih <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tarih" name="tarih" value="<?= $t['tarih'] ?? date('Y-m-d') ?>" required>
                </div>

                <!-- Tür -->
                <div class="col-md-4">
                    <label for="tur" class="form-label">Tür</label>
                    <select class="form-select" id="tur" name="tur">
                        <option value="">Seçin</option>
                        <option value="MALZEME" <?= ($t['tur'] ?? '') == 'MALZEME' ? 'selected' : '' ?>>Malzeme</option>
                        <option value="YEVMİYE" <?= ($t['tur'] ?? '') == 'YEVMİYE' ? 'selected' : '' ?>>Yevmiye</option>
                        <option value="CEZA" <?= ($t['tur'] ?? '') == 'CEZA' ? 'selected' : '' ?>>Ceza</option>
                        <option value="DİĞER" <?= ($t['tur'] ?? '') == 'DİĞER' ? 'selected' : '' ?>>Diğer</option>
                    </select>
                </div>

                <!-- Malzeme/Yevmiye/Ceza -->
                <div class="col-md-4">
                    <label for="malzeme_yevmiye_ceza" class="form-label">Malzeme/Yevmiye/Ceza</label>
                    <input type="text" class="form-control" id="malzeme_yevmiye_ceza" name="malzeme_yevmiye_ceza" value="<?= Helpers::e($t['malzeme_yevmiye_ceza'] ?? '') ?>">
                </div>

                <!-- Konu -->
                <div class="col-12">
                    <label for="konu" class="form-label">Konu</label>
                    <input type="text" class="form-control" id="konu" name="konu" value="<?= Helpers::e($t['konu'] ?? '') ?>">
                </div>

                <!-- Birim -->
                <div class="col-md-4">
                    <label for="birim_id" class="form-label">Birim</label>
                    <select class="form-select" id="birim_id" name="birim_id">
                        <option value="">Seçin</option>
                        <?php foreach ($units ?? [] as $u):
                            $displayValue = currentLanguage() === 'en' ? $u['EN'] : $u['TR'];
                        ?>
                            <option value="<?= $u['id'] ?>" <?= ($t['birim_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= Helpers::e($displayValue) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Miktar -->
                <div class="col-md-4">
                    <label for="miktar" class="form-label">Miktar</label>
                    <input type="text" class="form-control" id="miktar" name="miktar" value="<?= number_format((float)($t['miktar'] ?? 0), 2, ',', '.') ?>" pattern="[0-9.,]+" inputmode="decimal">
                </div>

                <!-- Birim Fiyat -->
                <div class="col-md-4">
                    <label for="birim_fiyat" class="form-label">Birim Fiyat (₺)</label>
                    <input type="text" class="form-control" id="birim_fiyat" name="birim_fiyat" value="<?= number_format((float)($t['birim_fiyat'] ?? 0), 2, ',', '.') ?>" pattern="[0-9.,]+" inputmode="decimal">
                </div>

                <!-- Tutar (Calculated) -->
                <div class="col-md-6">
                    <label for="tutar" class="form-label">Tutar (₺)</label>
                    <input type="text" class="form-control bg-light" id="tutar" name="tutar" value="<?= number_format((float)($t['tutar'] ?? 0), 2, ',', '.') ?>" readonly>
                </div>

                <!-- Ödeme Yapılacak Firma -->
                <div class="col-md-6">
                    <label for="odeme_yapilacak_firma" class="form-label">Ödeme Yapılacak Firma</label>
                    <input type="text" class="form-control" id="odeme_yapilacak_firma" name="odeme_yapilacak_firma" value="<?= Helpers::e($t['odeme_yapilacak_firma'] ?? '') ?>" list="firmalar">
                    <datalist id="firmalar">
                        <?php foreach ($companies ?? [] as $c): ?>
                            <option value="<?= Helpers::e($c['name']) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- Kesinti Yapılacak Firma -->
                <div class="col-md-6">
                    <label for="kesinti_yapilacak_firma" class="form-label">Kesinti Yapılacak Firma</label>
                    <input type="text" class="form-control" id="kesinti_yapilacak_firma" name="kesinti_yapilacak_firma" value="<?= Helpers::e($t['kesinti_yapilacak_firma'] ?? '') ?>" list="firmalar">
                </div>

                <!-- Not -->
                <div class="col-12">
                    <label for="not_text" class="form-label">Not</label>
                    <textarea class="form-control" id="not_text" name="not_text" rows="3"><?= Helpers::e($t['not_text'] ?? '') ?></textarea>
                </div>

                <!-- PDF Upload -->
                <div class="col-12">
                    <label for="pdf" class="form-label">PDF Dosyası</label>
                    <?php if (!empty($t['pdf_path'])): ?>
                        <div class="mb-2">
                            <small class="text-muted">Mevcut: <?= Helpers::e($t['pdf_path']) ?></small>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf">
                    <small class="text-muted">Yeni PDF yüklemek opsiyoneldir</small>
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSave">
                    <i class="bi bi-save me-1"></i>Güncelle
                </button>
                <a href="/tutanak" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('tutanakForm');
        const miktarInput = document.getElementById('miktar');
        const birimFiyatInput = document.getElementById('birim_fiyat');
        const tutarInput = document.getElementById('tutar');

        // Calculate tutar when miktar or birim_fiyat changes
        function calculateTutar() {
            const miktar = parseFloat(miktarInput.value.replace(/\./g, '').replace(',', '.')) || 0;
            const birimFiyat = parseFloat(birimFiyatInput.value.replace(/\./g, '').replace(',', '.')) || 0;
            const tutar = miktar * birimFiyat;
            tutarInput.value = tutar.toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        miktarInput.addEventListener('input', calculateTutar);
        birimFiyatInput.addEventListener('input', calculateTutar);

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btnSave = document.getElementById('btnSave');
            btnSave.disabled = true;

            try {
                const formData = new FormData(form);

                const response = await fetch('/tutanak/update', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Tutanak başarıyla güncellendi');
                    window.location.href = '/tutanak';
                } else {
                    alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
                    btnSave.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert('Hata: ' + err.message);
                btnSave.disabled = false;
            }
        });
    });
</script>