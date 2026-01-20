<?php

use App\Core\Helpers;

?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Yeni Tutanak') ?></h1>
    <a href="/tutanak" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form id="tutanakForm" enctype="multipart/form-data">
            <div class="row g-3">

                <!-- Project Selection -->
                <div class="col-md-6">
                    <label for="project_id" class="form-label">Proje <span class="text-danger">*</span></label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Proje Seçin</option>
                        <?php foreach ($projects ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= Helpers::e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tutanak No -->
                <div class="col-md-6">
                    <label for="tutanak_no" class="form-label">Tutanak No</label>
                    <input type="text" class="form-control" id="tutanak_no" name="tutanak_no" placeholder="Örn: 001">
                </div>

                <!-- Tarih -->
                <div class="col-md-4">
                    <label for="tarih" class="form-label">Tarih <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tarih" name="tarih" value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Konu -->
                <div class="col-md-8">
                    <label for="konu" class="form-label">Konu</label>
                    <input type="text" class="form-control" id="konu" name="konu" placeholder="Tutanak konusu">
                </div>

                <!-- Ödeme Yapılacak Firma -->
                <div class="col-md-6">
                    <label for="odeme_yapilacak_firma" class="form-label">Ödeme Yapılacak Firma</label>
                    <input type="text" class="form-control" id="odeme_yapilacak_firma" name="odeme_yapilacak_firma" list="firmalar">
                    <datalist id="firmalar">
                        <?php foreach ($companies ?? [] as $c): ?>
                            <option value="<?= Helpers::e($c['name']) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- Kesinti Yapılacak Firma -->
                <div class="col-md-6">
                    <label for="kesinti_yapilacak_firma" class="form-label">Kesinti Yapılacak Firma</label>
                    <input type="text" class="form-control" id="kesinti_yapilacak_firma" name="kesinti_yapilacak_firma" list="firmalar">
                </div>

                <!-- Total Amount Display -->
                <div class="col-12">
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <span style="font-size: 1.1rem;"><strong>TOPLAM TUTAR:</strong></span>
                        <span style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">
                            <span id="totalAmount">0,00</span> ₺
                        </span>
                    </div>
                </div>

                <!-- Line Items Section -->
                <div class="col-12">
                    <h5 class="mb-3">Tutanak Satırları</h5>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Tür</th>
                                    <th>Malzeme/Yevmiye/Ceza</th>
                                    <th>Birim</th>
                                    <th>Miktar</th>
                                    <th>Birim Fiyat (₺)</th>
                                    <th>Tutar (₺)</th>
                                    <th class="text-center" style="width: 60px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Line items will be added here -->
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-success" id="btnAddRow">
                        <i class="bi bi-plus-lg me-1"></i>Satır Ekle
                    </button>
                </div>

                <!-- Not -->
                <div class="col-12">
                    <label for="not_text" class="form-label">Not</label>
                    <textarea class="form-control" id="not_text" name="not_text" rows="2" placeholder="Ek açıklamalar"></textarea>
                </div>

                <!-- PDF Upload -->
                <div class="col-12">
                    <label for="pdf" class="form-label">PDF Dosyası</label>
                    <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf">
                    <small class="text-muted">PDF yüklemek opsiyoneldir</small>
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSave">
                    <i class="bi bi-save me-1"></i>Kaydet
                </button>
                <a href="/tutanak" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>

<!-- Hidden template for line items -->
<template id="rowTemplate">
    <tr class="line-item-row">
        <td>
            <select class="form-select form-select-sm tur-select" name="items[TUR][]">
                <option value="">Seçin</option>
                <option value="MALZEME">Malzeme</option>
                <option value="YEVMİYE">Yevmiye</option>
                <option value="CEZA">Ceza</option>
                <option value="DİĞER">Diğer</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm malzeme-input" name="items[MALZEME][]" placeholder="Malzeme adı">
        </td>
        <td>
            <select class="form-select form-select-sm birim-select" name="items[BIRIM_ID][]">
                <option value="">Seçin</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm miktar-input" name="items[MIKTAR][]" value="0,00" pattern="[0-9.,]+">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm birim-fiyat-input" name="items[BIRIM_FIYAT][]" value="0,00" pattern="[0-9.,]+">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bg-light tutar-output" name="items[TUTAR][]" value="0,00" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger btn-delete-row" title="Satırı Sil">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
    // Store units data as JavaScript array
    const unitsData = <?= json_encode($units ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const currentLanguage = '<?= currentLanguage() ?>';

    document.addEventListener('DOMContentLoaded', () => {
        try {
            const form = document.getElementById('tutanakForm');
            const btnAddRow = document.getElementById('btnAddRow');
            const itemsTableBody = document.getElementById('itemsTableBody');
            const rowTemplate = document.getElementById('rowTemplate');
            const totalAmountSpan = document.getElementById('totalAmount');

            // Helper function to populate birim select options based on current language
            function populateBirimSelect(selectElement) {
                selectElement.innerHTML = '<option value="">Seçin</option>';
                if (!unitsData || unitsData.length === 0) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Veri Yok';
                    selectElement.appendChild(option);
                    return;
                }

                unitsData.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.id || '';
                    // Column names are lowercase in Postgres; also support uppercase just in case
                    const trName = unit.tr || unit.TR || '';
                    const enName = unit.en || unit.EN || '';
                    const displayText = currentLanguage === 'en' ? enName : trName;
                    option.textContent = displayText;
                    selectElement.appendChild(option);
                });
            }

            // Parse numeric input (Turkish format)
            function parseNumeric(value) {
                const sanitized = value.replace(/\./g, '').replace(',', '.');
                return parseFloat(sanitized) || 0;
            }

            // Format numeric output (Turkish format)
            function formatNumeric(value) {
                return value.toLocaleString('tr-TR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // Add a new row
            function addRow() {
                const clone = rowTemplate.content.cloneNode(true);
                itemsTableBody.appendChild(clone);

                // Add event listeners to the new row
                const newRow = itemsTableBody.lastElementChild;

                // Populate birim select in new row
                const birimSelect = newRow.querySelector('.birim-select');
                populateBirimSelect(birimSelect);

                attachRowEventListeners(newRow);
            }

            // Attach event listeners to a row
            function attachRowEventListeners(row) {
                const miktarInput = row.querySelector('.miktar-input');
                const birimFiyatInput = row.querySelector('.birim-fiyat-input');
                const tutarOutput = row.querySelector('.tutar-output');
                const deleteBtn = row.querySelector('.btn-delete-row');

                function calculateRowTotal() {
                    const miktar = parseNumeric(miktarInput.value);
                    const birimFiyat = parseNumeric(birimFiyatInput.value);
                    const tutar = miktar * birimFiyat;
                    tutarOutput.value = formatNumeric(tutar);
                    calculateTotalAmount();
                }

                miktarInput.addEventListener('input', calculateRowTotal);
                birimFiyatInput.addEventListener('input', calculateRowTotal);

                deleteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    row.remove();
                    calculateTotalAmount();
                });
            }

            // Calculate total amount
            function calculateTotalAmount() {
                const rows = itemsTableBody.querySelectorAll('.line-item-row');
                let total = 0;
                rows.forEach(row => {
                    const tutarOutput = row.querySelector('.tutar-output');
                    total += parseNumeric(tutarOutput.value);
                });
                totalAmountSpan.textContent = formatNumeric(total);
            }

            // Add button event
            btnAddRow.addEventListener('click', (e) => {
                e.preventDefault();
                addRow();
            });

            // Add initial row
            addRow();

            // Form submission
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Validate that at least one row exists
                const rows = itemsTableBody.querySelectorAll('.line-item-row');
                if (rows.length === 0) {
                    alert('Lütfen en az bir satır ekleyin');
                    return;
                }

                // Collect all items data
                const items = [];
                rows.forEach(row => {
                    items.push({
                        tur: row.querySelector('.tur-select').value,
                        malzeme: row.querySelector('.malzeme-input').value,
                        birim_id: row.querySelector('.birim-select').value,
                        miktar: row.querySelector('.miktar-input').value,
                        birim_fiyat: row.querySelector('.birim-fiyat-input').value,
                        tutar: row.querySelector('.tutar-output').value
                    });
                });

                const btnSave = document.getElementById('btnSave');
                btnSave.disabled = true;

                try {
                    const formData = new FormData(form);
                    formData.append('items_json', JSON.stringify(items));

                    const response = await fetch('/tutanak/store', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Tutanak başarıyla kaydedildi: ' + result.tutanak_title);
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
        } catch (error) {
            console.error('Error initializing form:', error);
        }
    });
</script>