<?php

use App\Core\Helpers;

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Muhasebe') ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="/muhasebe/create"><i class="bi bi-plus-lg me-1"></i>Yeni Kayıt</a>
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
                <table class="table table-hover align-middle mb-0" id="muhasebeTable">
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

    #muhasebeTable {
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0;
    }

    #muhasebeTable thead {
        vertical-align: bottom;
    }

    #muhasebeTable thead th {
        position: relative;
        background-clip: padding-box;
        white-space: nowrap;
    }

    #muhasebeTable thead tr#filtersRow th {
        padding: .25rem .5rem;
        border-bottom: 0 !important;
    }

    #muhasebeTable thead tr#headerRow th {
        padding-top: .25rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid var(--bs-border-color) !important;
        vertical-align: bottom;
    }

    #muhasebeTable thead .filter-cell>* {
        display: block;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    #muhasebeTable thead input.form-control-sm,
    #muhasebeTable thead select.form-select-sm {
        min-height: 32px;
        line-height: 1.2;
    }

    #muhasebeTable th.col-actions {
        padding-left: .25rem;
        padding-right: .25rem;
    }

    #muhasebeTable th .col-resizer {
        position: absolute;
        top: 0;
        right: 0;
        width: 10px;
        height: 100%;
        cursor: col-resize;
        user-select: none;
        -webkit-user-select: none;
    }

    #muhasebeTable th.resizing,
    #muhasebeTable th .col-resizer.active {
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

    #muhasebeTable td .btn-group {
        gap: 4px;
    }

    #muhasebeTable td .btn-group .btn {
        border-width: 1px;
    }

    #muhasebeTable thead th.sortable {
        cursor: pointer;
    }

    #muhasebeTable thead th.sortable[data-sort="asc"]::after {
        content: " ↑";
        opacity: .6;
    }

    #muhasebeTable thead th.sortable[data-sort="desc"]::after {
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
    // Global variables for table management
    let allFields = [];
    let visibleFields = [];
    window.DATA = [];
    window.filters = {};
    let sortField = null;
    let sortDir = 'asc';
    let totalRecords = 0;
    let isLoadingMore = false;

    // Wait for DOM to be ready before initializing
    async function initMuhasebeTable() {
        // Load data via paginated API - load first page only for fast initial load
        try {
            let page = 1;
            const pageSize = 500;

            // Load first page immediately
            const resp = await fetch(`/muhasebe/api/records?page=${page}&pageSize=${pageSize}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (resp.ok) {
                const result = await resp.json();
                if (result.data && Array.isArray(result.data)) {
                    window.DATA = result.data;
                    totalRecords = result.pagination?.total || 0;
                    console.log(`Loaded initial page: ${result.data.length} records (Total: ${totalRecords})`);

                    // Load remaining pages in background (non-blocking)
                    if (result.pagination?.page < result.pagination?.totalPages) {
                        loadMorePagesInBackground(page + 1, result.pagination.totalPages, pageSize);
                    }
                } else {
                    window.DATA = [];
                }
            } else {
                console.warn('Could not load muhasebe records via API');
                window.DATA = [];
            }
        } catch (e) {
            console.warn('Could not load muhasebe records via API:', e);
            window.DATA = [];
        }

        allFields = [{
                id: 'id',
                label: 'ID'
            },
            {
                id: 'proje',
                label: 'Proje'
            },
            {
                id: 'tahakkuk_tarihi',
                label: 'Tahakkuk Tarihi'
            },
            {
                id: 'vade_tarihi',
                label: 'Vade Tarihi'
            },
            {
                id: 'cek_no',
                label: 'Çek No'
            },
            {
                id: 'aciklama',
                label: 'Açıklama'
            },
            {
                id: 'aciklama2',
                label: 'Açıklama 2'
            },
            {
                id: 'aciklama3',
                label: 'Açıklama 3'
            },
            {
                id: 'tutar_try',
                label: 'Tutar (TRY)'
            },
            {
                id: 'cari_hesap_ismi',
                label: 'Cari Hesap'
            },
            {
                id: 'wb',
                label: 'WB'
            },
            {
                id: 'ws',
                label: 'WS'
            },
            {
                id: 'row_col',
                label: 'Row'
            },
            {
                id: 'cost_code',
                label: 'Cost Code'
            },
            {
                id: 'dikkate_alinmayacaklar',
                label: 'Dikkate Alınmayacaklar'
            },
            {
                id: 'usd_karsiligi',
                label: 'USD Karşılığı'
            },
            {
                id: 'id_text',
                label: 'ID (Text)'
            },
            {
                id: 'id_veriler',
                label: 'ID Veriler'
            },
            {
                id: 'id_odeme_plan_satinalma_odeme_onay_listesi',
                label: 'ID Ödeme Plan'
            },
            {
                id: 'not_field',
                label: 'Not'
            },
            {
                id: 'not_ool_odeme_plani',
                label: 'Not OOL/Ödeme'
            }
        ];

        const defaultVisibleFields = ['id', 'proje', 'tahakkuk_tarihi', 'vade_tarihi', 'tutar_try', 'usd_karsiligi', 'cari_hesap_ismi', 'aciklama'];
        const pageSize = parseInt(localStorage.getItem('muhasebe_pageSize')) || 20;

        visibleFields = JSON.parse(localStorage.getItem('muhasebe_visibleFields')) || defaultVisibleFields;
        let currentPage = 1;

        window.renderTable = function renderTable() {
            const headerRow = document.getElementById('headerRow');
            const filtersRow = document.getElementById('filtersRow');
            const tableBody = document.getElementById('tableBody');
            const colGroup = document.getElementById('colGroup');
            const footerStats = document.getElementById('footerStats');
            const pageIndicator = document.getElementById('pageIndicator');

            if (!headerRow || !filtersRow || !tableBody || !colGroup) {
                console.warn('Some table elements not found:', {
                    headerRow,
                    filtersRow,
                    tableBody,
                    colGroup
                });
                return;
            }

            console.log('renderTable: DATA has', DATA.length, 'records');

            headerRow.innerHTML = '';
            filtersRow.innerHTML = '';
            tableBody.innerHTML = '';
            colGroup.innerHTML = '';

            let filtered = window.DATA.filter(record => {
                // Apply filters (text and date range with modes)
                for (let field in window.filters) {
                    const dateFields = ['tahakkuk_tarihi', 'vade_tarihi'];

                    if (dateFields.includes(field)) {
                        // Date filtering with three modes
                        const filterObj = window.filters[field];
                        if (!filterObj || (!filterObj.start_date && !filterObj.end_date)) continue;

                        const recordDate = String(record[field] || '').split('T')[0]; // Get YYYY-MM-DD
                        const mode = filterObj.mode || 'interval';
                        const startDate = filterObj.start_date;
                        const endDate = filterObj.end_date;

                        console.log(`Filtering field: ${field}, mode: ${mode}, recordDate: ${recordDate}, startDate: ${startDate}, endDate: ${endDate}`);

                        if (mode === 'after') {
                            // Only start date matters - record must be >= start date
                            if (startDate && recordDate < startDate) {
                                console.log(`  AFTER: ${recordDate} < ${startDate} => excluding`);
                                return false;
                            }
                        } else if (mode === 'up_to') {
                            // Only end date matters - record must be <= end date
                            if (endDate && recordDate > endDate) {
                                console.log(`  UP_TO: ${recordDate} > ${endDate} => excluding`);
                                return false;
                            }
                        } else {
                            // interval mode - both dates matter
                            if (startDate && recordDate < startDate) {
                                console.log(`  INTERVAL: ${recordDate} < ${startDate} => excluding`);
                                return false;
                            }
                            if (endDate && recordDate > endDate) {
                                console.log(`  INTERVAL: ${recordDate} > ${endDate} => excluding`);
                                return false;
                            }
                        }
                        continue; // Skip text filter check for date fields
                    } else {
                        // Text field filtering
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

                // Check if this is a date field
                const dateFields = ['tahakkuk_tarihi', 'vade_tarihi'];
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

                    if (['tahakkuk_tarihi', 'vade_tarihi'].includes(fieldId) && value) {
                        td.textContent = new Date(value).toLocaleDateString('tr-TR');
                    } else if (['tutar_try', 'usd_karsiligi'].includes(fieldId) && value) {
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
                    <a href="/muhasebe/edit?id=${record.id}" class="btn btn-sm btn-warning btn-icon" title="Düzenle">
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

            // Update totals row after rendering
            console.log('At end of renderTable: window.updateTotals_muhasebeTable exists?', typeof window.updateTotals_muhasebeTable);
            if (window.updateTotals_muhasebeTable) {
                console.log('Calling window.updateTotals_muhasebeTable([])');
                window.updateTotals_muhasebeTable([]);
            } else {
                console.warn('window.updateTotals_muhasebeTable does NOT exist!');
            }
        }

        const columnPanel = document.getElementById('columnPanel');
        const toggleButton = document.getElementById('toggleColumnPanel');
        const columnCheckboxes = document.getElementById('columnCheckboxes');
        const pageSizeSelect = document.getElementById('pageSizeSelect');
        const resetButton = document.getElementById('resetView');

        if (!columnPanel || !toggleButton || !columnCheckboxes || !pageSizeSelect || !resetButton) {
            console.warn('Some column panel elements not found');
            return;
        }

        toggleButton.onclick = () => {
            columnPanel.hidden = !columnPanel.hidden;
        };

        pageSizeSelect.value = pageSize;
        pageSizeSelect.onchange = () => {
            localStorage.setItem('muhasebe_pageSize', pageSizeSelect.value);
            currentPage = 1;
            renderTable();
        };

        resetButton.onclick = () => {
            localStorage.removeItem('muhasebe_visibleFields');
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
                    localStorage.setItem('muhasebe_visibleFields', JSON.stringify(visibleFields));
                    renderTable();
                };
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(' ' + field.label));
                columnCheckboxes.appendChild(label);
            });
        }

        // Defer initial render to AFTER data is loaded - use setTimeout to ensure async completion
        setTimeout(() => {
            console.log('Rendering table with', window.DATA.length, 'records');
            renderColumnCheckboxes();
            // Initialize totals FIRST so window.updateTotals_muhasebeTable exists
            initializeTotals();
            // Then render the table (which will call updateTotals)
            renderTable();
        }, 100);
    }

    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMuhasebeTable);
    } else {
        initMuhasebeTable();
    }

    function deleteRecord(id) {
        if (confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/muhasebe/delete';
            form.innerHTML = '<input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }

    function initButtonHandlers() {
        // Download Template
        const downloadBtn = document.getElementById('downloadTemplate');
        if (downloadBtn) {
            downloadBtn.onclick = () => {
                downloadTemplateXlsx();
            };
        }

        // Export to Excel
        const exportBtn = document.getElementById('exportExcel');
        if (exportBtn) {
            exportBtn.onclick = () => {
                exportToExcelXlsx();
            };
        }

        // Upload File
        const uploadFileInput = document.getElementById('uploadFile');
        if (uploadFileInput) {
            uploadFileInput.addEventListener('change', handleUpload);
        }

        // Confirm Upload Button
        const confirmBtn = document.getElementById('confirmUploadBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', handleConfirmUpload);
        }
    }

    // Load remaining pages in background without blocking UI
    async function loadMorePagesInBackground(startPage, totalPages, pageSize) {
        if (isLoadingMore) return;
        isLoadingMore = true;

        try {
            for (let page = startPage; page <= totalPages; page++) {
                const resp = await fetch(`/muhasebe/api/records?page=${page}&pageSize=${pageSize}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (resp.ok) {
                    const result = await resp.json();
                    if (result.data && Array.isArray(result.data)) {
                        window.DATA = window.DATA.concat(result.data);
                        console.log(`Loaded background page ${page}: ${result.data.length} records (Total: ${window.DATA.length}/${totalRecords})`);
                    }
                } else {
                    console.warn(`Failed to load page ${page}`);
                    break;
                }

                // Add small delay to avoid overwhelming server
                await new Promise(resolve => setTimeout(resolve, 50));
            }
        } catch (e) {
            console.warn('Error loading background pages:', e);
        } finally {
            isLoadingMore = false;
            console.log('Finished loading all data');
        }
    }

    // Initialize button handlers - defer to after load event
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initButtonHandlers, 0);
        });
    } else {
        setTimeout(initButtonHandlers, 0);
    }

    // Helper: Load XLSX library if needed
    async function loadXlsxIfNeeded() {
        if (typeof XLSX === 'undefined') {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
                script.onload = resolve;
                script.onerror = () => reject(new Error('XLSX library yüklenemedi'));
                document.head.appendChild(script);
            });
        }
    }

    // Helper: Read XLSX file
    async function readXlsx(file) {
        return new Promise((resolve, reject) => {
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
                    resolve(rows);
                } catch (err) {
                    reject(err);
                }
            };
            reader.onerror = () => reject(new Error('Dosya okunamadı'));
            reader.readAsArrayBuffer(file);
        });
    }

    async function downloadTemplateXlsx() {
        const allFields = [{
                id: 'proje',
                label: 'Proje'
            },
            {
                id: 'tahakkuk_tarihi',
                label: 'Tahakkuk Tarihi'
            },
            {
                id: 'vade_tarihi',
                label: 'Vade Tarihi'
            },
            {
                id: 'cek_no',
                label: 'Çek No'
            },
            {
                id: 'aciklama',
                label: 'Açıklama'
            },
            {
                id: 'aciklama2',
                label: 'Açıklama 2'
            },
            {
                id: 'aciklama3',
                label: 'Açıklama 3'
            },
            {
                id: 'tutar_try',
                label: 'Tutar (TRY)'
            },
            {
                id: 'cari_hesap_ismi',
                label: 'Cari Hesap'
            },
            {
                id: 'wb',
                label: 'WB'
            },
            {
                id: 'ws',
                label: 'WS'
            },
            {
                id: 'row_col',
                label: 'Row'
            },
            {
                id: 'cost_code',
                label: 'Cost Code'
            },
            {
                id: 'dikkate_alinmayacaklar',
                label: 'Dikkate Alınmayacaklar'
            },
            {
                id: 'usd_karsiligi',
                label: 'USD Karşılığı'
            },
            {
                id: 'id_text',
                label: 'ID (Text)'
            },
            {
                id: 'id_veriler',
                label: 'ID Veriler'
            },
            {
                id: 'id_odeme_plan_satinalma_odeme_onay_listesi',
                label: 'ID Ödeme Plan'
            },
            {
                id: 'not_field',
                label: 'Not'
            },
            {
                id: 'not_ool_odeme_plani',
                label: 'Not OOL/Ödeme'
            }
        ];

        const wb = XLSX.utils.book_new();
        const wsData = [allFields.map(f => f.label)];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        XLSX.utils.book_append_sheet(wb, ws, 'Muhasebe');
        XLSX.writeFile(wb, 'muhasebe_template.xlsx');
    }

    function exportToExcelXlsx() {
        const now = new Date();
        const dateStr = now.getFullYear() + '' + String(now.getMonth() + 1).padStart(2, '0') + '' + String(now.getDate()).padStart(2, '0');

        // Get visible columns only
        const visibleHeaders = allFields.filter(f => visibleFields.includes(f.id));

        // Get filtered and sorted data
        const wsData = [visibleHeaders.map(f => f.label)];

        // Apply filters
        let filteredData = window.DATA.filter(record => {
            return visibleHeaders.every(field => {
                const filterValue = window.filters[field.id] || '';
                if (!filterValue) return true;
                const cellValue = String(record[field.id] || '').toLowerCase();
                return cellValue.includes(filterValue.toLowerCase());
            });
        });

        // Apply sorting
        if (sortField) {
            const direction = sortDir === 'asc' ? 1 : -1;
            filteredData.sort((a, b) => {
                const aVal = a[sortField];
                const bVal = b[sortField];
                if (aVal === null || aVal === undefined) return direction;
                if (bVal === null || bVal === undefined) return -direction;
                if (aVal < bVal) return -direction;
                if (aVal > bVal) return direction;
                return 0;
            });
        }

        // Build data rows with proper formatting
        filteredData.forEach(record => {
            const row = visibleHeaders.map(field => {
                let value = record[field.id] || '';

                // Convert dates to Date objects for proper Excel formatting
                if ((field.id === 'tahakkuk_tarihi' || field.id === 'vade_tarihi') && value) {
                    // If it's a date string, convert to Date object
                    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
                        return new Date(value);
                    }
                }

                return value;
            });
            wsData.push(row);
        });

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(wsData);

        // Apply formatting to columns
        if (ws['!cols'] === undefined) ws['!cols'] = [];

        // Format columns based on field type
        visibleHeaders.forEach((field, colIndex) => {
            const cellAddress = XLSX.utils.encode_col(colIndex);

            // Set column width
            if (ws['!cols'][colIndex] === undefined) {
                ws['!cols'][colIndex] = {
                    wch: 12
                };
            }

            // Apply date format to date columns
            if (field.id === 'tahakkuk_tarihi' || field.id === 'vade_tarihi') {
                // Format date cells with date format
                for (let rowIndex = 2; rowIndex <= wsData.length; rowIndex++) {
                    const cellRef = cellAddress + rowIndex;
                    if (ws[cellRef]) {
                        ws[cellRef].z = 'yyyy-mm-dd';
                    }
                }
                ws['!cols'][colIndex] = {
                    wch: 12
                };
            }

            // Apply number format to numeric columns
            if (field.id === 'tutar_try' || field.id === 'usd_karsiligi') {
                // Format number cells with thousand separator and 2 decimal places
                for (let rowIndex = 2; rowIndex <= wsData.length; rowIndex++) {
                    const cellRef = cellAddress + rowIndex;
                    if (ws[cellRef]) {
                        ws[cellRef].z = '#,##0.00';
                    }
                }
                ws['!cols'][colIndex] = {
                    wch: 14
                };
            }
        });

        XLSX.utils.book_append_sheet(wb, ws, 'Muhasebe');
        XLSX.writeFile(wb, 'muhasebe_export_' + dateStr + '.xlsx');
    }

    async function handleUpload(e) {
        try {
            await loadXlsxIfNeeded();
        } catch (e2) {
            alert('XLSX kütüphanesi yüklenemedi: ' + (e2?.message || e2));
            return;
        }

        const input = e.currentTarget || e.target;
        const file = input?.files?.[0];
        if (!file) return;

        const name = (file.name || '').toLowerCase();

        try {
            let rows;
            if (name.endsWith('.xlsx') || name.endsWith('.xls')) {
                rows = await readXlsx(file);
            } else {
                throw new Error('Sadece .xlsx, .xls dosyaları desteklenir.');
            }

            if (!rows || rows.length === 0) throw new Error('Boş dosya veya okunamadı.');

            const preview = buildPreviewTable(rows);

            // Get elements with explicit wait and null safety
            let previewEl = document.getElementById('uploadPreview');
            let payloadEl = document.getElementById('uploadPayload');
            let modalEl = document.getElementById('uploadModal');

            console.log('Elements found:', {
                previewEl: !!previewEl,
                payloadEl: !!payloadEl,
                modalEl: !!modalEl,
                previewElId: previewEl?.id,
                payloadElId: payloadEl?.id,
                modalElId: modalEl?.id
            });

            if (!previewEl || !payloadEl || !modalEl) {
                console.error('Missing elements:', {
                    previewEl,
                    payloadEl,
                    modalEl
                });
                throw new Error('Modal bileşenleri eksik. Sayfayı yenileyip tekrar deneyin.');
            }

            // Set values
            previewEl.innerHTML = preview.html;

            try {
                console.log('Setting payload:', {
                    rows: rows.length,
                    payloadEl
                });
                payloadEl.value = JSON.stringify({
                    rows
                });
                console.log('Payload set successfully');
            } catch (err) {
                console.error('Error setting payload value:', {
                    err,
                    payloadEl,
                    type: typeof payloadEl
                });
                throw new Error('Yükle Payload ayarlanamadı: ' + (err?.message || err));
            }

            const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
            modal.show();

            if (input) input.value = '';
        } catch (err) {
            console.error('File upload error:', err);
            alert('Dosya okunamadı: ' + (err?.message || err));
            if (input) input.value = '';
        }
    }

    function buildPreviewTable(rows) {
        const headers = rows[0] || [];
        const dataRows = rows.slice(1);
        const PREVIEW_LIMIT = 100; // Only show first 100 rows in preview
        const displayRows = dataRows.slice(0, PREVIEW_LIMIT);
        const totalRowCount = dataRows.length;
        const truncated = totalRowCount > PREVIEW_LIMIT;

        let html = '<div class="alert alert-info mb-3" role="alert">';
        html += '<strong>Toplam Satır:</strong> ' + totalRowCount.toLocaleString('tr-TR') + ' satır';
        if (truncated) {
            html += ' <em class="text-muted">(Önizleme: ilk ' + PREVIEW_LIMIT + ' satır gösteriliyor)</em>';
        }
        html += '</div>';

        html += '<table class="table table-sm table-hover"><thead class="table-light"><tr>';
        html += '<th style="width:40px;"><input type="checkbox" id="selectAll" onchange="document.querySelectorAll(\'#uploadPreview input[data-row]\').forEach(cb => cb.checked = this.checked)"></th>';
        html += '<th>#</th>';
        headers.forEach((h) => {
            html += '<th>' + (h || '').toString().substring(0, 20) + '</th>';
        });
        html += '</tr></thead><tbody>';

        displayRows.forEach((row, idx) => {
            html += '<tr>';
            html += '<td><input type="checkbox" class="form-check-input" data-row="' + idx + '" checked></td>';
            html += '<td>' + (idx + 1) + '</td>';
            (row || []).forEach((cell) => {
                const val = (cell ?? '').toString();
                html += '<td>' + (val.substring(0, 30) || '-') + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';

        return {
            html,
            dataRows
        };
    }

    async function handleConfirmUpload(e) {
        e.preventDefault();

        const payloadEl = document.getElementById('uploadPayload');
        const val = payloadEl?.value || '';

        if (!val) {
            alert('Yüklenecek veri yok. Lütfen önce bir dosya seçin.');
            return;
        }

        try {
            const parsed = JSON.parse(val);
            if (!parsed || !Array.isArray(parsed.rows)) {
                alert('Geçersiz payload formatı.');
                return;
            }
        } catch {
            alert('Payload JSON değil.');
            return;
        }

        const headers = {
            'Accept': 'application/json'
        };
        const metaCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (metaCsrf) headers['X-CSRF-TOKEN'] = metaCsrf;

        const formData = new FormData();
        if (metaCsrf) formData.append('_token', metaCsrf);
        formData.append('payload', val);

        try {
            const resp = await fetch('/muhasebe/bulk-upload', {
                method: 'POST',
                headers,
                body: formData,
                credentials: 'same-origin'
            });

            const contentType = resp.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');
            const data = isJson ? await resp.json().catch(() => null) : await resp.text();

            if (!resp.ok) {
                const msg = isJson ? (data?.message || JSON.stringify(data)) : String(data);
                alert('Yükleme başarısız: ' + msg);
                return;
            }

            alert('Yükleme başarılı: ' + (data?.inserted || 0) + ' muhasebe kaydı eklendi.');

            // Hide modal and reload
            const modalEl = document.getElementById('uploadModal');
            const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
            modal.hide();

            setTimeout(() => {
                window.location.reload();
            }, 500);

        } catch (err) {
            alert('Yükleme sırasında hata oluştu: ' + (err?.message || err));
        }
    }
</script>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="uploadModalLabel" class="modal-title">Yükleme Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body" style="padding-bottom: 70px;">
                <div id="uploadPreview" class="upload-preview">
                    <p class="text-muted">Dosya seçildikten sonra önizleme burada görünecek...</p>
                </div>
                <input type="hidden" id="uploadPayload" value="">
            </div>
            <div class="modal-footer" style="position: sticky; bottom: 0; border-top: 1px solid #dee2e6; background-color: #fff; z-index: 1000;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="confirmUploadBtn">Onayla ve Yükle</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="/js/table-totals.js"></script>

<script>
    // Initialize table totals after table is rendered
    let totalsInitialized = false;

    function initializeTotals() {
        console.log('initializeTotals() called, totalsInitialized:', totalsInitialized);
        if (!totalsInitialized) {
            console.log('First time initialization - calling initTableTotals()');
            initTableTotals({
                tableId: 'muhasebeTable',
                fieldsToSum: ['tutar_try', 'usd_karsiligi'],
                dateFields: ['tahakkuk_tarihi', 'vade_tarihi'],
                formatters: {
                    tutar_try: (val) => parseFloat(val).toLocaleString('tr-TR', {
                        minimumFractionDigits: 2
                    }),
                    usd_karsiligi: (val) => parseFloat(val).toLocaleString('tr-TR', {
                        minimumFractionDigits: 2
                    })
                }
            });
            totalsInitialized = true;
        } else {
            // Update existing totals
            if (window.updateTotals_muhasebeTable) {
                window.updateTotals_muhasebeTable([]);
            }
        }
    }
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/flatpickr-date-filter.js"></script>

<style>
    /* Flatpickr date filter customization */
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