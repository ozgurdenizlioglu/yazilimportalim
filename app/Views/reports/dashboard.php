<?php

use App\Core\Helpers;

// Calculate component values
$muhasebe = $summaryMetrics['tahakkuk_edilen'] - ($summaryMetrics['tevkifat'] ?? 0) - ($summaryMetrics['barter_gerceklesen'] ?? 0) - ($summaryMetrics['tarihi_belirsiz_borclar'] ?? 0);
if ($muhasebe < 0) $muhasebe = 0;

// Check if actual spending is over budget
$isOverBudget = ($summaryMetrics['tahakkuk_edilen'] ?? 0) > ($summaryMetrics['toplam_kdv_dahil'] ?? 0);

$tevkifat = $summaryMetrics['tevkifat'] ?? 0;
$bakiye = $summaryMetrics['tarihi_belirsiz_borclar'] ?? 0;
$barter = $summaryMetrics['barter_gerceklesen'] ?? 0;
$balance = $summaryMetrics['kalan_kdv_dahil'] ?? 0;
$total = $summaryMetrics['toplam_kdv_dahil'] ?? 0;

// Prepare cost code data for JavaScript with safe output
$costCodeData = [];
if (!empty($reportData)) {
    foreach ($reportData as $item) {
        $costCodeData[] = [
            'cost_code' => $item['cost_code'] ?? '',
            'description' => $item['cost_code_aciklama'] ?? '',
            'budget' => (float)($item['toplam_kdv_dahil'] ?? 0),
            'actual' => (float)($item['tahakkuk_edilen'] ?? 0),
            'level' => (int)($item['level'] ?? 1)
        ];
    }
}

$reportDataJson = json_encode($costCodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <div>
            <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Mali Rapor - Kontrol Paneli') ?></h1>
            <?php if ($currentProject && $selectedProject !== '*'): ?>
                <small class="text-muted"><?= Helpers::e($currentProject['name'] ?? $selectedProject) ?></small>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-secondary" type="button" id="backToReport"><i class="bi bi-arrow-left me-1"></i>Rapora Dön</button>
        <button class="btn btn-info" type="button" id="refreshDashboard"><i class="bi bi-arrow-clockwise me-1"></i>Yenile</button>
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
        </div>
    </div>
</div>

<!-- Project Image + Metrics Section -->
<div class="row g-3 mb-4 align-items-stretch">
    <!-- Left: Project Image -->
    <?php if ($currentProject && ($currentProject['image_url'] || $currentProject['uuid'])): ?>
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="overflow: hidden; display: flex; flex-direction: column;">
                <?php
                $imageSrc = '';
                if ($currentProject['image_url']) {
                    // Extract filename from path (e.g., "/storage/projects/file.jpg" -> "file.jpg")
                    $imageSrc = '/reports/project-image?file=' . urlencode(basename($currentProject['image_url']));
                } elseif ($currentProject['uuid']) {
                    $imageSrc = '/reports/project-image?file=' . urlencode($currentProject['uuid'] . '.jpg');
                }
                ?>
                <img src="<?= Helpers::e($imageSrc) ?>"
                    alt="<?= Helpers::e($currentProject['name'] ?? 'Project') ?>"
                    style="width: 100%; height: auto; min-height: 400px; flex: 1; object-fit: cover; display: block;">
            </div>
        </div>
    <?php endif; ?>

    <!-- Right: Metrics Cards -->
    <div class="col-12 <?= ($currentProject && ($currentProject['image_url'] || $currentProject['uuid'])) ? 'col-lg-7' : 'col-lg-12' ?>">
        <div class="row g-3 h-100">
            <!-- Top Row: 3 Key Metrics -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="card-body">
                                <h6 class="card-title mb-2" style="opacity: 0.8;">TOPLAM BÜTÇE</h6>
                                <h3 class="mb-0">
                                    <?= number_format($total, 0, ',', '.') ?> TRY
                                </h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, <?= $isOverBudget ? '#ff4757 0%, #ee5a6f' : '#f093fb 0%, #f5576c' ?> 100%); color: white;">
                            <div class="card-body">
                                <h6 class="card-title mb-2" style="opacity: 0.8;">GERÇEKLEŞEN</h6>
                                <h3 class="mb-0" style="<?= $isOverBudget ? 'color: #ff1744; font-weight: 900;' : '' ?>">
                                    <?= number_format($summaryMetrics['tahakkuk_edilen'] ?? 0, 0, ',', '.') ?> TRY
                                </h3>
                                <?php if ($isOverBudget): ?>
                                    <small style="color: #ffcccb;"><strong>⚠ BÜTÇE AŞILDI</strong></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                            <div class="card-body">
                                <h6 class="card-title mb-2" style="opacity: 0.8;">KALAN</h6>
                                <h3 class="mb-0">
                                    <?= number_format($balance, 0, ',', '.') ?> TRY
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Chart and Summary Metrics -->
            <div class="col-12">
                <div class="row g-3">
                    <!-- Doughnut Chart -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <!-- Financial Progress -->
                            <div class="alert alert-info mb-0" style="border-radius: 0; border-bottom: 1px solid #dee2e6; padding: 1rem;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong style="font-size: 1.1rem;">FİNANSAL İLERLEME</strong>
                                    <div style="font-size: 1.8rem; font-weight: bold; color: #2c3e50;">
                                        <?= number_format(
                                            ($summaryMetrics['toplam_kdv_dahil'] > 0)
                                                ? ((($summaryMetrics['tahakkuk_edilen'] ?? 0) + ($summaryMetrics['barter_planlanan'] ?? 0) + ($summaryMetrics['barter_gerceklesen'] ?? 0)) / ($summaryMetrics['toplam_kdv_dahil'] ?? 1)) * 100
                                                : 0,
                                            2,
                                            ',',
                                            '.'
                                        ) ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">BÜTÇE KULLANIM DURUMU</h5>
                            </div>
                            <div class="card-body text-center" style="padding: 0.5rem; display: flex; align-items: center; justify-content: center; min-height: 250px;">
                                <div style="max-width: 100%; width: 100%; height: 100%; position: relative;">
                                    <canvas id="budgetBreakdownChart" style="max-width: 100%; max-height: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Metrics -->
                    <div class="col-12 col-md-6">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="card bg-light shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">TOPLAM BÜTÇE (KDV DAHİL)</h6>
                                        <h3 class="card-text text-primary fw-bold">
                                            <?= number_format($summaryMetrics['toplam_kdv_dahil'] ?? 0, 2, ',', '.') ?> TRY
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="card bg-light shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">MUHASEBE</h6>
                                        <h4 class="card-text fw-bold" style="color: #28a745;">
                                            <?= number_format($muhasebe, 0, ',', '.') ?>
                                        </h4>
                                        <small class="text-muted">
                                            % <?= $total > 0 ? number_format(($muhasebe / $total) * 100, 1, ',', '.') : '0,0' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">TEVKİFAT</h6>
                                        <h4 class="card-text fw-bold" style="color: #ffc107;">
                                            <?= number_format($tevkifat, 0, ',', '.') ?>
                                        </h4>
                                        <small class="text-muted">
                                            % <?= $total > 0 ? number_format(($tevkifat / $total) * 100, 1, ',', '.') : '0,0' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">BAKİYE</h6>
                                        <h4 class="card-text fw-bold" style="color: #17a2b8;">
                                            <?= number_format($bakiye, 0, ',', '.') ?>
                                        </h4>
                                        <small class="text-muted">
                                            % <?= $total > 0 ? number_format(($bakiye / $total) * 100, 1, ',', '.') : '0,0' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">BARTER</h6>
                                        <h4 class="card-text fw-bold" style="color: #e83e8c;">
                                            <?= number_format($barter, 0, ',', '.') ?>
                                        </h4>
                                        <small class="text-muted">
                                            % <?= $total > 0 ? number_format(($barter / $total) * 100, 1, ',', '.') : '0,0' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card bg-light shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">KALAN BAKİYE</h6>
                                        <h3 class="card-text fw-bold text-warning">
                                            <?= number_format($balance, 2, ',', '.') ?> TRY
                                        </h3>
                                        <small class="text-muted">
                                            % <?= $total > 0 ? number_format(($balance / $total) * 100, 1, ',', '.') : '0,0' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cost Code Breakdown with Collapsible Hierarchy -->
<div class="card shadow-sm">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">MALIYET KODLARI DETAYLI GÖRÜNÜM</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="costCodeTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>MALIYET KODU</th>
                        <th class="text-end">BÜTÇE</th>
                        <th class="text-end">GERÇEKLEŞEN</th>
                        <th class="text-end">KALAN</th>
                        <th class="text-end">% HARCANAN</th>
                    </tr>
                </thead>
                <tbody id="costCodeTableBody">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
    .cost-code-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .cost-code-row:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .cost-code-row.level-1 {
        font-weight: 700;
        background-color: rgba(0, 123, 255, 0.05);
    }

    .cost-code-row.level-2 {
        font-weight: 600;
        background-color: rgba(108, 117, 125, 0.03);
    }

    .cost-code-row.level-3 {
        font-weight: normal;
        padding-left: 2rem;
    }

    .toggle-btn {
        cursor: pointer;
        user-select: none;
        padding: 0;
        border: none;
        background: none;
        color: #007bff;
    }

    .toggle-btn:hover {
        color: #0056b3;
    }

    .collapse-icon {
        display: inline-block;
        transition: transform 0.2s;
        min-width: 20px;
    }

    .collapse-icon.collapsed {
        transform: rotate(-90deg);
    }

    .hidden-row {
        display: none;
    }

    .totals-row {
        background-color: #f8f9fa !important;
        font-weight: bold;
        border-top: 2px solid #dee2e6;
        border-bottom: 2px solid #dee2e6;
    }

    .totals-row td {
        padding: 12px 10px;
        background-color: #f8f9fa;
    }
</style>

<script>
    const reportData = <?= $reportDataJson ?>;

    // Back to Report
    document.getElementById('backToReport').addEventListener('click', () => {
        const selectedProject = document.getElementById('project').value || '*';
        const reportDate = document.getElementById('reportDate').value;
        const params = new URLSearchParams();
        if (selectedProject) params.append('project', selectedProject);
        if (reportDate) params.append('date', reportDate);
        window.location.href = '/reports?' + params.toString();
    });

    // Refresh
    document.getElementById('refreshDashboard').addEventListener('click', () => {
        location.reload();
    });

    // Project change
    document.getElementById('project').addEventListener('change', (e) => {
        const selectedProject = e.target.value;
        const reportDate = document.getElementById('reportDate').value;
        const params = new URLSearchParams();
        if (selectedProject) params.append('project', selectedProject);
        if (reportDate) params.append('date', reportDate);
        window.location.href = '/reports/dashboard?' + params.toString();
    });

    // Report date change
    document.getElementById('reportDate').addEventListener('change', (e) => {
        const reportDate = e.target.value;
        const selectedProject = document.getElementById('project').value;
        const params = new URLSearchParams();
        if (selectedProject) params.append('project', selectedProject);
        if (reportDate) params.append('date', reportDate);
        window.location.href = '/reports/dashboard?' + params.toString();
    });

    // Build cost code table with hierarchy
    function buildCostCodeTable() {
        const tbody = document.getElementById('costCodeTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        // Build hierarchy map
        const rows = reportData.sort((a, b) => {
            return (a.cost_code || '').localeCompare(b.cost_code || '');
        });

        // Calculate totals FIRST from all leaf nodes only
        let totalBudget = 0;
        let totalActual = 0;

        rows.forEach((row) => {
            const level = parseInt(row.level || 1);
            const costCode = row.cost_code || '';

            // Check if this is a leaf node (has no children)
            const hasChildren = rows.some(r => {
                const rCode = r.cost_code || '';
                return rCode !== costCode && (
                    rCode.startsWith(costCode + '-') ||
                    rCode.startsWith(costCode + '.')
                );
            });

            if (!hasChildren) {
                totalBudget += parseFloat(row.budget || 0);
                totalActual += parseFloat(row.actual || 0);
            }
        });

        // Add totals row at the TOP (first row)
        const totalsRow = document.createElement('tr');
        totalsRow.className = 'totals-row';
        totalsRow.style.cssText = 'background-color: #f8f9fa; font-weight: bold; border-bottom: 2px solid #dee2e6;';

        const totalRemaining = totalBudget - totalActual;
        const totalPercentage = totalBudget > 0 ? (totalActual / totalBudget) * 100 : 0;

        totalsRow.innerHTML = `
            <td></td>
            <td><strong>TOPLAM</strong></td>
            <td class="text-end"><strong>${new Intl.NumberFormat('tr-TR', {minimumFractionDigits: 0}).format(totalBudget)}</strong></td>
            <td class="text-end"><strong>${new Intl.NumberFormat('tr-TR', {minimumFractionDigits: 0}).format(totalActual)}</strong></td>
            <td class="text-end"><strong>${new Intl.NumberFormat('tr-TR', {minimumFractionDigits: 0}).format(totalRemaining)}</strong></td>
            <td class="text-end"><strong>${totalPercentage.toFixed(1)}%</strong></td>
        `;

        // Add totals row as first child
        tbody.appendChild(totalsRow);

        rows.forEach((row, index) => {
            const level = parseInt(row.level || 1);
            const costCode = row.cost_code || '';
            const description = row.description || '';
            const budget = parseFloat(row.budget || 0);
            const actual = parseFloat(row.actual || 0);
            const remaining = budget - actual;
            const percentage = budget > 0 ? (actual / budget) * 100 : 0;

            // Skip rows where all values are zero
            if (budget === 0 && actual === 0 && remaining === 0) {
                return; // Skip this row
            }

            // Check if has children
            const hasChildren = rows.some(r => {
                const rCode = r.cost_code || '';
                return rCode !== costCode && (
                    rCode.startsWith(costCode + '-') ||
                    rCode.startsWith(costCode + '.')
                );
            });

            const tr = document.createElement('tr');
            tr.className = `cost-code-row level-${level}`;
            tr.dataset.costCode = costCode;
            tr.dataset.level = level;

            // Hide level 3 by default
            if (level === 3) {
                tr.classList.add('hidden-row');
            }

            let toggleHtml = '';
            if (hasChildren) {
                toggleHtml = `<button class="toggle-btn" data-code="${costCode}">
                    <span class="collapse-icon">▼</span>
                </button>`;
            }

            const safeCostCode = escapeHtml(costCode);
            const safeDescription = escapeHtml(description);

            tr.innerHTML = `
                <td>${toggleHtml}</td>
                <td><strong>${safeCostCode}</strong> - ${safeDescription}</td>
                <td class="text-end">${new Intl.NumberFormat('tr-TR', {minimumFractionDigits: 0}).format(budget)}</td>
                <td class="text-end">${new Intl.NumberFormat('tr-TR', {minimumFractionDigits: 0}).format(actual)}</td>
                <td class="text-end">${new Intl.NumberFormat('tr-TR', {minimumFractionDigits: 0}).format(remaining)}</td>
                <td class="text-end"><strong>${percentage.toFixed(1)}%</strong></td>
            `;

            tbody.appendChild(tr);

            // Add collapse functionality
            if (hasChildren) {
                const btn = tr.querySelector('.toggle-btn');
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleChildren(costCode, btn);
                });
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function toggleChildren(parentCode, toggleBtn) {
        const tbody = document.getElementById('costCodeTableBody');
        const rows = tbody.querySelectorAll('tr.cost-code-row');
        const icon = toggleBtn.querySelector('.collapse-icon');
        const isCollapsed = icon.classList.contains('collapsed');

        rows.forEach(row => {
            const rowCode = row.dataset.costCode;
            const rowLevel = parseInt(row.dataset.level);

            if (rowCode.startsWith(parentCode + '-') || rowCode.startsWith(parentCode + '.')) {
                if (!isCollapsed) {
                    row.classList.add('hidden-row');
                    // Also hide nested children
                    const nestedToggle = row.querySelector('.toggle-btn');
                    if (nestedToggle) {
                        const nestedIcon = nestedToggle.querySelector('.collapse-icon');
                        nestedIcon.classList.add('collapsed');
                    }
                } else {
                    // Only show direct children
                    const pathParts = (rowCode.match(/[-\.]/g) || []).length;
                    const parentParts = (parentCode.match(/[-\.]/g) || []).length;
                    if (pathParts === parentParts + 1) {
                        row.classList.remove('hidden-row');
                    }
                }
            }
        });

        icon.classList.toggle('collapsed');
    }

    // Initialize Chart.js if available
    const ctx = document.getElementById('budgetBreakdownChart');
    if (ctx && typeof Chart !== 'undefined') {
        const muhasebe = <?= $muhasebe ?>;
        const tevkifat = <?= $tevkifat ?>;
        const bakiye = <?= $bakiye ?>;
        const barter = <?= $barter ?>;
        const balance = <?= $balance ?>;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Muhasebe', 'Tevkifat', 'Bakiye', 'Barter', 'Kalan'],
                datasets: [{
                    data: [muhasebe, tevkifat, bakiye, barter, balance],
                    backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#e83e8c', '#ffffff'],
                    borderColor: ['#1e7e34', '#ff9800', '#138496', '#d63384', '#cccccc'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return context.label + ': ' + new Intl.NumberFormat('tr-TR').format(value) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Build table on load
    buildCostCodeTable();
</script>