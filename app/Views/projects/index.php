<?php

use App\Core\Helpers;

ob_start();

?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Projeler') ?></h1>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="/projects/create"><i class="bi bi-plus-lg me-1"></i>Yeni Proje</a>
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

<?php $projectsJson = json_encode($projects ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>

<?php if (!empty($projects)): ?>

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
          <table class="table table-hover align-middle mb-0" id="projectsTable">
            <colgroup id="colGroup"></colgroup>
            <thead id="tableHead">
              <tr id="filtersRow"></tr>
              <tr id="headerRow"></tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>

        <!-- Upload önizleme modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 id="uploadModalLabel" class="modal-title">Yükleme Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
              </div>
              <div class="modal-body">
                <div id="uploadPreview" class="upload-preview">Dosya okunuyor…</div>
              </div>
              <div class="modal-footer">
                <form id="uploadSubmitForm" method="post" action="/projects/bulk-upload" class="ms-auto">
                  <!-- CSRF gerekiyorsa backend’inize uygun şekilde hidden ekleyin:
                  <input type="hidden" name="_token" value="<?= Helpers::e($_SESSION['csrf'] ?? '') ?>">
                  -->
                  <input type="hidden" name="payload" id="uploadPayload">
                  <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Sunucuya Yükle</button>
                </form>
              </div>
            </div>
          </div>
        </div>

      </div> <!-- table-wrap -->
    </div> <!-- card-body -->

    <!-- Kart altı: toplam | sayfa ve sayfalama -->
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
  </div> <!-- card -->

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

    #projectsTable {
      table-layout: auto;
      border-collapse: separate;
      border-spacing: 0;
    }

    #projectsTable thead {
      vertical-align: bottom;
    }

    #projectsTable thead th {
      position: relative;
      background-clip: padding-box;
      white-space: nowrap;
    }

    #projectsTable thead tr#filtersRow th {
      padding: .25rem .5rem;
      border-bottom: 0 !important;
    }

    #projectsTable thead tr#headerRow th {
      padding-top: .25rem;
      padding-bottom: .4rem;
      border-bottom: 1px solid var(--bs-border-color) !important;
      vertical-align: bottom;
    }

    #projectsTable thead .filter-cell>* {
      display: block;
      width: 100%;
      max-width: 100%;
      margin: 0;
    }

    #projectsTable thead input.form-control-sm,
    #projectsTable thead select.form-select-sm {
      min-height: 32px;
      line-height: 1.2;
    }

    #projectsTable th.col-actions {
      padding-left: .25rem;
      padding-right: .25rem;
    }

    #projectsTable th .col-resizer {
      position: absolute;
      top: 0;
      right: 0;
      width: 10px;
      height: 100%;
      cursor: col-resize;
      user-select: none;
      -webkit-user-select: none;
    }

    #projectsTable th.resizing,
    #projectsTable th .col-resizer.active {
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

    #projectsTable td .btn-group {
      gap: 4px;
    }

    #projectsTable td .btn-group .btn {
      border-width: 1px;
    }

    #projectsTable thead th.sortable {
      cursor: pointer;
    }

    #projectsTable thead th.sortable[data-sort="asc"]::after {
      content: " ↑";
      opacity: .6;
    }

    #projectsTable thead th.sortable[data-sort="desc"]::after {
      content: " ↓";
      opacity: .6;
    }

    .badge.ok {
      background: var(--bs-success-bg-subtle, #d1e7dd);
      color: var(--bs-success-text, #0f5132);
      border: 1px solid var(--bs-success-border-subtle, #badbcc);
    }

    .badge.no {
      background: var(--bs-danger-bg-subtle, #f8d7da);
      color: var(--bs-danger-text, #842029);
      border: 1px solid var(--bs-danger-border-subtle, #f5c2c7);
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

  <?php ob_start(); ?>

  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.20.2/dist/xlsx.full.min.js"></script>

  <script>
    (function() {

      const DATA = <?= $projectsJson ?: '[]'; ?>;

      // Proje alanları
      const allFields = [{
          id: 'id',
          label: 'ID'
        },
        {
          id: 'uuid',
          label: 'UUID'
        },
        {
          id: 'created_at',
          label: 'Oluşturma'
        },
        {
          id: 'updated_at',
          label: 'Güncelleme'
        },
        {
          id: 'deleted_at',
          label: 'Silinme'
        },
        {
          id: 'name',
          label: 'Ad'
        },
        {
          id: 'short_name',
          label: 'Kısa Ad'
        },
        {
          id: 'project_path',
          label: 'Path'
        },
        {
          id: 'company_id',
          label: 'Firma ID'
        },
        {
          id: 'start_date',
          label: 'Başlangıç'
        },
        {
          id: 'end_date',
          label: 'Bitiş'
        },
        {
          id: 'budget',
          label: 'Bütçe'
        },
        {
          id: 'status',
          label: 'Durum'
        },
        {
          id: 'currency_code',
          label: 'Para Birimi'
        },
        {
          id: 'timezone',
          label: 'Zaman Dilimi'
        },
        {
          id: 'image_url',
          label: 'Görsel URL'
        },
        {
          id: 'notes',
          label: 'Notlar'
        },
        {
          id: 'address_line1',
          label: 'Adres 1'
        },
        {
          id: 'address_line2',
          label: 'Adres 2'
        },
        {
          id: 'city',
          label: 'Şehir'
        },
        {
          id: 'state_region',
          label: 'Eyalet/Bölge'
        },
        {
          id: 'postal_code',
          label: 'Posta Kodu'
        },
        {
          id: 'country_code',
          label: 'Ülke (ISO-2)'
        },
        {
          id: 'is_active',
          label: 'Aktif'
        },
        {
          id: 'created_by',
          label: 'Oluşturan'
        },
        {
          id: 'updated_by',
          label: 'Güncelleyen'
        },
      ];

      const columns = [{
          id: 'actions',
          label: 'İşlemler',
          isAction: true,
          className: 'col-actions'
        },
        {
          id: 'id',
          label: 'ID',
          filterType: 'text',
          className: 'text-end'
        },
        {
          id: 'uuid',
          label: 'UUID',
          filterType: 'text'
        },
        {
          id: 'name',
          label: 'Ad',
          filterType: 'text'
        },
        {
          id: 'short_name',
          label: 'Kısa Ad',
          filterType: 'text'
        },
        {
          id: 'project_path',
          label: 'Path',
          filterType: 'text'
        },
        {
          id: 'company_id',
          label: 'Firma ID',
          filterType: 'text'
        },
        {
          id: 'start_date',
          label: 'Başlangıç',
          filterType: 'date'
        },
        {
          id: 'end_date',
          label: 'Bitiş',
          filterType: 'date'
        },
        {
          id: 'budget',
          label: 'Bütçe',
          filterType: 'text',
          className: 'text-end'
        },
        {
          id: 'status',
          label: 'Durum',
          filterType: 'text'
        },
        {
          id: 'currency_code',
          label: 'Para Birimi',
          filterType: 'text'
        },
        {
          id: 'timezone',
          label: 'Zaman Dilimi',
          filterType: 'text'
        },
        {
          id: 'image_url',
          label: 'Görsel URL',
          filterType: 'text'
        },
        {
          id: 'notes',
          label: 'Notlar',
          filterType: 'text'
        },
        {
          id: 'address_line1',
          label: 'Adres 1',
          filterType: 'text'
        },
        {
          id: 'address_line2',
          label: 'Adres 2',
          filterType: 'text'
        },
        {
          id: 'city',
          label: 'Şehir',
          filterType: 'text'
        },
        {
          id: 'state_region',
          label: 'Eyalet/Bölge',
          filterType: 'text'
        },
        {
          id: 'postal_code',
          label: 'Posta Kodu',
          filterType: 'text'
        },
        {
          id: 'country_code',
          label: 'Ülke (ISO-2)',
          filterType: 'text'
        },
        {
          id: 'is_active',
          label: 'Aktif',
          filterType: 'boolean'
        },
        {
          id: 'created_by',
          label: 'Oluşturan',
          filterType: 'text'
        },
        {
          id: 'updated_by',
          label: 'Güncelleyen',
          filterType: 'text'
        },
        {
          id: 'created_at',
          label: 'Oluşturma',
          filterType: 'date'
        },
        {
          id: 'updated_at',
          label: 'Güncelleme',
          filterType: 'date'
        },
        {
          id: 'deleted_at',
          label: 'Silinme',
          filterType: 'date'
        },
      ];

      const defaultVisible = ['actions', 'id', 'name', 'company_id', 'status', 'start_date', 'end_date', 'is_active'];

      const LS_KEYS = {
        visibleCols: 'projects.visibleCols',
        filters: 'projects.filters',
        sort: 'projects.sort',
        widths: 'projects.widths',
        page: 'projects.page',
        limit: 'projects.limit'
      };

      // DOM
      const table = document.getElementById('projectsTable');
      const thead = document.getElementById('tableHead');
      const tbody = document.getElementById('tableBody');
      const headerRow = document.getElementById('headerRow');
      const filtersRow = document.getElementById('filtersRow');
      const colGroup = document.getElementById('colGroup');
      const columnPanel = document.getElementById('columnPanel');
      const columnCheckboxes = document.getElementById('columnCheckboxes');
      const footerStats = document.getElementById('footerStats');
      const pager = document.getElementById('pager');
      const pageIndicator = document.getElementById('pageIndicator');
      const pageSizeSelect = document.getElementById('pageSizeSelect');

      // State
      let state = {
        visibleCols: loadVisibleCols(),
        filters: loadFilters(),
        sort: loadSort(),
        widths: loadWidths(),
        page: loadPage(),
        limit: loadLimit()
      };

      // UI events
      document.getElementById('toggleColumnPanel').addEventListener('click', () => columnPanel.hidden = !columnPanel.hidden);

      document.getElementById('resetView').addEventListener('click', () => {
        state.visibleCols = [...defaultVisible];
        state.filters = {};
        state.sort = {
          by: 'id',
          dir: 'asc'
        };
        state.widths = {};
        state.page = 1;
        state.limit = 50;
        saveAll();
        buildColumnPanel();
        rebuildHeadAndCols();
        render();
      });

      const tmplBtn = document.getElementById('downloadTemplate');
      if (tmplBtn && !tmplBtn.dataset.bound) {
        tmplBtn.addEventListener('click', downloadTemplateXlsx);
        tmplBtn.dataset.bound = '1';
      }

      const exportBtn = document.getElementById('exportExcel');
      if (exportBtn && !exportBtn.dataset.bound) {
        exportBtn.addEventListener('click', exportAllToXlsx);
        exportBtn.dataset.bound = '1';
      }

      document.getElementById('uploadFile').addEventListener('change', handleUpload);

      // Upload submit form guard + AJAX submit
      const uploadForm = document.getElementById('uploadSubmitForm');
      if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const payloadEl = document.getElementById('uploadPayload');
          const val = payloadEl?.value || '';
          if (!val) {
            alert('Yüklenecek veri yok. Lütfen önce bir dosya seçin.');
            return;
          }
          let parsed;
          try {
            parsed = JSON.parse(val);
            if (!parsed || !Array.isArray(parsed.rows)) {
              alert('Geçersiz payload formatı.');
              return;
            }
          } catch {
            alert('Payload JSON değil.');
            return;
          }

          // CSRF token
          const headers = {
            'Accept': 'application/json'
          };
          const metaCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const formCsrf = uploadForm.querySelector('input[name="_token"]')?.value;
          const csrf = metaCsrf || formCsrf;
          if (csrf) headers['X-CSRF-TOKEN'] = csrf;

          // FormData ile gönder
          const formData = new FormData();
          if (formCsrf) formData.append('_token', formCsrf);
          formData.append('payload', val);

          try {
            const resp = await fetch(uploadForm.getAttribute('action') || '/projects/bulk-upload', {
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

            alert('Yükleme başarılı.');
            const modalEl = document.getElementById('uploadModal');
            const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
            modal.hide();
            // location.reload();
          } catch (err) {
            alert('İstek gönderilemedi: ' + (err?.message || err));
          }
        });
      }

      // Pager
      pager.addEventListener('click', (e) => {
        const a = e.target.closest('a[data-page]');
        if (!a) return;
        e.preventDefault();
        const action = a.dataset.page;
        const {
          totalPages
        } = currentTotals();
        if (action === 'first') state.page = 1;
        else if (action === 'prev') state.page = Math.max(1, state.page - 1);
        else if (action === 'next') state.page = Math.min(totalPages, state.page + 1);
        else if (action === 'last') state.page = totalPages;
        savePageAndLimit();
        render();
      });

      initPageSizeSelect();
      pageSizeSelect.addEventListener('change', () => {
        state.limit = Number(pageSizeSelect.value) || 50;
        state.page = 1;
        savePageAndLimit();
        render();
      });

      // Başlat
      buildColumnPanel();
      rebuildHeadAndCols();
      render();

      // Storage helpers
      function loadVisibleCols() {
        try {
          const raw = localStorage.getItem(LS_KEYS.visibleCols);
          const arr = raw ? JSON.parse(raw) : null;
          let cols = arr && Array.isArray(arr) ? arr : defaultVisible;
          cols = cols.filter(id => columns.some(c => c.id === id));
          cols = ['actions', ...cols.filter(id => id !== 'actions')];
          return cols;
        } catch {
          return [...defaultVisible];
        }
      }

      function loadFilters() {
        try {
          return JSON.parse(localStorage.getItem(LS_KEYS.filters) || '{}');
        } catch {
          return {};
        }
      }

      function loadSort() {
        try {
          const s = JSON.parse(localStorage.getItem(LS_KEYS.sort) || '{"by":"id","dir":"asc"}');
          return columns.find(c => c.id === s.by) ? s : {
            by: 'id',
            dir: 'asc'
          };
        } catch {
          return {
            by: 'id',
            dir: 'asc'
          };
        }
      }

      function loadWidths() {
        try {
          return JSON.parse(localStorage.getItem(LS_KEYS.widths) || '{}') || {};
        } catch {
          return {};
        }
      }

      function loadPage() {
        try {
          const fromQs = Number(new URLSearchParams(location.search).get('page'));
          if (!Number.isNaN(fromQs) && fromQs > 0) return fromQs;
          const p = Number(localStorage.getItem(LS_KEYS.page));
          return !Number.isNaN(p) && p > 0 ? p : 1;
        } catch {
          return 1;
        }
      }

      function loadLimit() {
        try {
          const fromQs = Number(new URLSearchParams(location.search).get('limit'));
          if (!Number.isNaN(fromQs) && fromQs > 0) return fromQs;
          const l = Number(localStorage.getItem(LS_KEYS.limit));
          return !Number.isNaN(l) && l > 0 ? l : 50;
        } catch {
          return 50;
        }
      }

      function saveAll() {
        localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));
        localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));
        localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));
        localStorage.setItem(LS_KEYS.widths, JSON.stringify(state.widths));
        savePageAndLimit();
      }

      function savePageAndLimit() {
        localStorage.setItem(LS_KEYS.page, String(state.page));
        localStorage.setItem(LS_KEYS.limit, String(state.limit));
      }

      // Yapı koruması
      function normalizeTableStructure() {
        if (!table.tHead) {
          const th = document.createElement('thead');
          table.insertBefore(th, table.firstChild);
        }
        const th = table.tHead;
        if (headerRow.parentNode !== th) th.appendChild(headerRow);
        if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);
        if (!table.tBodies || table.tBodies.length === 0) {
          table.appendChild(tbody);
        }
        const tb = table.tBodies[0];
        tb.querySelectorAll('tr#headerRow, tr#filtersRow').forEach(tr => {
          if (tr.id === 'filtersRow') th.insertBefore(tr, th.firstChild);
          else th.appendChild(tr);
        });
        Array.from(th.children).forEach(node => {
          if (node.nodeName !== 'TR') node.remove();
        });
      }

      function visibleColumns() {
        return state.visibleCols.map(id => columns.find(c => c.id === id)).filter(Boolean);
      }

      // Kolon paneli
      function buildColumnPanel() {
        columnCheckboxes.innerHTML = '';
        columns.forEach(col => {
          if (col.id === 'actions') return;
          const id = `colchk_${col.id}`;
          const wrap = document.createElement('label');
          wrap.className = 'form-check d-flex align-items-center gap-2';
          wrap.innerHTML = `
<input class="form-check-input" type="checkbox" id="${id}" ${state.visibleCols.includes(col.id)?'checked':''}>
<span class="form-check-label">${col.label}</span>
`;
          wrap.querySelector('input').addEventListener('change', (e) => {
            const on = e.target.checked;
            if (on) {
              if (!state.visibleCols.includes(col.id)) state.visibleCols.push(col.id);
            } else {
              state.visibleCols = state.visibleCols.filter(x => x !== col.id);
            }
            state.visibleCols = ['actions', ...state.visibleCols.filter(x => x !== 'actions')];
            saveAll();
            rebuildHeadAndCols();
            state.page = 1;
            savePageAndLimit();
            render();
          });
          columnCheckboxes.appendChild(wrap);
        });
      }

      function initPageSizeSelect() {
        const opts = Array.from(pageSizeSelect.options).map(o => Number(o.value));
        if (!opts.includes(state.limit)) {
          const opt = document.createElement('option');
          opt.value = String(state.limit);
          opt.textContent = state.limit + ' satır';
          pageSizeSelect.appendChild(opt);
        }
        pageSizeSelect.value = String(state.limit);
      }

      // Başlık ve filtreleri kur
      function rebuildHeadAndCols() {
        normalizeTableStructure();
        const th = table.tHead;
        if (headerRow.parentNode !== th) th.appendChild(headerRow);
        if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);

        headerRow.replaceChildren();
        filtersRow.replaceChildren();
        colGroup.replaceChildren();

        const cols = visibleColumns();

        cols.forEach((col) => {
          const c = document.createElement('col');
          c.dataset.colId = col.id;
          const savedW = Number(state.widths[col.id] || 0);
          if (savedW > 0) c.style.width = savedW + 'px';
          colGroup.appendChild(c);

          const thCell = document.createElement('th');
          thCell.textContent = col.label;
          if (col.className) thCell.className = col.className;
          if (!col.isAction) {
            thCell.classList.add('sortable');
            if (state.sort.by === col.id) thCell.dataset.sort = state.sort.dir;
            thCell.addEventListener('click', (ev) => {
              if ((ev.target).classList?.contains('col-resizer')) return;
              toggleSort(col.id);
            });
          } else {
            thCell.classList.add('col-actions');
          }

          const resizer = document.createElement('div');
          resizer.className = 'col-resizer';
          resizer.addEventListener('mousedown', (e) => startResize(e, col.id));
          thCell.appendChild(resizer);
          headerRow.appendChild(thCell);

          const tf = document.createElement('th');
          tf.className = 'filter-cell';
          if (!col.isAction) {
            const ft = col.filterType || 'text';
            if (ft === 'date') {
              tf.innerHTML = `
<div class="d-grid" style="grid-template-columns:1fr 1fr; gap:.25rem;">
  <input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="from" value="${escapeAttr(state.filters[col.id]?.from || '')}">
  <input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="to" value="${escapeAttr(state.filters[col.id]?.to || '')}">
</div>
`;
            } else if (ft === 'boolean') {
              const cur = state.filters[col.id]?.val ?? '';
              tf.innerHTML = `
<select class="form-select form-select-sm" data-key="${col.id}" data-kind="bool">
  <option value="" ${cur===''?'selected':''}>— Tümü —</option>
  <option value="true" ${cur==='true'?'selected':''}>true</option>
  <option value="false" ${cur==='false'?'selected':''}>false</option>
</select>
`;
            } else {
              const cur = state.filters[col.id]?.val ?? '';
              tf.innerHTML = `<input type="text" class="form-control form-control-sm" placeholder="Ara..." data-key="${col.id}" data-kind="text" value="${escapeAttr(cur)}">`;
            }
          }
          filtersRow.appendChild(tf);
        });

        // Filtre olayları
        filtersRow.querySelectorAll('input[data-kind="text"]').forEach(inp => {
          inp.addEventListener('input', debounce(() => {
            const key = inp.dataset.key;
            state.filters[key] = {
              val: inp.value
            };
            state.page = 1;
            saveAll();
            render();
          }, 200));
        });
        filtersRow.querySelectorAll('select[data-kind="bool"]').forEach(sel => {
          sel.addEventListener('change', () => {
            const key = sel.dataset.key;
            state.filters[key] = {
              val: sel.value
            };
            state.page = 1;
            saveAll();
            render();
          });
        });
        filtersRow.querySelectorAll('input[data-kind="from"], input[data-kind="to"]').forEach(inp => {
          inp.addEventListener('change', () => {
            const key = inp.dataset.key,
              kind = inp.dataset.kind;
            state.filters[key] = state.filters[key] || {};
            state.filters[key][kind] = inp.value;
            state.page = 1;
            saveAll();
            render();
          });
        });

        normalizeTableStructure();
      }

      // Sütun genişliği
      function startResize(e, colId) {
        e.preventDefault();
        const startX = e.pageX;
        const colEl = [...colGroup.children].find(c => c.dataset.colId === colId);
        const startWidth = (colEl && colEl.style.width) ? parseInt(colEl.style.width, 10) : getComputedWidth(colId);
        const min = colId === 'actions' ? 40 : (colId === 'id' ? 56 : 70);
        const th = [...headerRow.children][visibleColumns().findIndex(c => c.id === colId)];
        if (th) th.classList.add('resizing');
        const resizer = th?.querySelector('.col-resizer');
        if (resizer) resizer.classList.add('active');

        function onMouseMove(ev) {
          const dx = ev.pageX - startX;
          const newW = Math.max(min, Math.round(startWidth + dx));
          if (colEl) colEl.style.width = newW + 'px';
        }

        function onMouseUp() {
          document.removeEventListener('mousemove', onMouseMove);
          document.removeEventListener('mouseup', onMouseUp);
          if (th) th.classList.remove('resizing');
          if (resizer) resizer.classList.remove('active');
          const finalW = Math.max(min, parseInt((colEl?.style.width || startWidth), 10));
          state.widths[colId] = finalW;
          saveAll();
        }
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
      }

      function getComputedWidth(colId) {
        const idx = visibleColumns().findIndex(c => c.id === colId);
        if (idx < 0) return 100;
        const th = headerRow.children[idx];
        if (!th) return 100;
        return Math.round(th.getBoundingClientRect().width);
      }

      // Sıralama
      function toggleSort(colId) {
        if (state.sort.by === colId) state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
        else {
          state.sort.by = colId;
          state.sort.dir = 'asc';
        }
        state.page = 1;
        saveAll();
        rebuildHeadAndCols();
        render();
      }

      // Filtre & sıralama uygulama
      function applyFilters(rows) {
        return rows.filter(r => {
          for (const col of columns) {
            const f = state.filters[col.id];
            if (!f) continue;

            if (col.filterType === 'text') {
              const needle = (f.val ?? '').trim().toLowerCase();
              if (needle) {
                const hay = String(r[col.id] ?? '').toLowerCase();
                if (!hay.includes(needle)) return false;
              }
            } else if (col.filterType === 'boolean') {
              const val = String(f.val ?? '');
              if (val) {
                const raw = r[col.id];
                const boolVal = (typeof raw === 'boolean') ? raw : ['1', 'true', 'on', 'yes', 'evet'].includes(String(raw).toLowerCase());
                if ((val === 'true') !== boolVal) return false;
              }
            } else if (col.filterType === 'date') {
              const from = f.from ? Date.parse(f.from) : null;
              const to = f.to ? Date.parse(f.to) : null;
              const v = Date.parse(r[col.id] ?? '');
              if (from && !(v >= from)) return false;
              if (to && !(v <= to)) return false;
            }
          }
          return true;
        });
      }

      function applySort(rows) {
        const col = columns.find(c => c.id === state.sort.by);
        if (!col) return rows;
        const dir = state.sort.dir === 'asc' ? 1 : -1;
        return [...rows].sort((a, b) => {
          const avRaw = a[col.id];
          const bvRaw = b[col.id];
          if (col.id === 'id' || col.id === 'budget') {
            const av = Number(avRaw ?? 0),
              bv = Number(bvRaw ?? 0);
            return (av < bv ? -1 : av > bv ? 1 : 0) * dir;
          }
          const av = String(avRaw ?? '').toLowerCase();
          const bv = String(bvRaw ?? '').toLowerCase();
          if (av < bv) return -1 * dir;
          if (av > bv) return 1 * dir;
          return 0;
        });
      }

      // Toplamlar ve sayfa bilgisi
      function currentTotals() {
        const filtered = applyFilters(DATA);
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / Math.max(1, state.limit)));
        if (state.page > totalPages) state.page = totalPages;
        return {
          filtered,
          total,
          totalPages
        };
      }

      // Render
      function render() {
        normalizeTableStructure();

        const cols = visibleColumns();
        const {
          filtered,
          total,
          totalPages
        } = currentTotals();
        const sorted = applySort(filtered);
        const startIndex = (Math.max(1, state.page) - 1) * Math.max(1, state.limit);
        const pageRows = sorted.slice(startIndex, startIndex + state.limit);

        tbody.innerHTML = '';

        for (const r of pageRows) {
          const tr = document.createElement('tr');
          for (const col of cols) {
            const td = document.createElement('td');
            if (col.className) td.className = col.className;

            if (col.isAction) {
              td.innerHTML = `
<div class="btn-group" role="group" aria-label="İşlemler">
  <a class="btn btn-light btn-icon" href="/projects/edit?id=${escapeAttr(String(r.id ?? r.uuid ?? ''))}" title="Düzenle">
    <i class="bi bi-pencil"></i>
  </a>
  <form method="post" action="/projects/delete" onsubmit="return confirm('Silinsin mi?')" style="display:inline;">
    <input type="hidden" name="uuid" value="${escapeAttr(String(r.uuid ?? ''))}">
    <button class="btn btn-light btn-icon text-danger" type="submit" title="Sil">
      <i class="bi bi-trash"></i>
    </button>
  </form>
</div>
`;
            } else if (col.id === 'image_url') {
              const url = String(r.image_url ?? '');
              td.innerHTML = url ? `<a href="${escapeAttr(url)}" target="_blank" rel="noopener" class="truncate">${escapeHtml(url)}</a>` : '';
            } else if (col.id === 'budget') {
              const raw = r.budget == null ? '' : String(r.budget);
              td.textContent = raw;
            } else if (col.id === 'is_active') {
              const raw = r.is_active;
              const isTrue = typeof raw === 'boolean' ? raw : ['1', 'true', 'on', 'yes', 'evet'].includes(String(raw).toLowerCase());
              td.innerHTML = `<span class="badge ${isTrue ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'}">${isTrue}</span>`;
            } else {
              td.textContent = r[col.id] == null ? '' : String(r[col.id]);
            }
            tr.appendChild(td);
          }
          tbody.appendChild(tr);
        }

        headerRow.querySelectorAll('th.sortable').forEach(th => th.removeAttribute('data-sort'));
        const idx = visibleColumns().findIndex(c => c.id === state.sort.by);
        if (idx >= 0) headerRow.children[idx].dataset.sort = state.sort.dir;

        footerStats.innerHTML = `Toplam: <strong>${total}</strong> | Sayfa: ${state.page}/${totalPages}`;
        pageIndicator.textContent = `${state.page} / ${totalPages}`;
        setPagerState(totalPages);

        updateQueryParams({
          page: state.page,
          limit: state.limit
        });

        normalizeTableStructure();
      }

      function setPagerState(totalPages) {
        const firstLi = pager.querySelector('a[data-page="first"]').closest('.page-item');
        const prevLi = pager.querySelector('a[data-page="prev"]').closest('.page-item');
        const nextLi = pager.querySelector('a[data-page="next"]').closest('.page-item');
        const lastLi = pager.querySelector('a[data-page="last"]').closest('.page-item');

        if (state.page <= 1) {
          firstLi.classList.add('disabled');
          prevLi.classList.add('disabled');
        } else {
          firstLi.classList.remove('disabled');
          prevLi.classList.remove('disabled');
        }
        if (state.page >= totalPages) {
          nextLi.classList.add('disabled');
          lastLi.classList.add('disabled');
        } else {
          nextLi.classList.remove('disabled');
          lastLi.classList.remove('disabled');
        }
      }

      // Export / Template / Upload
      async function loadXlsxIfNeeded() {
        if (window.XLSX) return;
        const base = (document.querySelector('base')?.getAttribute('href') || '/').replace(/\/+$/, '') + '/';
        const candidates = [
          'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js',
          'https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js',
          base + 'assets/vendor/xlsx/xlsx.full.min.js'
        ];
        let lastErr;
        for (const src of candidates) {
          try {
            await new Promise((resolve, reject) => {
              const s = document.createElement('script');
              s.src = src;
              s.async = true;
              s.onload = () => resolve();
              s.onerror = () => reject(new Error('XLSX yüklenemedi: ' + src));
              document.head.appendChild(s);
            });
            if (window.XLSX) return;
          } catch (e) {
            lastErr = e;
          }
        }
        throw lastErr || new Error('XLSX yüklenemedi');
      }

      async function readXlsx(file) {
        if (!window.XLSX) {
          await loadXlsxIfNeeded();
          if (!window.XLSX) throw new Error('XLSX kütüphanesi yüklenemedi');
        }
        const data = await file.arrayBuffer();
        const wb = XLSX.read(data, {
          type: 'array'
        });
        const sheetName = wb.SheetNames.includes('Data') ? 'Data' : wb.SheetNames[0];
        const ws = wb.Sheets[sheetName];
        let aoa = XLSX.utils.sheet_to_json(ws, {
          header: 1,
          blankrows: false
        });
        if (!aoa || aoa.length === 0) return [];
        aoa = aoa.map(row => row.map(v => v == null ? '' : String(v)));
        return aoa;
      }

      async function downloadTemplateXlsx() {
        try {
          await loadXlsxIfNeeded();
        } catch (e) {
          alert('Şablon için XLSX kütüphanesi yüklenemedi.\nDetay: ' + (e?.message || e));
          return;
        }

        const headers = allFields.map(c => c.id);

        const sample1 = {
          id: '',
          uuid: '',
          created_at: '',
          updated_at: '',
          deleted_at: '',
          name: 'Yeni CRM Projesi',
          short_name: 'CRM',
          project_path: '/var/www/crm',
          company_id: '1',
          start_date: '2025-01-01',
          end_date: '2025-06-30',
          budget: '250000',
          status: 'active',
          currency_code: 'TRY',
          timezone: 'Europe/Istanbul',
          image_url: '',
          notes: 'Örnek not',
          address_line1: 'Adres satırı 1',
          address_line2: '',
          city: 'İstanbul',
          state_region: 'Kadıköy',
          postal_code: '34710',
          country_code: 'TR',
          is_active: 'true',
          created_by: '',
          updated_by: ''
        };
        const sample2 = {
          id: '',
          uuid: '',
          created_at: '',
          updated_at: '',
          deleted_at: '',
          name: 'E-ticaret Uygulaması',
          short_name: 'SHOP',
          project_path: '/srv/shop',
          company_id: '2',
          start_date: '2025-02-15',
          end_date: '2025-10-15',
          budget: '500000',
          status: 'planned',
          currency_code: 'USD',
          timezone: 'Europe/Istanbul',
          image_url: 'https://example.com/banner.png',
          notes: '',
          address_line1: 'Adres 1',
          address_line2: 'Adres 2',
          city: 'Ankara',
          state_region: 'Çankaya',
          postal_code: '06680',
          country_code: 'TR',
          is_active: 'true',
          created_by: '',
          updated_by: ''
        };

        const dataRows = [
          headers,
          headers.map(h => sample1[h] ?? ''),
          headers.map(h => sample2[h] ?? '')
        ];

        const wsData = XLSX.utils.aoa_to_sheet(dataRows);
        wsData['!cols'] = headers.map(h => ({
          wch: Math.min(Math.max(String(h).length + 2, 12), 30)
        }));

        const wsInfo = XLSX.utils.aoa_to_sheet([
          ['Kılavuz'],
          ['status: active | planned | in_progress | on_hold | completed | cancelled'],
          ['boolean: true | false (is_active)'],
          ['created_at/updated_at/deleted_at: ISO datetime (opsiyonel)'],
          ['country_code: ISO-2 (TR, US, vb.)'],
          ['Not: Data sheet’teki başlıkları değiştirmeyin.']
        ]);

        const wsDict = XLSX.utils.aoa_to_sheet([
          ['status'],
          ['active'],
          ['planned'],
          ['in_progress'],
          ['on_hold'],
          ['completed'],
          ['cancelled']
        ]);

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, wsData, 'Data');
        XLSX.utils.book_append_sheet(wb, wsInfo, 'Açıklamalar');
        XLSX.utils.book_append_sheet(wb, wsDict, 'Sözlükler');

        XLSX.writeFile(wb, 'projects_template_full.xlsx', {
          compression: true
        });
      }

      async function exportAllToXlsx() {
        try {
          await loadXlsxIfNeeded();
        } catch (e) {
          alert('Excel’e aktarmak için XLSX kütüphanesi yüklenemedi.\nDetay: ' + (e?.message || e));
          return;
        }

        const exportCols = allFields;
        const headers = exportCols.map(c => c.id);
        const rows = DATA.map(r => exportCols.map(c => r[c.id] == null ? '' : String(r[c.id])));
        const aoa = [headers, ...rows];

        const ws = XLSX.utils.aoa_to_sheet(aoa);
        ws['!cols'] = headers.map(h => ({
          wch: Math.min(Math.max(String(h).length + 2, 12), 30)
        }));

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Projects');

        XLSX.writeFile(wb, 'projects_full_export.xlsx', {
          compression: true
        });
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

          if (!rows || rows.length === 0) {
            throw new Error('Boş dosya veya okunamadı.');
          }

          const preview = buildPreviewTable(rows);
          document.getElementById('uploadPreview').innerHTML = preview.html;
          document.getElementById('uploadPayload').value = JSON.stringify({
            rows
          });

          const modalEl = document.getElementById('uploadModal');
          const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
          modal.show();

          input.value = '';
        } catch (err) {
          alert('Dosya okunamadı: ' + (err?.message || err));
          if (input) input.value = '';
        }
      }

      function buildPreviewTable(rows) {
        const maxRows = Math.min(rows.length, 10);
        const headers = rows[0] || [];
        const bodyRows = rows.slice(1, maxRows);

        const html = `
<div class="small text-muted mb-2">
  Toplam satır: ${Math.max(0, rows.length - 1)} (başlık hariç). Önizleme ilk ${Math.max(0, maxRows - 1)} satır.
</div>
<div class="table-responsive">
  <table class="table table-sm table-bordered">
    <thead><tr>${headers.map(h=>`<th>${escapeHtml(h)}</th>`).join('')}</tr></thead>
    <tbody>
      ${bodyRows.map(r=>`<tr>${headers.map((_,i)=>`<td>${escapeHtml(String(r[i] ?? ''))}</td>`).join('')}</tr>`).join('')}
    </tbody>
  </table>
</div>
<div class="mt-2 small text-secondary">
  Beklenen başlıklar: ${escapeHtml(allFields.map(f=>f.id).join(', '))}.
  • status: active | planned | in_progress | on_hold | completed | cancelled
  • boolean: true | false (is_active)
  • created_at/updated_at/deleted_at: ISO datetime (opsiyonel)
  • country_code: ISO-2 (TR, US, vb.)
</div>
`;
        return {
          html
        };
      }

      function escapeHtml(str) {
        return String(str)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#39;');
      }

      function escapeAttr(str) {
        return escapeHtml(str).replaceAll('\n', ' ');
      }

      function debounce(fn, wait) {
        let t;
        return (...a) => {
          clearTimeout(t);
          t = setTimeout(() => fn(...a), wait);
        };
      }

      function updateQueryParams(obj) {
        try {
          const url = new URL(window.location.href);
          if (obj.page) url.searchParams.set('page', String(obj.page));
          if (obj.limit) url.searchParams.set('limit', String(obj.limit));
          window.history.replaceState({}, '', url.toString());
        } catch {
          /* no-op */ }
      }

    })();
  </script>

  <?php $pageScripts = ob_get_clean(); ?>

<?php else: ?>

  <div class="alert alert-light border d-flex align-items-center" role="alert">
    <i class="bi bi-inboxes me-2"></i> Hiç proje yok.
  </div>

<?php endif; ?>

<?php

$content = ob_get_clean();

include __DIR__ . '/../layouts/base.php';

?>