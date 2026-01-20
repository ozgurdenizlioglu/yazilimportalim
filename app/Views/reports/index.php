<?php

use App\Core\Helpers;

$reportDataJson = json_encode($reportData ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$monthColumnsJson = json_encode($monthColumns ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <?php if ($currentProject && $currentProject['uuid']): ?>
            <div class="position-relative" style="width: 80px; height: 80px;">
                <img src="/storage/logos/<?= Helpers::e($currentProject['uuid']) ?>.png"
                    onerror="this.onerror=null; this.src='/storage/logos/<?= Helpers::e($currentProject['uuid']) ?>.jpg'; if (this.src.includes('logos')) { this.src='/storage/projects/<?= Helpers::e($currentProject['uuid']) ?>.png'; this.onerror=function() { this.src='/storage/projects/<?= Helpers::e($currentProject['uuid']) ?>.jpg'; }; }"
                    alt="<?= Helpers::e($currentProject['name'] ?? 'Project') ?>"
                    style="width: 100%; height: 100%; object-fit: contain; background: white; padding: 5px; border-radius: 8px; border: 1px solid #ddd;">
            </div>
        <?php endif; ?>
        <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Mali Rapor') ?></h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-info" type="button" id="openDashboard"><i class="bi bi-bar-chart me-1"></i>Kontrol Paneli</button>
        <button class="btn btn-outline-secondary" type="button" id="toggleColumnPanel"><i class="bi bi-columns-gap me-1"></i>Kolonları Yönet</button>
        <button class="btn btn-outline-secondary" type="button" id="resetView"><i class="bi bi-arrow-counterclockwise me-1"></i>Görünümü Sıfırla</button>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excele Aktar</button>
    </div>
</div>

<!-- Filter Card -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label">Rapor Tarihi</label>
                <input type="date" id="reportDate" class="form-control" value="<?= Helpers::e($reportDate) ?>">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Proje</label>
                <select id="project" class="form-select">
                    <option value="*" <?= ($selectedProject === '*') ? 'selected' : '' ?>>* Tümü</option>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= Helpers::e($proj['name'] ?? '') ?>"
                            <?= ($selectedProject === ($proj['name'] ?? '')) ? 'selected' : '' ?>>
                            <?= Helpers::e($proj['name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Başlangıç Ay</label>
                <input type="month" id="startMonthPicker" class="form-control" value="<?= !empty($startDate) ? substr($startDate, 0, 4) . '-' . substr($startDate, 4, 2) : '' ?>">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Bitiş Ay</label>
                <input type="month" id="endMonthPicker" class="form-control" value="<?= !empty($endDate) ? substr($endDate, 0, 4) . '-' . substr($endDate, 4, 2) : '' ?>">
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-wrap p-2 pt-0">
            <!-- Column Panel -->
            <div id="columnPanel" class="column-panel card card-body py-2 mb-2" hidden>
                <div class="d-flex align-items-center justify-content-between">
                    <strong>Görünen Kolonlar</strong>
                </div>
                <hr class="my-2">
                <div id="columnCheckboxes" class="columns-grid"></div>
            </div>

            <table class="table table-hover align-middle mb-0" id="reportTable">
                <colgroup id="colGroup"></colgroup>
                <thead id="tableHead">
                    <!-- Group Headers Row -->
                    <tr class="group-header">
                        <th colspan="3" style="border: 1px solid #bbb;">INFO</th>
                        <th colspan="2" style="border: 1px solid #bbb; text-align: center;">BUDGET ESTIMATION</th>
                        <th colspan="1" style="border: 1px solid #bbb; text-align: center;">BALANCE</th>
                        <th colspan="2" style="border: 1px solid #bbb; text-align: center;">DAILY</th>
                        <th colspan="1" style="border: 1px solid #bbb; text-align: center;">WITHHOLDING</th>
                        <th colspan="2" style="border: 1px solid #bbb; text-align: center;">MONTHLY</th>
                        <th colspan="3" style="border: 1px solid #bbb; text-align: center;">BARTER</th>
                        <th colspan="1" style="border: 1px solid #bbb; text-align: center;">UNCERTAIN</th>
                        <th colspan="1" style="border: 1px solid #bbb; text-align: center;">ACCRUED</th>
                        <th colspan="999" style="border: 1px solid #bbb; text-align: center;">MONTHLY DATA</th>
                    </tr>

                    <!-- Column Headers Row -->
                    <tr>
                        <th style="border: 1px solid #dee2e6;">LEVEL</th>
                        <th style="border: 1px solid #dee2e6;">COST CODE</th>
                        <th style="border: 1px solid #dee2e6;">COST CODE ACIKLAMA</th>
                    </tr>

                    <!-- Date Interval Info Row -->
                    <tr style="font-size: 0.75rem; color: #666;">
                        <td colspan="3"></td>
                    </tr>

                    <!-- Totals Row -->
                    <tr class="totals-row">
                        <td style="border: 1px solid #bbb;">TOTAL</td>
                        <td style="border: 1px solid #bbb;"></td>
                        <td style="border: 1px solid #bbb;"></td>
                    </tr>
                </thead>
                <tbody id="tableBody">
                </tbody>
            </table>

            <!-- Total Count -->
            <div class="pt-2">
                <small class="text-muted">Toplam: <span id="totalCount">0</span> kayıt</small>
            </div>
        </div>
    </div>
</div>

<style>
    .table-wrap {
        overflow-x: auto;
        overflow-y: auto;
        max-height: calc(100vh - 300px);
        position: relative;
    }

    .columns-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
    }

    .column-panel {
        background-color: #f8f9fa;
        border-left: 4px solid #1e4d5c;
    }

    .no-data {
        text-align: center;
        padding: 20px;
        color: #6c757d;
    }

    /* Auto width for columns based on content */
    #reportTable {
        table-layout: auto;
        width: auto;
        border-collapse: collapse;
    }

    #reportTable td,
    #reportTable th {
        border: 1px solid #bbb;
        padding: 8px 10px;
        white-space: nowrap;
    }

    /* Sticky headers - frozen on scroll - all 3 rows always visible */
    #reportTable thead tr:nth-child(1) {
        position: sticky;
        top: 0;
        z-index: 100;
    }

    #reportTable thead tr:nth-child(2) {
        position: sticky;
        top: 37px;
        z-index: 100;
    }

    #reportTable thead tr:nth-child(3) {
        position: sticky;
        top: 74px;
        z-index: 100;
    }

    #reportTable thead tr:nth-child(4) {
        position: sticky;
        top: 111px;
        z-index: 100;
    }

    #reportTable th {
        background-color: #2c3e50;
        color: white;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        text-align: center;
        white-space: nowrap;
    }

    /* Group header row styling */
    #reportTable thead tr.group-header th {
        background-color: #34495e;
        font-size: 11px;
        font-weight: 600;
    }

    /* Totals row styling */
    #reportTable thead tr.totals-row {
        background-color: #2c3e50;
    }

    #reportTable thead tr.totals-row td {
        background-color: #2c3e50 !important;
        font-weight: 800;
        text-align: right;
        font-size: 14px;
        color: white;
        padding: 12px 10px;
    }

    #reportTable thead tr.totals-row td:first-child,
    #reportTable thead tr.totals-row td:nth-child(2),
    #reportTable thead tr.totals-row td:nth-child(3) {
        background-color: #2c3e50 !important;
        text-align: left;
        font-weight: 800;
        color: white;
    }

    /* Color palette - CSS custom properties */
    :root {
        --lvl1-a: #C36A4A;
        --lvl1-b: #B1563E;
        --lvl2-a: #F3B34C;
        --lvl2-b: #E89A2A;
        --lvl3-a: #4DA3E6;
        --lvl3-b: #2C7BE5;
    }

    /* Background color classes for alternating shades */
    #reportTable .bg-lvl1-a {
        background-color: var(--lvl1-a) !important;
        color: #ffffff !important;
    }

    #reportTable .bg-lvl1-b {
        background-color: var(--lvl1-b) !important;
        color: #ffffff !important;
    }

    #reportTable .bg-lvl2-a {
        background-color: var(--lvl2-a) !important;
        color: #000000 !important;
    }

    #reportTable .bg-lvl2-b {
        background-color: var(--lvl2-b) !important;
        color: #ffffff !important;
    }

    #reportTable .bg-lvl3-a {
        background-color: var(--lvl3-a) !important;
        color: #ffffff !important;
    }

    #reportTable .bg-lvl3-b {
        background-color: var(--lvl3-b) !important;
        color: #ffffff !important;
    }

    #reportTable tbody tr {
        transition: opacity 0.2s;
    }

    #reportTable tbody tr:hover {
        opacity: 0.95;
    }

    /* Font weights by level */
    #reportTable tbody tr[data-level="1"] {
        font-weight: 700;
        font-size: 13px;
    }

    #reportTable tbody tr[data-level="2"] {
        font-weight: 600;
        font-size: 12px;
    }

    #reportTable tbody tr[data-level="3"] {
        font-weight: normal;
        font-size: 11px;
    }
</style>

<script>
    // Initialize variables and data
</script>

<script>
    const reportData = <?= $reportDataJson ?>;
    const monthColumns = <?= $monthColumnsJson ?>;

    const defaultColumns = [
        'level',
        'cost_code',
        'cost_code_aciklama',
        'toplam_kdv_haric',
        'toplam_kdv_dahil',
        'kalan_kdv_dahil',
        'odenmis_gunluk',
        'odenecek_gunluk',
        'odenmis_aylik',
        'odenecek_aylik',
        'tahakkuk_edilen',
        'tevkifat',
        'gerceklesen_barter',
        'planlanan_barter',
        'tarihi_belirsiz_borclar'
    ];

    const allColumns = [{
            id: 'level',
            label: 'LEVEL'
        },
        {
            id: 'cost_code',
            label: 'COST CODE'
        },
        {
            id: 'cost_code_aciklama',
            label: 'COST CODE ACIKLAMA'
        },
        {
            id: 'toplam_kdv_haric',
            label: 'TOPLAM (KDV HARIC)'
        },
        {
            id: 'toplam_kdv_dahil',
            label: 'TOPLAM (KDV DAHIL)'
        },
        {
            id: 'kalan_kdv_dahil',
            label: 'KALAN (KDV DAHIL)'
        },
        {
            id: 'odenmis_gunluk',
            label: 'ODENMIS GUNLUK'
        },
        {
            id: 'odenecek_gunluk',
            label: 'ODENECEK GUNLUK'
        },
        {
            id: 'odenecek_aylik',
            label: 'ODENECEK AYLIK'
        },
        {
            id: 'odenmis_aylik',
            label: 'ODENMIS AYLIK'
        },
        {
            id: 'tahakkuk_edilen',
            label: 'TAHAKKUK EDILEN'
        },
        {
            id: 'tevkifat',
            label: 'TEVKIFAT'
        },
        {
            id: 'gerceklesen_barter',
            label: 'BARTER GERCEKLESEN'
        },
        {
            id: 'planlanan_barter',
            label: 'BARTER PLANLANAN'
        },
        {
            id: 'barter_orani',
            label: 'BARTER ORANI'
        },
        {
            id: 'tarihi_belirsiz_borclar',
            label: 'TARIHI BELIRSIZ BORCLAR'
        },
        ...monthColumns.map(m => ({
            id: 'month_' + m,
            label: m
        }))
    ];

    let visibleColumns = localStorage.getItem('reportVisibleColumns') ?
        JSON.parse(localStorage.getItem('reportVisibleColumns')) :
        defaultColumns;

    // Safety check: if visibleColumns is empty or invalid, reset to defaultColumns
    if (!Array.isArray(visibleColumns) || visibleColumns.length === 0) {
        console.warn('visibleColumns is empty or invalid, resetting to defaultColumns');
        visibleColumns = [...defaultColumns];
        localStorage.removeItem('reportVisibleColumns');
    }

    // Define totalIdMap globally so it can be used in both rebuildTableHeaders and buildTable
    const totalIdMap = {
        'toplam_kdv_haric': 'total-toplam-kdv-haric',
        'toplam_kdv_dahil': 'total-toplam-kdv-dahil',
        'kalan_kdv_dahil': 'total-kalan',
        'odenmis_gunluk': 'total-odenmis-gunluk',
        'odenecek_gunluk': 'total-odenecek-gunluk',
        'tevkifat': 'total-tevkifat',
        'odenmis_aylik': 'total-odenmis-aylik',
        'odenecek_aylik': 'total-odenecek-aylik',
        'gerceklesen_barter': 'total-gerceklesen-barter',
        'barter_orani': 'total-barter-orani',
        'planlanan_barter': 'total-planlanan-barter',
        'tarihi_belirsiz_borclar': 'total-tarihi-belirsiz',
        'tahakkuk_edilen': 'total-tahakkuk'
    };



    // Initialize
    // Try both DOMContentLoaded and load events to ensure code runs
    function initializeApp() {
        // FORCE CLEAR CORRUPTED LOCALSTORAGE
        try {
            const stored = localStorage.getItem('reportVisibleColumns');
            if (stored) {
                const parsed = JSON.parse(stored);
                if (!Array.isArray(parsed) || parsed.length === 0) {
                    localStorage.removeItem('reportVisibleColumns');
                    visibleColumns = [...defaultColumns];
                    console.log('Cleared corrupted localStorage, reset visibleColumns');
                }
            }
        } catch (e) {
            localStorage.removeItem('reportVisibleColumns');
            visibleColumns = [...defaultColumns];
            console.log('localStorage parse error, reset:', e);
        }



        try {
            initializeColumnPanel();
            buildTable();
            attachEventListeners();
        } catch (e) {
            console.error('Error during initialization:', e);
        }
    }

    // Call initialization when both DOM is ready AND window has loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApp);
    } else {
        // DOM is already loaded, call it immediately
        initializeApp();
    }
    window.addEventListener('load', initializeApp);

    function initializeColumnPanel() {
        const container = document.getElementById('columnCheckboxes');
        container.innerHTML = '';
        allColumns.forEach(col => {
            const label = document.createElement('label');
            label.className = 'form-check form-check-inline';
            label.innerHTML = `
                <input type="checkbox" class="form-check-input column-toggle" value="${col.id}" 
                    ${visibleColumns.includes(col.id) ? 'checked' : ''}>
                <span class="form-check-label small">${col.label}</span>
            `;
            container.appendChild(label);
        });

        document.querySelectorAll('.column-toggle').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const colId = e.target.value;
                if (e.target.checked) {
                    if (!visibleColumns.includes(colId)) visibleColumns.push(colId);
                } else {
                    visibleColumns = visibleColumns.filter(c => c !== colId);
                }
                localStorage.setItem('reportVisibleColumns', JSON.stringify(visibleColumns));
                buildTable();
            });
        });
    }

    function updateDateIntervals() {
        // Parse report date from input
        const reportDateInput = document.getElementById('reportDate').value;
        if (!reportDateInput) return;

        // Parse date (format: YYYY-MM-DD)
        const date = new Date(reportDateInput + 'T00:00:00');
        if (isNaN(date.getTime())) return;

        // Get last day of the report month
        const lastDayOfMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        const firstDayNextMonth = new Date(date.getFullYear(), date.getMonth() + 1, 1);

        // Format dates in Turkish locale (DD/MM/YYYY)
        const formatTurkish = (d) => {
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}/${month}/${year}`;
        };

        const reportDateFormatted = formatTurkish(date);
        const lastDayStr = formatTurkish(lastDayOfMonth);
        const firstDayStr = formatTurkish(firstDayNextMonth);

        // Populate the date interval elements
        document.getElementById('daily-info').textContent = '~ - ' + reportDateFormatted;
        document.getElementById('daily-info-2').textContent = reportDateFormatted + ' - ~';
        document.getElementById('monthly-info').textContent = '~ - ' + lastDayStr;
        document.getElementById('monthly-info-2').textContent = firstDayStr + ' - ~';
    }

    function rebuildTableHeaders() {
        try {
            console.log('rebuildTableHeaders: Looking for tbody first...');
            const testTbody = document.getElementById('tableBody');
            console.log('rebuildTableHeaders: tbody element:', testTbody ? 'FOUND' : 'NOT FOUND');

            console.log('rebuildTableHeaders: Looking for thead with id="tableHead"');
            let thead = document.querySelector('thead#tableHead') || document.getElementById('tableHead');
            console.log('rebuildTableHeaders: thead element:', thead ? 'FOUND' : 'NOT FOUND');

            // If thead doesn't exist, create it
            if (!thead) {
                console.log('Creating thead element...');
                const table = document.getElementById('reportTable');
                if (table) {
                    thead = document.createElement('thead');
                    thead.id = 'tableHead';
                    // Insert thead at the beginning of the table, before colgroup/tbody
                    const colgroup = table.querySelector('colgroup');
                    if (colgroup && colgroup.nextElementSibling) {
                        table.insertBefore(thead, colgroup.nextElementSibling);
                    } else if (colgroup) {
                        table.insertBefore(thead, colgroup.nextElementSibling || null);
                    } else {
                        table.insertBefore(thead, table.firstChild);
                    }
                    console.log('thead element created and inserted');
                } else {
                    console.error('ERROR: reportTable element not found!');
                    return;
                }
            }

            // Clear all existing header rows
            thead.innerHTML = '';

            // === BUILD GROUP HEADER ROW (Row 0) ===
            const groupHeaderRow = document.createElement('tr');
            groupHeaderRow.className = 'group-header';

            // Column groups based on visibleColumns
            const columnGroups = {
                'info': ['level', 'cost_code', 'cost_code_aciklama'],
                'budget': ['toplam_kdv_haric', 'toplam_kdv_dahil'],
                'balance': ['kalan_kdv_dahil'],
                'daily': ['odenmis_gunluk', 'odenecek_gunluk'],
                'monthly': ['odenecek_aylik', 'odenmis_aylik'],
                'accrued': ['tahakkuk_edilen'],
                'withholding': ['tevkifat'],
                'barter': ['gerceklesen_barter', 'planlanan_barter'],
                'barter_orani': ['barter_orani'],
                'uncertain': ['tarihi_belirsiz_borclar']
            };

            const groupLabels = {
                'info': 'INFO',
                'budget': 'BUDGET ESTIMATION',
                'balance': 'BALANCE',
                'daily': 'DAILY',
                'monthly': 'MONTHLY',
                'accrued': 'ACCRUED',
                'withholding': 'WITHHOLDING',
                'barter': 'BARTER',
                'barter_orani': '',
                'uncertain': 'UNCERTAIN'
            };

            // Count visible columns per group
            for (const [groupKey, groupCols] of Object.entries(columnGroups)) {
                const visibleCount = groupCols.filter(col => visibleColumns.includes(col)).length;
                if (visibleCount > 0) {
                    const th = document.createElement('th');
                    th.style.border = '1px solid #bbb';
                    th.style.textAlign = 'center';
                    th.colSpan = visibleCount;
                    th.textContent = groupLabels[groupKey];
                    groupHeaderRow.appendChild(th);
                }
            }

            // Add MONTHLY DATA group if any month columns visible
            const monthCols = visibleColumns.filter(col => col.startsWith('month_'));
            if (monthCols.length > 0) {
                const th = document.createElement('th');
                th.style.border = '1px solid #bbb';
                th.style.textAlign = 'center';
                th.colSpan = monthCols.length;
                th.textContent = 'MONTHLY DATA';
                groupHeaderRow.appendChild(th);
            }

            thead.appendChild(groupHeaderRow);

            // === BUILD COLUMN HEADERS ROW (Row 1) ===
            const columnHeaderRow = document.createElement('tr');

            visibleColumns.forEach(colId => {
                const col = allColumns.find(c => c.id === colId);
                if (col) {
                    const th = document.createElement('th');
                    th.textContent = col.label;
                    th.style.border = '1px solid #dee2e6';

                    if (!['level', 'cost_code', 'cost_code_aciklama'].includes(colId)) {
                        th.className = 'text-end';
                    }
                    columnHeaderRow.appendChild(th);
                }
            });

            thead.appendChild(columnHeaderRow);

            // === BUILD DATE INTERVAL ROW (Row 2) ===
            const dateIntervalRow = document.createElement('tr');
            dateIntervalRow.style.fontSize = '0.75rem';
            dateIntervalRow.style.color = '#666';

            visibleColumns.forEach(colId => {
                const td = document.createElement('td');
                td.style.border = '1px solid #dee2e6';

                // Add date info only for specific columns
                if (colId === 'odenmis_gunluk') {
                    td.innerHTML = '<small id="daily-info"></small>';
                    td.className = 'text-end';
                } else if (colId === 'odenecek_gunluk') {
                    td.innerHTML = '<small id="daily-info-2"></small>';
                    td.className = 'text-end';
                } else if (colId === 'odenmis_aylik') {
                    td.innerHTML = '<small id="monthly-info"></small>';
                    td.className = 'text-end';
                } else if (colId === 'odenecek_aylik') {
                    td.innerHTML = '<small id="monthly-info-2"></small>';
                    td.className = 'text-end';
                }
                dateIntervalRow.appendChild(td);
            });

            thead.appendChild(dateIntervalRow);

            // === BUILD TOTALS ROW (Row 3) ===
            const totalsRow = document.createElement('tr');
            totalsRow.className = 'totals-row';

            // Add totals cells for ALL visible columns
            visibleColumns.forEach((colId, idx) => {
                const td = document.createElement('td');
                td.style.border = '1px solid #bbb';

                // First column (index 0) is the label column
                if (idx === 0) {
                    td.textContent = 'TOTAL';
                    td.style.textAlign = 'left';
                    td.style.fontWeight = '800';
                } else if (idx === 1 || idx === 2) {
                    // Second and third columns are empty labels
                    td.textContent = '';
                } else {
                    // Other columns get total values
                    td.className = 'text-end';
                    const totalId = totalIdMap[colId];
                    if (totalId) {
                        td.id = totalId;
                        td.textContent = '0.00';
                    } else if (colId.startsWith('month_')) {
                        const monthKey = colId.replace('month_', '');
                        td.id = 'month-total-' + monthKey;
                        td.textContent = '0.00';
                    }
                }
                totalsRow.appendChild(td);
            });

            thead.appendChild(totalsRow);
        } catch (e) {
            console.error('Error in rebuildTableHeaders:', e);
        }
    }

    function buildTable() {
        console.log('buildTable() called. reportData keys:', Object.keys(reportData).length);

        // Rebuild headers first
        try {
            rebuildTableHeaders();
            updateDateIntervals();
        } catch (e) {
            console.error('Error rebuilding headers:', e);
        }

        // Get tbody reference
        const tbody2 = document.getElementById('tableBody');
        if (!tbody2) {
            console.error('ERROR: tbody not found!');
            return;
        }

        // Clear tbody
        tbody2.innerHTML = '';

        try {
            const entries = Object.entries(reportData);
            console.log('entries.length:', entries.length);

            if (entries.length === 0) {
                tbody2.innerHTML = '<tr><td colspan="100">No data</td></tr>';
                document.getElementById('totalCount').textContent = '0';
                return;
            }

            // Build HTML string instead of using createElement
            let rowsHTML = '';

            const levelCounters = {
                1: 0,
                2: 0,
                3: 0
            };

            const colorMap = {
                1: ['bg-lvl1-a', 'bg-lvl1-b'],
                2: ['bg-lvl2-a', 'bg-lvl2-b'],
                3: ['bg-lvl3-a', 'bg-lvl3-b']
            };

            const totals = {
                'toplam_kdv_haric': 0,
                'toplam_kdv_dahil': 0,
                'kalan_kdv_dahil': 0,
                'odenmis_gunluk': 0,
                'odenecek_gunluk': 0,
                'tevkifat': 0,
                'odenmis_aylik': 0,
                'odenecek_aylik': 0,
                'gerceklesen_barter': 0,
                'barter_orani': 0,
                'planlanan_barter': 0,
                'tarihi_belirsiz_borclar': 0,
                'tahakkuk_edilen': 0
            };
            const monthTotals = {};

            function isLeafCostCode(costCode) {
                return !entries.some(([key, row]) => {
                    const rowCode = row.cost_code;
                    return rowCode !== costCode &&
                        ((rowCode.startsWith(costCode + '-')) || (rowCode.startsWith(costCode + '.')));
                });
            }

            let rowAddCounter = 0;

            entries.forEach(([key, row]) => {
                try {
                    rowAddCounter++;
                    let rowHTML = '<tr style="height: auto;" data-level="' + (parseInt(row.level) || 1) + '">';

                    let dataLevel = parseInt(row.level) || 1;
                    dataLevel = Math.min(dataLevel, 3);

                    levelCounters[dataLevel]++;
                    const colorIndex = (levelCounters[dataLevel] - 1) % 2;
                    const colorClass = colorMap[dataLevel][colorIndex];

                    visibleColumns.forEach(colId => {
                        const value = colId.startsWith('month_') ?
                            (row.monthly_data?.[colId.replace('month_', '')] || 0) :
                            (row[colId] || '');

                        let cellHTML = '';
                        if (typeof value === 'number' && !['cost_code', 'cost_code_aciklama', 'barter_orani', 'level'].includes(colId)) {
                            const formatted = value.toLocaleString('tr-TR', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            cellHTML = '<td class="' + colorClass + ' text-end" style="border: 1px solid #999; padding: 8px 10px; white-space: nowrap;">' + formatted + '</td>';

                            const isLeaf = isLeafCostCode(row.cost_code);
                            if (colId.startsWith('month_')) {
                                const monthKey = colId.replace('month_', '');
                                if (isLeaf) {
                                    monthTotals[monthKey] = (monthTotals[monthKey] || 0) + value;
                                }
                            } else if ((colId === 'toplam_kdv_haric' || colId === 'toplam_kdv_dahil' ||
                                    colId === 'kalan_kdv_dahil' || colId === 'odenmis_gunluk' || colId === 'odenecek_gunluk' ||
                                    colId === 'odenmis_aylik' || colId === 'odenecek_aylik' ||
                                    colId === 'tahakkuk_edilen' || colId === 'tevkifat' ||
                                    colId === 'gerceklesen_barter' || colId === 'planlanan_barter' ||
                                    colId === 'tarihi_belirsiz_borclar') && isLeaf) {
                                totals[colId] += value;
                            }
                        } else if (colId === 'barter_orani') {
                            const valStr = typeof value === 'number' ? value.toFixed(2) : value;
                            cellHTML = '<td class="' + colorClass + ' text-end" style="border: 1px solid #999; padding: 8px 10px; white-space: nowrap;">' + valStr + '%</td>';
                        } else {
                            cellHTML = '<td class="' + colorClass + '" style="border: 1px solid #999; padding: 8px 10px; white-space: nowrap;">' + value + '</td>';
                        }
                        rowHTML += cellHTML;
                    });

                    rowHTML += '</tr>';
                    rowsHTML += rowHTML;
                } catch (e) {
                    console.error('Error in forEach row iteration:', e, 'key:', key);
                }
            });

            console.log('forEach completed. rowAddCounter =', rowAddCounter);
            tbody2.innerHTML = rowsHTML;
            console.log('tbody.innerHTML has been set');
            document.getElementById('totalCount').textContent = entries.length;

            // Update total cells
            Object.keys(totalIdMap).forEach(colId => {
                const el = document.getElementById(totalIdMap[colId]);
                if (el && visibleColumns.includes(colId)) {
                    el.textContent = totals[colId].toLocaleString('tr-TR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });

            // Update month totals
            Object.keys(monthTotals).forEach(month => {
                const el = document.getElementById('month-total-' + month);
                if (el) {
                    el.textContent = monthTotals[month].toLocaleString('tr-TR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        } catch (e) {
            console.error('FATAL ERROR in buildTable:', e, e.stack);
            tbody2.innerHTML = '<tr><td colspan="100" style="background-color: #ff0000; color: white;">ERROR: ' + e.message + '</td></tr>';
        }
    }

    function getParentLevel(level) {
        if (!level) return '';

        // Handle levels with dashes or dots
        // For "01-01-01", parent would be "01-01"
        // For "01-01", parent would be "01"
        // For "01", parent would be "01"
        const separator = level.includes('-') ? '-' : (level.includes('.') ? '.' : '');
        if (!separator) return level;

        const parts = level.split(separator);
        // If it's a deep level (3+ parts), return first 2 parts
        // If it's a mid level (2 parts), return first part
        // If it's a top level (1 part), return itself
        if (parts.length >= 3) {
            return parts.slice(0, 2).join(separator);
        } else if (parts.length === 2) {
            return parts[0];
        }
        return level;
    }

    function getDepth(level) {
        if (!level) return 0;

        // Count separators to determine depth
        // "01" = 0, "01-01" = 1, "01-01-01" = 2
        const dashCount = (level.match(/-/g) || []).length;
        const dotCount = (level.match(/\./g) || []).length;
        return dashCount + dotCount;
    }

    function attachEventListeners() {
        document.getElementById('toggleColumnPanel').addEventListener('click', () => {
            const panel = document.getElementById('columnPanel');
            panel.hidden = !panel.hidden;
        });

        document.getElementById('resetView').addEventListener('click', () => {
            visibleColumns = [...defaultColumns];
            localStorage.setItem('reportVisibleColumns', JSON.stringify(visibleColumns));
            initializeColumnPanel();
            buildTable();
        });

        // Month picker change handlers - add month columns dynamically
        document.getElementById('startMonthPicker').addEventListener('change', updateMonthColumns);
        document.getElementById('endMonthPicker').addEventListener('change', updateMonthColumns);

        // Report date change handler - reload report with new date
        document.getElementById('reportDate').addEventListener('change', (e) => {
            const reportDate = e.target.value;
            const selectedProject = document.getElementById('project').value;
            const startMonth = document.getElementById('startMonthPicker').value;
            const endMonth = document.getElementById('endMonthPicker').value;

            // Build query string
            const params = new URLSearchParams();
            if (selectedProject) params.append('project', selectedProject);
            if (reportDate) params.append('date', reportDate);
            if (startMonth) params.append('start_date', startMonth.replace('-', ''));
            if (endMonth) params.append('end_date', endMonth.replace('-', ''));

            // Reload page with new filters
            window.location.href = `/reports?${params.toString()}`;
        });

        // Project change handler - reload report with new project
        document.getElementById('project').addEventListener('change', (e) => {
            const selectedProject = e.target.value;
            const reportDate = document.getElementById('reportDate').value;
            const startMonth = document.getElementById('startMonthPicker').value;
            const endMonth = document.getElementById('endMonthPicker').value;

            // Build query string
            const params = new URLSearchParams();
            if (selectedProject) params.append('project', selectedProject);
            if (reportDate) params.append('date', reportDate);
            if (startMonth) params.append('start_date', startMonth.replace('-', ''));
            if (endMonth) params.append('end_date', endMonth.replace('-', ''));

            // Reload page with new filters
            window.location.href = `/reports?${params.toString()}`;
        });

        document.getElementById('exportExcel').addEventListener('click', exportToExcel);
    }

    function updateMonthColumns() {
        const startInput = document.getElementById('startMonthPicker').value;
        const endInput = document.getElementById('endMonthPicker').value;

        if (!startInput || !endInput) return;

        // Convert YYYY-MM format to YYYYMM
        const startDate = startInput.replace('-', '');
        const endDate = endInput.replace('-', '');

        // Generate month columns
        const newMonthColumns = generateMonthColumnsFromDates(startDate, endDate);

        // Update allColumns with new month columns
        const nonMonthColumns = allColumns.filter(c => !c.id.startsWith('month_'));
        allColumns.length = 0;
        allColumns.push(...nonMonthColumns);
        allColumns.push(...newMonthColumns.map(m => ({
            id: 'month_' + m,
            label: m
        })));

        // Initialize checkboxes again to include new month columns
        initializeColumnPanel();
        buildTable();
    }

    function generateMonthColumnsFromDates(startDate, endDate) {
        const months = [];

        if (startDate.length === 6 && endDate.length === 6) {
            const startYear = parseInt(startDate.substring(0, 4));
            const startMonth = parseInt(startDate.substring(4, 6));
            const endYear = parseInt(endDate.substring(0, 4));
            const endMonth = parseInt(endDate.substring(4, 6));

            let currentYear = startYear;
            let currentMonth = startMonth;

            while (currentYear < endYear || (currentYear === endYear && currentMonth <= endMonth)) {
                months.push(currentYear + String(currentMonth).padStart(2, '0'));
                currentMonth++;
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                }
            }
        }

        return months;
    }

    function exportToExcel() {
        const selectedProject = document.getElementById('project').value || '*';
        const reportDate = document.getElementById('reportDate').value;
        const startMonth = document.getElementById('startMonthPicker').value;
        const endMonth = document.getElementById('endMonthPicker').value;

        // Build query string for export endpoint
        const params = new URLSearchParams();
        params.append('project', selectedProject);
        if (reportDate) params.append('date', reportDate);
        if (startMonth) params.append('start_date', startMonth.replace('-', ''));
        if (endMonth) params.append('end_date', endMonth.replace('-', ''));

        // Redirect to export endpoint
        window.location.href = '/reports/export?' + params.toString();
    }

    // Dashboard button handler
    document.getElementById('openDashboard').addEventListener('click', () => {
        const selectedProject = document.getElementById('project').value || '*';
        const reportDate = document.getElementById('reportDate').value;
        const startMonth = document.getElementById('startMonthPicker').value;
        const endMonth = document.getElementById('endMonthPicker').value;

        // Build query string
        const params = new URLSearchParams();
        if (selectedProject) params.append('project', selectedProject);
        if (reportDate) params.append('date', reportDate);
        if (startMonth) params.append('start_date', startMonth.replace('-', ''));
        if (endMonth) params.append('end_date', endMonth.replace('-', ''));

        // Redirect to dashboard
        window.location.href = '/reports/dashboard?' + params.toString();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>