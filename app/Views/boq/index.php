<?php

use App\Core\Helpers;
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= Helpers::e($title ?? trans('common.boq')) ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" id="uploadDwgBtn"><i class="bi bi-upload me-1"></i>Upload DWG</button>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel</button>
    </div>
</div>

<!-- Upload DWG Modal -->
<div class="modal fade" id="uploadDwgModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload DWG File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadDwgForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="projectSelect" class="form-label">Project (Optional)</label>
                        <select class="form-select" id="projectSelect" name="project_id">
                            <option value="">-- Select Project --</option>
                            <!-- Populate from projects -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dwgFile" class="form-label">DWG File(s) - Select one or multiple files</label>
                        <input type="file" class="form-control" id="dwgFile" name="dwg" accept=".dwg" multiple required>
                        <small class="text-muted">Hold Ctrl (or Cmd on Mac) and click to select multiple files</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadDwgSubmit">Upload</button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($projectId)): ?>
            <div class="alert alert-info">
                Showing BOQ for Project ID: <?= Helpers::e($projectId) ?>
                <a href="/boq" class="btn btn-sm btn-outline-secondary ms-2">Show All</a>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="boqTable">
                <thead>
                    <tr>
                        <th>DWG File</th>
                        <th>Layer</th>
                        <th>Poz</th>
                        <th>Element</th>
                        <th>Length (m)</th>
                        <th>Area (m²)</th>
                        <th>Height (m)</th>
                        <th>Beton (m³)</th>
                        <th>Kalıp (m²)</th>
                        <th>Block</th>
                        <th>Floor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($boqItems)): ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No BOQ items found. Upload a DWG file to get started.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($boqItems as $item): ?>
                            <tr>
                                <td><?= Helpers::e($item['drawing'] ?? '') ?></td>
                                <td><small><?= Helpers::e($item['layer'] ?? '') ?></small></td>
                                <td><?= Helpers::e($item['poz_cizim'] ?? '') ?></td>
                                <td><?= Helpers::e($item['member_type'] ?? '') ?></td>
                                <td class="text-end"><?= number_format((float)($item['length'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($item['area'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($item['height'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($item['boq_beton'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($item['boq_formwork'] ?? 0), 2) ?></td>
                                <td><?= Helpers::e($item['block'] ?? '') ?></td>
                                <td><?= Helpers::e($item['floor'] ?? '') ?></td>
                                <td>
                                    <form method="POST" action="/boq/delete" style="display:inline;" onsubmit="return confirm('Delete this item?')">
                                        <input type="hidden" name="id" value="<?= Helpers::e($item['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadDwgBtn = document.getElementById('uploadDwgBtn');
        const uploadDwgModal = new bootstrap.Modal(document.getElementById('uploadDwgModal'));
        const uploadDwgSubmit = document.getElementById('uploadDwgSubmit');
        const uploadDwgForm = document.getElementById('uploadDwgForm');

        uploadDwgBtn.addEventListener('click', () => uploadDwgModal.show());

        uploadDwgSubmit.addEventListener('click', async () => {
            const formData = new FormData(uploadDwgForm);
            const dwgFile = document.getElementById('dwgFile').files[0];

            if (!dwgFile) {
                alert('Please select a DWG file');
                return;
            }

            uploadDwgSubmit.disabled = true;
            uploadDwgSubmit.textContent = 'Uploading...';

            try {
                const response = await fetch('/boq/upload', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const count = result.uploaded?.length || 0;
                    let message = `${count} file(s) uploaded successfully!`;
                    if (result.errors?.length > 0) {
                        message += '\n\nErrors:\n' + result.errors.join('\n');
                    }
                    alert(message + '\n\nProcessing will start shortly.');
                    uploadDwgModal.hide();
                    uploadDwgForm.reset();
                    // Reload page after a few seconds
                    setTimeout(() => location.reload(), 2000);
                } else if (result.uploaded?.length > 0) {
                    let message = `${result.uploaded.length} file(s) uploaded with errors:\n${result.errors.join('\n')}`;
                    alert(message + '\n\nProcessing will start shortly.');
                    uploadDwgModal.hide();
                    uploadDwgForm.reset();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Upload failed:\n' + (result.errors?.join('\n') || 'Unknown error'));
                }
            } catch (error) {
                alert('Upload error: ' + error.message);
            } finally {
                uploadDwgSubmit.disabled = false;
                uploadDwgSubmit.textContent = 'Upload';
            }
        });

        // Export to Excel
        document.getElementById('exportExcel')?.addEventListener('click', function() {
            window.location.href = '/boq?export=excel';
        });

        // Load projects for dropdown
        fetch('/projects/company-list')
            .then(r => r.json())
            .then(data => {
                const select = document.getElementById('projectSelect');
                if (data.projects) {
                    data.projects.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.uuid || p.id;
                        opt.textContent = p.name;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Failed to load projects:', err));
    });
</script>