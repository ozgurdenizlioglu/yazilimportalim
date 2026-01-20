<?php

use App\Core\Helpers;

$costcodesJson = json_encode($costcodes ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Maliyet Kodları') ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/costcodes/create"><i class="bi bi-plus-lg me-1"></i>Yeni Kayıt</a>
        <button class="btn btn-outline-secondary" type="button" id="toggleColumnPanel"><i class="bi bi-columns-gap me-1"></i>Kolonları Yönet</button>
        <button class="btn btn-outline-secondary" type="button" id="resetView"><i class="bi bi-arrow-counterclockwise me-1"></i>Görünümü Sıfırla</button>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excele Aktar</button>
        <button class="btn btn-outline-primary" type="button" id="downloadTemplate"><i class="bi bi-download me-1"></i>Şablon İndir</button>
        <button class="btn btn-outline-danger" type="button" id="removeDuplicates"><i class="bi bi-trash me-1"></i>Çiftleri Kaldır</button>
        <label class="btn btn-outline-secondary mb-0">
            <i class="bi bi-upload me-1"></i>Upload Et
            <input type="file" id="uploadFile" accept=".xlsx,.xls" hidden>
        </label>
    </div>
</div>

<?php if (!empty($costcodes)): ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-wrap p-2 pt-0">

                <div id="columnPanel" class="column-panel card card-body py-2 mb-2" hidden>
                    <div class="d-flex align-items-center justify-content-between">
                        <strong>Görünen Kolonlar</strong>
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label m-0 small text-muted" for="pageSizeSelect">Bir sayfada</label>
                            <select id="pageSizeSelect" class="form-select form-select-sm" style="width:auto;">
                                <option value="20">20 satır</option>
                                <option value="50">50 satır</option>
                                <option value="100">100 satır</option>
                                <option value="200">200 satır</option>
                                <option value="500">500 satır</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div id="columnCheckboxes" class="columns-grid"></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="costcodesTable">
                        <colgroup id="colGroup"></colgroup>
                        <thead id="tableHead">
                            <tr id="filtersRow"></tr>
                            <tr id="headerRow"></tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>

            </div><!-- table-wrap -->
        </div><!-- card-body -->

        <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
            <div class="text-muted small" id="footerStats">Toplam: <strong>0</strong> | Sayfa: 1/1</div>
            <div class="ms-auto"></div>
            <nav>
                <ul class="pagination mb-0" id="pager">
                    <li class="page-item"><a class="page-link" href="#" data-page="first">« İlk</a></li>
                    <li class="page-item"><a class="page-link" href="#" data-page="prev">‹ Önceki</a></li>
                    <li class="page-item disabled"><span class="page-link" id="pageIndicator">1 / 1</span></li>
                    <li class="page-item"><a class="page-link" href="#" data-page="next">Sonraki ›</a></li>
                    <li class="page-item"><a class="page-link" href="#" data-page="last">Son »</a></li>
                </ul>
            </nav>
        </div>
    </div><!-- card -->
<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>Henüz maliyet kodu bulunmamaktadır.
        <a href="/costcodes/create">Yeni kayıt ekleyin</a>
    </div>
<?php endif; ?>

<style>
    .columns-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(180px, 1fr));
        gap: .35rem .75rem;
    }

    @media (min-width: 576px) {
        .columns-grid {
            grid-template-columns: repeat(3, minmax(180px, 1fr));
        }
    }

    @media (min-width: 768px) {
        .columns-grid {
            grid-template-columns: repeat(4, minmax(180px, 1fr));
        }
    }

    @media (min-width: 992px) {
        .columns-grid {
            grid-template-columns: repeat(5, minmax(180px, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .columns-grid {
            grid-template-columns: repeat(6, minmax(180px, 1fr));
        }
    }

    #costcodesTable {
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0;
    }

    #costcodesTable thead {
        vertical-align: bottom;
    }

    #costcodesTable thead th {
        position: relative;
        background-clip: padding-box;
        white-space: nowrap;
    }

    #costcodesTable thead tr#filtersRow th {
        padding: .25rem .5rem;
        border-bottom: 0 !important;
    }

    #costcodesTable thead tr#headerRow th {
        padding-top: .25rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid var(--bs-border-color) !important;
        vertical-align: bottom;
    }

    #costcodesTable thead .filter-cell>* {
        display: block;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    #costcodesTable thead input.form-control-sm,
    #costcodesTable thead select.form-select-sm {
        min-height: 32px;
        line-height: 1.2;
    }

    #costcodesTable th.col-actions {
        padding-left: .25rem;
        padding-right: .25rem;
    }

    .btn-icon {
        --btn-size: 28px;
        width: var(--btn-size);
        height: var(--btn-size);
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .25rem;
    }

    .btn-icon.btn-light {
        border: 1px solid var(--bs-border-color);
    }

    .btn-icon i {
        font-size: 14px;
    }

    #costcodesTable td .btn-group {
        gap: 4px;
    }

    #costcodesTable td .btn-group .btn {
        border-width: 1px;
    }

    #costcodesTable thead th.sortable {
        cursor: pointer;
    }

    #costcodesTable thead th.sortable[data-sort="asc"]::after {
        content: " ↑";
        opacity: .6;
    }

    #costcodesTable thead th.sortable[data-sort="desc"]::after {
        content: " ↓";
        opacity: .6;
    }

    .truncate {
        max-width: 240px;
        display: inline-block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: bottom;
    }
</style>

<script>
    const costcodesData = <?= $costcodesJson ?>;

    function initCostCodesTable() {
        const DATA = costcodesData || [];

        const allFields = [{
                id: 'id',
                label: 'ID'
            },
            {
                id: 'level',
                label: 'Level'
            },
            {
                id: 'ust_baslik_veri',
                label: 'Üst Başlık / Veri'
            },
            {
                id: 'ortalama_gider',
                label: 'Ortalama Gider'
            },
            {
                id: 'cost_code',
                label: 'Cost Code'
            },
            {
                id: 'direct_indirect',
                label: 'Direct / Indirect'
            },
            {
                id: 'muhasebe_kodu_aciklama',
                label: 'Muhasebe Kodu Açıklama'
            },
            {
                id: 'cost_code_description',
                label: 'Cost Code Description'
            }
        ];

        const defaultVisibleFields = ['id', 'level', 'cost_code', 'direct_indirect', 'ortalama_gider'];
        const pageSize = parseInt(localStorage.getItem('costcodes_pageSize')) || 20;

        let visibleFields = JSON.parse(localStorage.getItem('costcodes_visibleFields')) || defaultVisibleFields;
        let currentPage = 1;
        let sortField = null;
        let sortDir = 'asc';
        let filters = {};

        function renderTable() {
            const headerRow = document.getElementById('headerRow');
            const filtersRow = document.getElementById('filtersRow');
            const tableBody = document.getElementById('tableBody');
            const colGroup = document.getElementById('colGroup');
            const footerStats = document.getElementById('footerStats');
            const pageIndicator = document.getElementById('pageIndicator');

            if (!headerRow || !filtersRow || !tableBody || !colGroup) {
                return;
            }

            headerRow.innerHTML = '';
            filtersRow.innerHTML = '';
            tableBody.innerHTML = '';
            colGroup.innerHTML = '';

            let filtered = DATA.filter(record => {
                for (let field in filters) {
                    const value = String(record[field] || '').toLowerCase();
                    const filter = String(filters[field] || '').toLowerCase();
                    if (!value.includes(filter)) return false;
                }
                return true;
            });

            if (sortField) {
                filtered.sort((a, b) => {
                    const aVal = a[sortField] || '';
                    const bVal = b[sortField] || '';
                    if (aVal < bVal) return sortDir === 'asc' ? -1 : 1;
                    if (aVal > bVal) return sortDir === 'asc' ? 1 : -1;
                    return 0;
                });
            }

            const total = filtered.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            const start = (currentPage - 1) * pageSize;
            const paged = filtered.slice(start, start + pageSize);

            visibleFields.forEach(fieldId => {
                const col = document.createElement('col');
                colGroup.appendChild(col);
            });

            visibleFields.forEach(fieldId => {
                const field = allFields.find(f => f.id === fieldId);
                const th = document.createElement('th');
                th.className = 'sortable';
                th.setAttribute('data-field', fieldId);
                if (sortField === fieldId) th.setAttribute('data-sort', sortDir);
                th.textContent = field ? field.label : fieldId;
                th.onclick = () => {
                    sortField = sortField === fieldId && sortDir === 'asc' ? fieldId : fieldId;
                    sortDir = sortField === fieldId && sortDir === 'asc' ? 'desc' : 'asc';
                    currentPage = 1;
                    renderTable();
                };
                headerRow.appendChild(th);
            });

            visibleFields.forEach(fieldId => {
                const th = document.createElement('th');
                th.className = 'filter-cell';
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.placeholder = 'Ara...';
                input.value = filters[fieldId] || '';
                input.onchange = () => {
                    filters[fieldId] = input.value;
                    currentPage = 1;
                    renderTable();
                };
                th.appendChild(input);
                filtersRow.appendChild(th);
            });

            paged.forEach(record => {
                const tr = document.createElement('tr');
                visibleFields.forEach(fieldId => {
                    const td = document.createElement('td');
                    const value = record[fieldId];

                    if (fieldId === 'ortalama_gider' && value) {
                        td.textContent = parseFloat(value).toLocaleString('tr-TR', {
                            minimumFractionDigits: 2
                        });
                        td.className = 'text-end';
                    } else {
                        td.textContent = value ? String(value).substring(0, 50) : '-';
                    }
                    tr.appendChild(td);
                });

                const actionsCell = document.createElement('td');
                actionsCell.innerHTML = `
                    <a href="/costcodes/edit?id=${record.id}" class="btn btn-sm btn-warning btn-icon" title="Düzenle">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button class="btn btn-sm btn-danger btn-icon" onclick="deleteRecord(${record.id})" title="Sil">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                tr.appendChild(actionsCell);
                tableBody.appendChild(tr);
            });

            footerStats.innerHTML = `Toplam: <strong>${total}</strong> | Sayfa: ${currentPage}/${totalPages}`;
            pageIndicator.textContent = `${currentPage} / ${totalPages}`;

            document.querySelectorAll('#pager a').forEach(link => {
                link.onclick = (e) => {
                    e.preventDefault();
                    const page = link.dataset.page;
                    if (page === 'first') currentPage = 1;
                    else if (page === 'prev') currentPage = Math.max(1, currentPage - 1);
                    else if (page === 'next') currentPage = Math.min(totalPages, currentPage + 1);
                    else if (page === 'last') currentPage = totalPages;
                    renderTable();
                };
            });
        }

        const columnPanel = document.getElementById('columnPanel');
        const toggleButton = document.getElementById('toggleColumnPanel');
        const columnCheckboxes = document.getElementById('columnCheckboxes');
        const pageSizeSelect = document.getElementById('pageSizeSelect');
        const resetButton = document.getElementById('resetView');

        if (!columnPanel || !toggleButton || !columnCheckboxes || !pageSizeSelect || !resetButton) {
            return;
        }

        toggleButton.onclick = () => {
            columnPanel.hidden = !columnPanel.hidden;
        };

        pageSizeSelect.value = pageSize;
        pageSizeSelect.onchange = () => {
            localStorage.setItem('costcodes_pageSize', pageSizeSelect.value);
            currentPage = 1;
            renderTable();
        };

        resetButton.onclick = () => {
            localStorage.removeItem('costcodes_visibleFields');
            visibleFields = defaultVisibleFields;
            filters = {};
            sortField = null;
            renderTable();
            renderColumnCheckboxes();
        };

        function renderColumnCheckboxes() {
            columnCheckboxes.innerHTML = '';
            allFields.forEach(field => {
                const label = document.createElement('label');
                label.className = 'form-check-label';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input';
                checkbox.checked = visibleFields.includes(field.id);
                checkbox.onchange = () => {
                    if (checkbox.checked) {
                        visibleFields.push(field.id);
                    } else {
                        visibleFields = visibleFields.filter(f => f !== field.id);
                    }
                    localStorage.setItem('costcodes_visibleFields', JSON.stringify(visibleFields));
                    renderTable();
                };
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(' ' + field.label));
                columnCheckboxes.appendChild(label);
            });
        }

        renderColumnCheckboxes();
        renderTable();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCostCodesTable);
    } else {
        initCostCodesTable();
    }

    function deleteRecord(id) {
        if (confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/costcodes/delete';
            form.innerHTML = '<input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.getElementById('downloadTemplate')?.addEventListener('click', () => {
        const headers = ['LEVEL', 'UST BASLIK / VERI', 'ORTALAMA GIDER', 'COST CODE', 'DIRECT/INDIRECT', 'MUHASEBE KODU ACIKLAMA', 'COST CODE DESCRIPTION'];
        const ws = XLSX.utils.aoa_to_sheet([headers]);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Codes');
        XLSX.writeFile(wb, 'costcodes_template.xlsx');
    });

    document.getElementById('removeDuplicates')?.addEventListener('click', () => {
        if (!confirm('Çiftli maliyet kodlarını kaldırmak istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
            return;
        }

        const btn = document.getElementById('removeDuplicates');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> İşleniyor...';

        fetch('/costcodes/remove-duplicates', {
                method: 'POST'
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if (data.success) {
                    const result = data.result;
                    const message = `Çiftler Kaldırıldı!\n\n` +
                        `Öncesi: ${result.before_total} kayıt (${result.before_unique} benzersiz kod)\n` +
                        `Sonrası: ${result.after_total} kayıt (${result.after_unique} benzersiz kod)\n` +
                        `Silinen: ${result.deleted} çiftli kayıt\n\n` +
                        `Sayfa yeniden yükleniyor...`;
                    alert(message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert('Hata: ' + err.message);
                console.error(err);
            });
    });

    document.getElementById('exportExcel')?.addEventListener('click', () => {
        const now = new Date();
        const dateStr = now.getFullYear() + '' + String(now.getMonth() + 1).padStart(2, '0') + '' + String(now.getDate()).padStart(2, '0');
        const headers = ['LEVEL', 'UST BASLIK / VERI', 'ORTALAMA GIDER', 'COST CODE', 'DIRECT/INDIRECT', 'MUHASEBE KODU ACIKLAMA', 'COST CODE DESCRIPTION'];
        const data = [headers, ...costcodesData.map(r => [r.level, r.ust_baslik_veri, r.ortalama_gider, r.cost_code, r.direct_indirect, r.muhasebe_kodu_aciklama, r.cost_code_description])];
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Codes');
        XLSX.writeFile(wb, 'costcodes_export_' + dateStr + '.xlsx');
    });

    document.getElementById('uploadFile')?.addEventListener('change', async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (typeof XLSX === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
            script.onload = () => processUpload(file);
            document.head.appendChild(script);
        } else {
            processUpload(file);
        }
    });

    function processUpload(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {
                    type: 'array'
                });
                const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1
                });

                if (rows.length < 2) {
                    alert('Boş dosya veya geçersiz format');
                    return;
                }

                const headers = rows[0];
                const dataRows = rows.slice(1);

                const colMap = {};
                headers.forEach((h, idx) => {
                    const normalized = (h || '').toString().toLowerCase().trim();
                    if (normalized.includes('level')) colMap[idx] = 'level';
                    else if (normalized.includes('ust baslik')) colMap[idx] = 'ust_baslik_veri';
                    else if (normalized.includes('ortalama')) colMap[idx] = 'ortalama_gider';
                    else if (normalized.includes('cost code') && !normalized.includes('description')) colMap[idx] = 'cost_code';
                    else if (normalized.includes('direct')) colMap[idx] = 'direct_indirect';
                    else if (normalized.includes('muhasebe')) colMap[idx] = 'muhasebe_kodu_aciklama';
                    else if (normalized.includes('description')) colMap[idx] = 'cost_code_description';
                    // If column wasn't matched and looks like it could be cost codes, mark it
                    else if (!colMap[idx] && (normalized.includes('kod') || normalized.includes('code') || normalized === 'xxx' || /^\d+-\d+/.test(String(h)))) {
                        colMap[idx] = 'cost_code';
                    }
                });

                let inserted = 0;
                let skipped = 0;
                let rowsProcessed = 0;
                let rowsTotal = 0;

                // First pass: count rows to process
                dataRows.forEach(row => {
                    const payload = {};
                    Object.entries(colMap).forEach(([idx, field]) => {
                        payload[field] = row[idx] || null;
                    });
                    if (Object.keys(payload).some(k => payload[k])) {
                        rowsTotal++;
                    }
                });

                if (rowsTotal === 0) {
                    alert('Yüklenecek veri bulunamadı.');
                    return;
                }

                // Show processing message
                const processingMsg = document.createElement('div');
                processingMsg.className = 'alert alert-info mt-2';
                processingMsg.innerHTML = '<i class="bi bi-hourglass-split"></i> İşleniyor: 0/' + rowsTotal + ' satır yüklendi...';
                processingMsg.id = 'uploadProgress';
                document.querySelector('.d-flex.flex-wrap.gap-2.justify-content-between')?.parentElement?.appendChild(processingMsg);

                // Second pass: upload rows
                dataRows.forEach(row => {
                    const payload = {};
                    Object.entries(colMap).forEach(([idx, field]) => {
                        payload[field] = row[idx] || null;
                    });

                    if (Object.keys(payload).some(k => payload[k])) {
                        fetch('/costcodes/bulk-upload', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'payload=' + encodeURIComponent(JSON.stringify({
                                rows: [headers, row]
                            }))
                        }).then(r => r.json()).then(d => {
                            inserted += d.inserted || 0;
                            skipped += d.skipped || 0;
                            rowsProcessed++;
                            const progressEl = document.getElementById('uploadProgress');
                            if (progressEl) {
                                progressEl.innerHTML = '<i class="bi bi-hourglass-split"></i> İşleniyor: ' + rowsProcessed + '/' + rowsTotal + ' satır yüklendi...';
                            }
                            if (rowsProcessed === rowsTotal) {
                                const progressEl = document.getElementById('uploadProgress');
                                if (progressEl) progressEl.remove();
                                let message = '✓ Yükleme Tamamlandı!\n\n' + inserted + ' kayıt başarıyla eklendi.';
                                if (skipped > 0) {
                                    message += '\n' + skipped + ' kayıt zaten var (atlandı).';
                                }
                                message += '\n\nSayfa yeniden yükleniyor...';
                                alert(message);
                                setTimeout(() => window.location.reload(), 1000);
                            }
                        }).catch(err => {
                            console.error('Upload error:', err);
                            rowsProcessed++;
                            if (rowsProcessed === rowsTotal) {
                                const progressEl = document.getElementById('uploadProgress');
                                if (progressEl) progressEl.remove();
                                if (inserted > 0) {
                                    let message = '⚠ Yükleme Tamamlandı (Hatalı)!\n\n' + inserted + ' kayıt eklendi.';
                                    if (skipped > 0) {
                                        message += '\n' + skipped + ' kayıt zaten var (atlandı).';
                                    }
                                    message += '\n\nSayfa yeniden yükleniyor...';
                                    alert(message);
                                    setTimeout(() => window.location.reload(), 1000);
                                } else {
                                    alert('✗ Yükleme Başarısız!\n\nLütfen dosya formatını kontrol edin.');
                                }
                            }
                        });
                    }
                });
            } catch (err) {
                alert('Dosya okunamadı: ' + err.message);
            }
        };
        reader.readAsArrayBuffer(file);
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>