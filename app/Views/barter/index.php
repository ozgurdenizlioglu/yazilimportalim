<?php

use App\Core\Helpers;

$bartersJson = json_encode($barters ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Barter') ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/barter/create"><i class="bi bi-plus-lg me-1"></i>Yeni Kayıt</a>
        <button class="btn btn-outline-secondary" type="button" id="toggleColumnPanel"><i class="bi bi-columns-gap me-1"></i>Kolonları Yönet</button>
        <button class="btn btn-outline-secondary" type="button" id="resetView"><i class="bi bi-arrow-counterclockwise me-1"></i>Görünümü Sıfırla</button>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excele Aktar</button>
        <button class="btn btn-outline-primary" type="button" id="downloadTemplate"><i class="bi bi-download me-1"></i>Şablon İndir</button>
        <label class="btn btn-outline-secondary mb-0">
            <i class="bi bi-upload me-1"></i>Upload Et
            <input type="file" id="uploadFile" accept=".xlsx,.xls" hidden>
        </label>
    </div>
</div>

<?php if (!empty($barters)): ?>
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
                    <table class="table table-hover align-middle mb-0" id="barterTable">
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
        <i class="bi bi-info-circle me-2"></i>Henüz barter kaydı bulunmamaktadır.
        <a href="/barter/create">Yeni kayıt ekleyin</a>
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

    #barterTable {
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0;
    }

    #barterTable thead {
        vertical-align: bottom;
    }

    #barterTable thead th {
        position: relative;
        background-clip: padding-box;
        white-space: nowrap;
    }

    #barterTable thead tr#filtersRow th {
        padding: .25rem .5rem;
        border-bottom: 0 !important;
    }

    #barterTable thead tr#headerRow th {
        padding-top: .25rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid var(--bs-border-color) !important;
        vertical-align: bottom;
    }

    #barterTable thead .filter-cell>* {
        display: block;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    #barterTable thead input.form-control-sm,
    #barterTable thead select.form-select-sm {
        min-height: 32px;
        line-height: 1.2;
    }

    #barterTable th.col-actions {
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

    #barterTable td .btn-group {
        gap: 4px;
    }

    #barterTable td .btn-group .btn {
        border-width: 1px;
    }

    #barterTable thead th.sortable {
        cursor: pointer;
    }

    #barterTable thead th.sortable[data-sort="asc"]::after {
        content: " ↑";
        opacity: .6;
    }

    #barterTable thead th.sortable[data-sort="desc"]::after {
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

    .upload-preview {
        font-size: 0.875rem;
    }

    .upload-preview table {
        margin-bottom: 0;
    }

    .upload-preview th {
        font-weight: 600;
        background-color: #f8f9fa;
        white-space: nowrap;
    }

    .upload-preview td {
        padding: 0.5rem;
        vertical-align: middle;
    }
</style>

<script>
    const bartersData = <?= $bartersJson ?>;

    function initBarterTable() {
        window.DATA = bartersData || [];

        const allFields = [{
                id: 'id',
                label: 'ID'
            },
            {
                id: 'proje',
                label: 'Proje'
            },
            {
                id: 'cost_code',
                label: 'Cost Code'
            },
            {
                id: 'aciklama',
                label: 'Açıklama'
            },
            {
                id: 'barter_tutari',
                label: 'Barter Tutarı'
            },
            {
                id: 'barter_currency',
                label: 'Barter Currency'
            },
            {
                id: 'barter_gerceklesen',
                label: 'Barter Gerçekleşen'
            },
            {
                id: 'barter_planlanan_oran',
                label: 'Barter - Planlanan Oran'
            },
            {
                id: 'barter_planlanan_tutar',
                label: 'Barter - Planlanan Tutar'
            },
            {
                id: 'sozlesme_tarihi',
                label: 'Sözleşme Tarihi'
            },
            {
                id: 'kur',
                label: 'Kur'
            },
            {
                id: 'usd_karsiligi',
                label: 'USD Karşılığı'
            },
            {
                id: 'tutar_try',
                label: 'Tutar TRY'
            },
            {
                id: 'not_field',
                label: 'Not'
            },
            {
                id: 'path',
                label: 'Path'
            },
            {
                id: 'yuklenici',
                label: 'Yüklenici'
            },
            {
                id: 'karsi_hesap_ismi',
                label: 'Karşı Hesap İsmi'
            }
        ];

        const defaultVisibleFields = ['id', 'proje', 'cost_code', 'barter_tutari', 'barter_currency', 'barter_gerceklesen', 'tutar_try'];
        const pageSize = parseInt(localStorage.getItem('barter_pageSize')) || 20;

        let visibleFields = JSON.parse(localStorage.getItem('barter_visibleFields')) || defaultVisibleFields;
        let currentPage = 1;
        let sortField = null;
        let sortDir = 'asc';
        window.filters = {};

        window.renderTable = function renderTable() {
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

            let filtered = window.DATA.filter(record => {
                for (let field in window.filters) {
                    const dateFields = ['sozlesme_tarihi'];
                    if (dateFields.includes(field)) {
                        const filterObj = window.filters[field];
                        if (!filterObj || (!filterObj.start_date && !filterObj.end_date)) continue;
                        const recordDate = String(record[field] || '').split('T')[0];
                        const mode = filterObj.mode || 'interval';
                        const startDate = filterObj.start_date;
                        const endDate = filterObj.end_date;

                        console.log(`Filtering field: ${field}, mode: ${mode}, recordDate: ${recordDate}, startDate: ${startDate}, endDate: ${endDate}`);

                        if (mode === 'after') {
                            if (startDate && recordDate < startDate) {
                                console.log(`  AFTER: ${recordDate} < ${startDate} => excluding`);
                                return false;
                            }
                        } else if (mode === 'up_to') {
                            if (endDate && recordDate > endDate) {
                                console.log(`  UP_TO: ${recordDate} > ${endDate} => excluding`);
                                return false;
                            }
                        } else {
                            if (startDate && recordDate < startDate) {
                                console.log(`  INTERVAL: ${recordDate} < ${startDate} => excluding`);
                                return false;
                            }
                            if (endDate && recordDate > endDate) {
                                console.log(`  INTERVAL: ${recordDate} > ${endDate} => excluding`);
                                return false;
                            }
                        }
                        continue;
                    } else {
                        const filterValue = String(window.filters[field] || '').toLowerCase();
                        if (!filterValue) continue;
                        const value = String(record[field] || '').toLowerCase();
                        if (!value.includes(filterValue)) return false;
                    }
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

                const dateFields = ['sozlesme_tarihi'];
                if (dateFields.includes(fieldId)) {
                    // Flatpickr date range filter with three modes
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'date-filter-input form-control form-control-sm';
                    input.placeholder = 'YYYY-MM-DD or YYYY-MM-DD to YYYY-MM-DD';
                    input.title = 'Pick dates or type in format: YYYY-MM-DD or YYYY-MM-DD to YYYY-MM-DD';

                    // Initialize filter object
                    if (!window.filters[fieldId]) {
                        window.filters[fieldId] = {
                            mode: 'interval',
                            start_date: null,
                            end_date: null
                        };
                    }

                    th.appendChild(input);

                    // Initialize Flatpickr after element is in DOM
                    setTimeout(() => {
                        const fp = initDateFilterPicker(input, fieldId, window.filters[fieldId]);
                        // Trigger table update on change
                        const originalOnChange = fp.onChange;
                        fp.onChange = function() {
                            currentPage = 1;
                            renderTable();
                            if (originalOnChange) originalOnChange.apply(this, arguments);
                        };
                    }, 0);
                } else {
                    // Text filter input
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.placeholder = 'Ara...';
                    input.className = 'form-control form-control-sm';
                    input.value = window.filters[fieldId] || '';
                    input.onchange = () => {
                        window.filters[fieldId] = input.value;
                        currentPage = 1;
                        renderTable();
                    };
                    th.appendChild(input);
                }

                filtersRow.appendChild(th);
            });

            paged.forEach(record => {
                const tr = document.createElement('tr');
                visibleFields.forEach(fieldId => {
                    const td = document.createElement('td');
                    const value = record[fieldId];

                    if (['barter_tutari', 'barter_gerceklesen', 'barter_planlanan_tutar', 'kur', 'usd_karsiligi', 'tutar_try'].includes(fieldId) && value) {
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
                    <a href="/barter/edit?id=${record.id}" class="btn btn-sm btn-warning btn-icon" title="Düzenle">
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

            if (window.updateTotals_barterTable) {
                window.updateTotals_barterTable([]);
            }
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
            localStorage.setItem('barter_pageSize', pageSizeSelect.value);
            currentPage = 1;
            renderTable();
        };

        resetButton.onclick = () => {
            localStorage.removeItem('barter_visibleFields');
            visibleFields = defaultVisibleFields;
            window.filters = {};
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
                    localStorage.setItem('barter_visibleFields', JSON.stringify(visibleFields));
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

    function initializeBarterTotals() {
        initTableTotals({
            tableId: 'barterTable',
            fieldsToSum: ['barter_tutari', 'barter_gerceklesen', 'barter_planlanan_tutar', 'usd_karsiligi', 'tutar_try'],
            dateFields: ['sozlesme_tarihi'],
            formatters: {
                barter_tutari: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                barter_gerceklesen: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                barter_planlanan_tutar: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                usd_karsiligi: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                tutar_try: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                })
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize totals FIRST so the update function exists before rendering table
            initializeBarterTotals();
            // Then initialize the table (which will call updateTotals when rendering)
            initBarterTable();
        });
    } else {
        // Initialize totals FIRST so the update function exists before rendering table
        initializeBarterTotals();
        // Then initialize the table (which will call updateTotals when rendering)
        initBarterTable();
    }

    function deleteRecord(id) {
        if (confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/barter/delete';
            form.innerHTML = '<input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.getElementById('downloadTemplate')?.addEventListener('click', () => {
        const headers = ['PROJE', 'COST CODE', 'ACIKLAMA', 'BARTER TUTARI', 'BARTER CURRENCY', 'BARTER GERCEKLESEN', 'BARTER - PLANLANAN ORAN', 'BARTER - PLANLANAN TUTAR', 'SOZLESME TARIHI', 'KUR', 'USD KARSILIGI', 'TUTAR_TRY', 'NOT', 'PATH', 'YUKLENICI', 'KARSI HESAP ISMI'];
        const ws = XLSX.utils.aoa_to_sheet([headers]);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Barter');
        XLSX.writeFile(wb, 'barter_template.xlsx');
    });

    document.getElementById('exportExcel')?.addEventListener('click', () => {
        const now = new Date();
        const dateStr = now.getFullYear() + '' + String(now.getMonth() + 1).padStart(2, '0') + '' + String(now.getDate()).padStart(2, '0');
        const headers = ['PROJE', 'COST CODE', 'ACIKLAMA', 'BARTER TUTARI', 'BARTER CURRENCY', 'BARTER GERCEKLESEN', 'BARTER - PLANLANAN ORAN', 'BARTER - PLANLANAN TUTAR', 'SOZLESME TARIHI', 'KUR', 'USD KARSILIGI', 'TUTAR_TRY', 'NOT', 'PATH', 'YUKLENICI', 'KARSI HESAP ISMI'];
        const data = [headers, ...bartersData.map(r => [r.proje, r.cost_code, r.aciklama, r.barter_tutari, r.barter_currency, r.barter_gerceklesen, r.barter_planlanan_oran, r.barter_planlanan_tutar, r.sozlesme_tarihi, r.kur, r.usd_karsiligi, r.tutar_try, r.not_field, r.path, r.yuklenici, r.karsi_hesap_ismi])];
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Barter');
        XLSX.writeFile(wb, 'barter_export_' + dateStr + '.xlsx');
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
                    if (normalized.includes('proje')) colMap[idx] = 'proje';
                    else if (normalized.includes('cost')) colMap[idx] = 'cost_code';
                    else if (normalized.includes('aciklama')) colMap[idx] = 'aciklama';
                    else if (normalized.includes('barter tutari')) colMap[idx] = 'barter_tutari';
                    else if (normalized.includes('currency')) colMap[idx] = 'barter_currency';
                    else if (normalized.includes('gerceklesen')) colMap[idx] = 'barter_gerceklesen';
                    else if (normalized.includes('oran')) colMap[idx] = 'barter_planlanan_oran';
                    else if (normalized.includes('planlanan tutar')) colMap[idx] = 'barter_planlanan_tutar';
                    else if (normalized.includes('tarihi')) colMap[idx] = 'sozlesme_tarihi';
                    else if (normalized === 'kur') colMap[idx] = 'kur';
                    else if (normalized.includes('usd')) colMap[idx] = 'usd_karsiligi';
                    else if (normalized.includes('tutar')) colMap[idx] = 'tutar_try';
                    else if (normalized === 'not') colMap[idx] = 'not_field';
                    else if (normalized === 'path') colMap[idx] = 'path';
                    else if (normalized.includes('yuklenici')) colMap[idx] = 'yuklenici';
                    else if (normalized.includes('karsi')) colMap[idx] = 'karsi_hesap_ismi';
                });

                let inserted = 0;
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
                        fetch('/barter/bulk-upload', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'payload=' + encodeURIComponent(JSON.stringify({
                                rows: [headers, row]
                            }))
                        }).then(r => r.json()).then(d => {
                            inserted += d.inserted || 0;
                            rowsProcessed++;
                            const progressEl = document.getElementById('uploadProgress');
                            if (progressEl) {
                                progressEl.innerHTML = '<i class="bi bi-hourglass-split"></i> İşleniyor: ' + rowsProcessed + '/' + rowsTotal + ' satır yüklendi...';
                            }
                            if (rowsProcessed === rowsTotal) {
                                const progressEl = document.getElementById('uploadProgress');
                                if (progressEl) progressEl.remove();
                                alert('✓ Yükleme Tamamlandı!\n\n' + inserted + ' kayıt başarıyla eklendi.\n\nSayfa yeniden yükleniyor...');
                                setTimeout(() => window.location.reload(), 1000);
                            }
                        }).catch(err => {
                            console.error('Upload error:', err);
                            rowsProcessed++;
                            if (rowsProcessed === rowsTotal) {
                                const progressEl = document.getElementById('uploadProgress');
                                if (progressEl) progressEl.remove();
                                if (inserted > 0) {
                                    alert('⚠ Yükleme Tamamlandı (Hatalı)!\n\n' + inserted + ' kayıt eklendi.\n\nSayfa yeniden yükleniyor...');
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

<script src="/js/table-totals.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/flatpickr-date-filter.js"></script>

<style>
    .flatpickr-calendar {
        border: 2px solid #1f2937;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        background: #fff;
    }

    .flatpickr-monthDropdown-months,
    .flatpickr-current-month,
    .flatpickr-rContainer {
        background: #fff;
        color: #000;
    }

    .flatpickr-weekday {
        color: #000;
        font-weight: 700;
        background: #f3f4f6;
    }

    .flatpickr-day {
        border-radius: 6px;
        color: #000;
        font-weight: 500;
        background: #fff;
    }

    .flatpickr-day.inRange {
        background: #dbeafe;
        color: #000;
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: #3b82f6;
        border: 2px solid #1e40af;
        color: #fff;
        font-weight: 700;
    }

    .flatpickr-day:hover {
        background: #e5e7eb;
        color: #000;
    }

    .flatpickr-day.nextMonthDay,
    .flatpickr-day.prevMonthDay {
        color: #d1d5db;
    }

    .fp-mode-wrapper {
        margin-bottom: 8px;
        display: flex;
        gap: 8px;
        padding: 8px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }

    .fp-mode-select {
        background: #1f2937;
        color: #fff;
        border: 2px solid #374151;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        min-height: 36px;
    }

    .fp-mode-select:hover {
        border-color: #4b5563;
        background: #2d3748;
    }

    .fp-mode-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .fp-mode-select option {
        background: #1f2937;
        color: #fff;
        padding: 8px;
    }

    .date-filter-input {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #1f2937;
        border-radius: 6px;
        font-size: 14px;
        background: #fff;
        color: #000;
        outline: none;
        font-weight: 600;
    }

    .date-filter-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .date-filter-input::placeholder {
        color: #6b7280;
    }
</style>