<?php

use App\Core\Helpers;

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Tevkifat') ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/tevkifat/create"><i class="bi bi-plus-lg me-1"></i>Yeni Kayıt</a>
        <button class="btn btn-outline-secondary" type="button" id="toggleColumnPanel"><i class="bi bi-columns-gap me-1"></i>Kolonları Yönet</button>
        <button class="btn btn-outline-secondary" type="button" id="resetView"><i class="bi bi-arrow-counterclockwise me-1"></i>Görünümü Sıfırla</button>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excele Aktar</button>
        <button class="btn btn-outline-primary" type="button" id="downloadTemplate"><i class="bi bi-download me-1"></i>Şablon İndir</button>
        <label class="btn btn-outline-secondary mb-0">
            <i class="bi bi-upload me-1"></i>Upload Et
            <input type="file" id="uploadFile" accept=".xlsx,.xls,.csv" hidden>
        </label>
    </div>
</div>

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
                <table class="table table-hover align-middle mb-0" id="tevkifatTable">
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

    #tevkifatTable {
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0;
    }

    #tevkifatTable thead {
        vertical-align: bottom;
    }

    #tevkifatTable thead th {
        position: relative;
        background-clip: padding-box;
        white-space: nowrap;
    }

    #tevkifatTable thead tr#filtersRow th {
        padding: .25rem .5rem;
        border-bottom: 0 !important;
    }

    #tevkifatTable thead tr#headerRow th {
        padding-top: .25rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid var(--bs-border-color) !important;
        vertical-align: bottom;
    }

    #tevkifatTable thead .filter-cell>* {
        display: block;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    #tevkifatTable thead input.form-control-sm,
    #tevkifatTable thead select.form-select-sm {
        min-height: 32px;
        line-height: 1.2;
    }

    #tevkifatTable th.col-actions {
        padding-left: .25rem;
        padding-right: .25rem;
    }

    #tevkifatTable th .col-resizer {
        position: absolute;
        top: 0;
        right: 0;
        width: 10px;
        height: 100%;
        cursor: col-resize;
        user-select: none;
        -webkit-user-select: none;
    }

    #tevkifatTable th.resizing,
    #tevkifatTable th .col-resizer.active {
        background-image: linear-gradient(to bottom, rgba(45, 108, 223, .15), rgba(45, 108, 223, .15));
        background-repeat: no-repeat;
        background-position: right center;
        background-size: 2px 100%;
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

    #tevkifatTable td .btn-group {
        gap: 4px;
    }

    #tevkifatTable td .btn-group .btn {
        border-width: 1px;
    }

    #tevkifatTable thead th.sortable {
        cursor: pointer;
    }

    #tevkifatTable thead th.sortable[data-sort="asc"]::after {
        content: " ↑";
        opacity: .6;
    }

    #tevkifatTable thead th.sortable[data-sort="desc"]::after {
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

    .table-wrap {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        overflow-x: auto;
    }
</style>

<script>
    // Global variables
    window.DATA = [];
    let allFields = [];
    let visibleFields = [];
    let sortField = null;
    let sortDir = 'asc';
    let currentPage = 1;
    let pageSize = 50;
    let totalRecords = 0;
    window.filters = {};

    // Wait for DOM to be ready before initializing
    async function initTevkifatTable() {
        // Load initial data
        DATA = <?= json_encode($records) ?> || [];
        totalRecords = DATA.length;

        allFields = [{
                id: 'id',
                label: 'ID'
            },
            {
                id: 'firma',
                label: 'FIRMA'
            },
            {
                id: 'proje',
                label: 'PROJE'
            },
            {
                id: 'tarih',
                label: 'TARIH'
            },
            {
                id: 'karsi_hesap_ismi',
                label: 'KARSI HESAP ISMI'
            },
            {
                id: 'cost_code',
                label: 'COST CODE'
            },
            {
                id: 'vergi_matrahı',
                label: 'Vergi matrahı'
            },
            {
                id: 'kdv_orani',
                label: 'KDV Orani'
            },
            {
                id: 'tevkifat',
                label: 'Tevkifat'
            },
            {
                id: 'tevkifat_orani',
                label: 'Tevkifat Orani'
            },
            {
                id: 'toplam',
                label: 'Toplam'
            },
            {
                id: 'kdv_dahil',
                label: 'KDV DAHIL'
            },
            {
                id: 'tevkifat_usd',
                label: 'Tevkifat_USD'
            },
            {
                id: 'dikkate_alinmayacaklar',
                label: 'DIKKATE ALMA'
            }
        ];

        const defaultVisibleFields = ['firma', 'proje', 'tarih', 'karsi_hesap_ismi', 'cost_code', 'vergi_matrahı', 'kdv_orani', 'tevkifat', 'toplam', 'kdv_dahil'];
        pageSize = parseInt(localStorage.getItem('tevkifat_pageSize')) || 50;
        visibleFields = JSON.parse(localStorage.getItem('tevkifat_visibleFields')) || defaultVisibleFields;

        renderTable();
        initializeColumnPanel();
        setupPagination();
    }

    window.renderTable = function renderTable() {
        const headerRow = document.getElementById('headerRow');
        const filtersRow = document.getElementById('filtersRow');
        const tableBody = document.getElementById('tableBody');
        const colGroup = document.getElementById('colGroup');
        const footerStats = document.getElementById('footerStats');
        const pageIndicator = document.getElementById('pageIndicator');

        if (!headerRow || !filtersRow || !tableBody || !colGroup) {
            console.warn('Some table elements not found');
            return;
        }

        headerRow.innerHTML = '';
        filtersRow.innerHTML = '';
        tableBody.innerHTML = '';
        colGroup.innerHTML = '';

        // Filter data
        let filtered = window.DATA.filter(record => {
            for (let field in window.filters) {
                const dateFields = ['tarih'];
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

        // Sort data
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

        // Create colgroup
        visibleFields.forEach(fieldId => {
            const col = document.createElement('col');
            colGroup.appendChild(col);
        });

        // Create header row
        visibleFields.forEach(fieldId => {
            const field = allFields.find(f => f.id === fieldId);
            const th = document.createElement('th');
            th.className = 'sortable';
            th.setAttribute('data-field', fieldId);
            if (sortField === fieldId) th.setAttribute('data-sort', sortDir);
            th.textContent = field ? field.label : fieldId;
            th.onclick = () => {
                sortField = fieldId;
                sortDir = sortField === fieldId && sortDir === 'asc' ? 'desc' : 'asc';
                currentPage = 1;
                renderTable();
            };
            headerRow.appendChild(th);
        });

        // Create actions header
        const actionsHeader = document.createElement('th');
        actionsHeader.textContent = 'İŞLEMLER';
        actionsHeader.className = 'col-actions';
        headerRow.appendChild(actionsHeader);

        // Create filter row
        visibleFields.forEach(fieldId => {
            const th = document.createElement('th');
            th.className = 'filter-cell';

            const dateFields = ['tarih'];
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

        // Add empty header for actions in filter row
        const filterActionsHeader = document.createElement('th');
        filtersRow.appendChild(filterActionsHeader);

        // Create data rows
        paged.forEach(record => {
            const tr = document.createElement('tr');
            visibleFields.forEach(fieldId => {
                const td = document.createElement('td');
                const value = record[fieldId];

                if (fieldId === 'tarih' && value) {
                    td.textContent = new Date(value).toLocaleDateString('tr-TR');
                } else if (['vergi_matrahı', 'kdv_orani', 'tevkifat', 'tevkifat_orani', 'toplam', 'kdv_dahil', 'tevkifat_usd'].includes(fieldId) && value) {
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
            actionsCell.className = 'col-actions';
            actionsCell.innerHTML = `
                <a href="/tevkifat/edit/${record.id}" class="btn btn-sm btn-warning btn-icon" title="Düzenle">
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
        updatePaginationButtons(totalPages);

        if (window.updateTotals_tevkifatTable) {
            window.updateTotals_tevkifatTable([]);
        }
    }

    function initializeColumnPanel() {
        const container = document.getElementById('columnCheckboxes');
        container.innerHTML = '';

        allFields.forEach(field => {
            const label = document.createElement('label');
            label.className = 'form-check form-check-inline';
            label.innerHTML = `
                <input type="checkbox" class="form-check-input column-toggle" value="${field.id}" 
                    ${visibleFields.includes(field.id) ? 'checked' : ''}>
                <span class="form-check-label small">${field.label}</span>
            `;
            container.appendChild(label);
        });

        document.querySelectorAll('.column-toggle').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const fieldId = e.target.value;
                if (e.target.checked) {
                    if (!visibleFields.includes(fieldId)) {
                        visibleFields.push(fieldId);
                    }
                } else {
                    visibleFields = visibleFields.filter(f => f !== fieldId);
                }
                localStorage.setItem('tevkifat_visibleFields', JSON.stringify(visibleFields));
                renderTable();
            });
        });
    }

    function setupPagination() {
        document.getElementById('pageSizeSelect').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value);
            localStorage.setItem('tevkifat_pageSize', pageSize);
            currentPage = 1;
            renderTable();
        });

        document.querySelectorAll('#pager a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const action = e.target.closest('[data-page]').getAttribute('data-page');
                const totalPages = Math.max(1, Math.ceil(DATA.filter(r => Object.keys(filters).every(f => String(r[f] || '').toLowerCase().includes(String(filters[f] || '').toLowerCase()))).length / pageSize));

                if (action === 'first') currentPage = 1;
                else if (action === 'prev') currentPage = Math.max(1, currentPage - 1);
                else if (action === 'next') currentPage = Math.min(totalPages, currentPage + 1);
                else if (action === 'last') currentPage = totalPages;

                renderTable();
            });
        });
    }

    function updatePaginationButtons(totalPages) {
        const pagerLinks = document.querySelectorAll('#pager a');
        pagerLinks.forEach(link => {
            const action = link.getAttribute('data-page');
            const parentItem = link.closest('.page-item');

            if ((action === 'first' || action === 'prev') && currentPage === 1) {
                parentItem.classList.add('disabled');
            } else if ((action === 'next' || action === 'last') && currentPage === totalPages) {
                parentItem.classList.add('disabled');
            } else {
                parentItem.classList.remove('disabled');
            }
        });
    }

    function deleteRecord(id) {
        if (confirm('Silmek istediğinizden emin misiniz?')) {
            window.location.href = `/tevkifat/delete/${id}`;
        }
    }

    // Event listeners
    document.getElementById('toggleColumnPanel').addEventListener('click', () => {
        const panel = document.getElementById('columnPanel');
        panel.hidden = !panel.hidden;
    });

    document.getElementById('resetView').addEventListener('click', () => {
        const defaultVisibleFields = ['firma', 'proje', 'tarih', 'karsi_hesap_ismi', 'cost_code', 'vergi_matrahı', 'kdv_orani', 'tevkifat', 'toplam', 'kdv_dahil'];
        visibleFields = [...defaultVisibleFields];
        localStorage.removeItem('tevkifat_visibleFields');
        localStorage.removeItem('tevkifat_pageSize');
        pageSize = 50;
        currentPage = 1;
        filters = {};
        initTevkifatTable();
    });

    document.getElementById('downloadTemplate').addEventListener('click', () => {
        window.location.href = '/tevkifat/downloadTemplate';
    });

    document.getElementById('uploadFile').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const data = event.target.result;
                const workbook = XLSX.read(data, {
                    type: 'array',
                    cellDates: true
                });
                const sheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[sheetName];

                // Filter out ID field for upload (uploaded files don't have ID column)
                const uploadFields = allFields.filter(f => f.id !== 'id');

                // Extract rows starting from row 2 (row 1 is headers)
                const rows = [];
                let rowNum = 2;
                while (true) {
                    const row = {};
                    let hasData = false;
                    uploadFields.forEach((field, idx) => {
                        const cellAddr = XLSX.utils.encode_col(idx) + rowNum;
                        const cell = worksheet[cellAddr];
                        const val = cell ? cell.v : '';
                        if (val !== '' && val !== undefined) hasData = true;

                        // Parse dates from Excel serial format
                        if (field.id === 'tarih' && val) {
                            if (typeof val === 'number') {
                                const date = new Date((val - 25569) * 86400 * 1000);
                                row[field.id] = date.toISOString().split('T')[0];
                            } else if (typeof val === 'string') {
                                row[field.id] = val.split('T')[0];
                            }
                        } else {
                            row[field.id] = val !== undefined && val !== '' ? val : null;
                        }
                    });

                    if (!hasData) break;
                    rows.push(row);
                    rowNum++;
                }

                if (rows.length === 0) {
                    alert('Dosya boş görünüyor. Lütfen veri ekleyin.');
                    return;
                }

                // Prepare rows with headers as first row (for backend compatibility)
                const headerRow = uploadFields.map(f => f.label);
                const rowsWithHeaders = [headerRow, ...rows.map(row =>
                    uploadFields.map(f => row[f.id] || '')
                )];

                // Send to backend
                fetch('/tevkifat/bulkUpload', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            rows: rowsWithHeaders
                        })
                    })
                    .then(res => res.json())
                    .then(result => {
                        console.log('Upload response:', result);
                        if (result.inserted !== undefined && result.inserted > 0) {
                            const totalRows = rows.length;
                            const uploadedRows = result.inserted;
                            const message = `Veri Yükleme Sonucu:\n\nToplam satır: ${totalRows}\nBaşarıyla yüklenen: ${uploadedRows}`;
                            alert(message);
                            // Refresh page to show new data
                            window.location.reload();
                        } else {
                            alert('Hata: ' + (result.message || 'Bilinmeyen hata'));
                        }
                    })
                    .catch(err => {
                        console.error('Upload error:', err);
                        alert('Yükleme hatası: ' + err.message);
                    });
            } catch (error) {
                console.error('Error processing file:', error);
                alert('Dosya işleme hatası: ' + error.message);
            }
        };
        reader.readAsArrayBuffer(file);
    });

    document.getElementById('exportExcel').addEventListener('click', () => {
        const headers = visibleFields.map(id => allFields.find(f => f.id === id)?.label || id);
        const data = [headers, ...DATA.map(row => visibleFields.map(id => {
            const val = row[id];
            if (['vergi_matrahı', 'kdv_orani', 'tevkifat', 'tevkifat_orani', 'toplam', 'kdv_dahil', 'tevkifat_usd'].includes(id) && val) {
                return parseFloat(val).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                });
            }
            return val || '';
        }))];

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Tevkifat');
        XLSX.writeFile(wb, 'tevkifat_' + new Date().toLocaleDateString('tr-TR').replace(/\./g, '') + '.xlsx');
    });

    function initializeTevkifatTotals() {
        initTableTotals({
            tableId: 'tevkifatTable',
            fieldsToSum: ['tevkifat', 'toplam', 'kdv_dahil', 'tevkifat_usd'],
            dateFields: ['tarih'],
            formatters: {
                tevkifat: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                toplam: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                kdv_dahil: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                }),
                tevkifat_usd: (value) => parseFloat(value || 0).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2
                })
            }
        });
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize totals FIRST so the update function exists before rendering table
        initializeTevkifatTotals();
        // Then initialize the table (which will call updateTotals when rendering)
        initTevkifatTable();
    });
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
</div><!-- card -->
</body>

</html>