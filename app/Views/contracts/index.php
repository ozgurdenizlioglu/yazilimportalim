<?php

use App\Core\Helpers;

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">

  <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Sözleşmeler') ?></h1>

  <div class="d-flex flex-wrap gap-2">

    <a class="btn btn-primary" href="/contracts/create"><i class="bi bi-plus-lg me-1"></i>Yeni Sözleşme</a>

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

<?php $contractsJson = json_encode($contracts ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>

<?php if (!empty($contracts)): ?>

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
          <table class="table table-hover align-middle mb-0" id="contractsTable">
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

    #contractsTable {
      table-layout: auto;
      border-collapse: separate;
      border-spacing: 0;
    }

    #contractsTable thead {
      vertical-align: bottom;
    }

    #contractsTable thead th {
      position: relative;
      background-clip: padding-box;
      white-space: nowrap;
    }

    #contractsTable thead tr#filtersRow th {
      padding: .25rem .5rem;
      border-bottom: 0 !important;
    }

    #contractsTable thead tr#headerRow th {
      padding-top: .25rem;
      padding-bottom: .4rem;
      border-bottom: 1px solid var(--bs-border-color) !important;
      vertical-align: bottom;
    }

    #contractsTable thead .filter-cell>* {
      display: block;
      width: 100%;
      max-width: 100%;
      margin: 0;
    }

    #contractsTable thead input.form-control-sm,
    #contractsTable thead select.form-select-sm {
      min-height: 32px;
      line-height: 1.2;
    }

    #contractsTable th.col-actions {
      padding-left: .25rem;
      padding-right: .25rem;
    }

    #contractsTable th .col-resizer {
      position: absolute;
      top: 0;
      right: 0;
      width: 10px;
      height: 100%;
      cursor: col-resize;
      user-select: none;
      -webkit-user-select: none;
    }

    #contractsTable th.resizing,
    #contractsTable th .col-resizer.active {
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

    #contractsTable td .btn-group {
      gap: 4px;
    }

    #contractsTable td .btn-group .btn {
      border-width: 1px;
    }

    #contractsTable thead th.sortable {
      cursor: pointer;
    }

    #contractsTable thead th.sortable[data-sort="asc"]::after {
      content: " ↑";
      opacity: .6;
    }

    #contractsTable thead th.sortable[data-sort="desc"]::after {
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

  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

  <script src="/js/contract-template-improved.js"></script>

  <script>
    (function() {

      // Helper function to generate contract title dynamically
      function generateContractTitle(contract) {
        if (!contract) return '';

        function extractProjectCode(text) {
          if (!text) return 'XXXXXX';
          text = text.toUpperCase();
          text = text.replace(/[^A-Z0-9\s]/g, '');
          text = text.trim();
          if (!text) return 'XXXXXX';
          const words = text.split(/\s+/).filter(w => w.length > 0);
          if (words.length === 0) return 'XXXXXX';
          if (words.length === 1) {
            return (words[0] + 'XXXXXX').substring(0, 6);
          }
          const code = words[0].substring(0, 3) + words[1].substring(0, 3);
          return (code + 'XXXXXX').substring(0, 6);
        }

        function extractCode(text, length) {
          if (!text) return 'X'.repeat(length);
          text = text.toUpperCase();
          text = text.replace(/[^A-Z0-9]/g, '');
          if (!text) return 'X'.repeat(length);
          return (text + 'X'.repeat(length)).substring(0, length);
        }

        function formatDateForTitle(dateStr) {
          if (!dateStr) return '00000000';
          try {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return '00000000';
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}${month}${day}`;
          } catch (e) {
            return '00000000';
          }
        }

        const projectName = contract.project_name || 'PROJECT';
        const subject = contract.subject || '';
        const subcontractorName = contract.subcontractor_company_name || contract.contractor_name || '';
        const contractDate = contract.contract_date || '';

        const prj = extractProjectCode(projectName);
        const subj = extractCode(subject, 8);
        const cont = extractCode(subcontractorName, 8);
        const dateFormatted = formatDateForTitle(contractDate);

        return `SZL_${prj}_${subj}_${cont}_${dateFormatted}`;
      }

      const DATA = <?= $contractsJson ?: '[]'; ?>;

      // ALWAYS generate contract titles dynamically - overwrites any stored values
      DATA.forEach(contract => {
        contract.contract_title = generateContractTitle(contract);
      });

      // Tüm alanlar (export/template için)

      const allFields = [

        {
          id: 'id',
          label: 'ID'
        },

        {
          id: 'uuid',
          label: 'UUID'
        },

        {
          id: 'contractor_company_id',
          label: 'İşveren ID'
        },

        {
          id: 'contractor_name',
          label: 'İşveren'
        },

        {
          id: 'subcontractor_company_id',
          label: 'Yüklenici ID'
        },

        {
          id: 'subcontractor_name',
          label: 'Yüklenici'
        },

        {
          id: 'contract_date',
          label: 'Sözleşme Tarihi'
        },

        {
          id: 'end_date',
          label: 'Bitiş Tarihi'
        },

        {
          id: 'subject',
          label: 'Konu'
        },

        {
          id: 'project_id',
          label: 'Proje ID'
        },

        {
          id: 'project_name',
          label: 'Proje'
        },

        {
          id: 'discipline_id',
          label: 'Disiplin'
        },

        {
          id: 'branch_id',
          label: 'Alt Disiplin'
        },

        {
          id: 'contract_title',
          label: 'Başlık'
        },

        {
          id: 'amount',
          label: 'Tutar'
        },

        {
          id: 'currency_id',
          label: 'Para Birimi ID'
        },

        {
          id: 'currency_code',
          label: 'Para Birimi'
        },

        {
          id: 'amount_in_words',
          label: 'Tutar Yazıyla'
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

      ];

      // Görünür kolonlar ve filtrelenebilir liste

      const columns = [

        {
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
          id: 'contract_title',
          label: 'Başlık',
          filterType: 'text'
        },

        {
          id: 'contract_date',
          label: 'Sözleşme Tarihi',
          filterType: 'date'
        },

        {
          id: 'end_date',
          label: 'Bitiş Tarihi',
          filterType: 'date'
        },

        {
          id: 'contractor_name',
          label: 'İşveren',
          filterType: 'text'
        },

        {
          id: 'subcontractor_name',
          label: 'Yüklenici',
          filterType: 'text'
        },

        {
          id: 'project_name',
          label: 'Proje',
          filterType: 'text'
        },

        {
          id: 'amount',
          label: 'Tutar',
          filterType: 'text'
        },

        {
          id: 'currency_code',
          label: 'Para Birimi',
          filterType: 'text'
        },

        {
          id: 'subject',
          label: 'Konu',
          filterType: 'text'
        },

        {
          id: 'is_active',
          label: 'Aktif',
          filterType: 'boolean'
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

        // İsterseniz ek kolonları da buraya ekleyebilirsiniz:

        // { id:'employer_company_id', label:'İşveren ID', filterType:'text' },

        // { id:'contractor_company_id', label:'Yüklenici ID', filterType:'text' },

        // { id:'discipline_id', label:'Disiplin', filterType:'text' },

        // { id:'branch_id', label:'Alt Disiplin', filterType:'text' },

        // { id:'amount_in_words', label:'Tutar Yazıyla', filterType:'text' },

        // { id:'project_id', label:'Proje ID', filterType:'text' },

        // { id:'currency_id', label:'Para Birimi ID', filterType:'text' },

      ];

      const defaultVisible = ['actions', 'id', 'project_name', 'subcontractor_name', 'contract_date', 'amount', 'currency_code', 'is_active'];

      const LS_KEYS = {

        visibleCols: 'contracts.visibleCols',

        filters: 'contracts.filters',

        sort: 'contracts.sort',

        widths: 'contracts.widths',

        page: 'contracts.page',

        limit: 'contracts.limit'

      };

      const table = document.getElementById('contractsTable');

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

      let state = {

        visibleCols: loadVisibleCols(),

        filters: loadFilters(),

        sort: loadSort(),

        widths: loadWidths(),

        page: loadPage(),

        limit: loadLimit()

      };

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
        tmplBtn.addEventListener('click', function(e) {
          e.preventDefault();
          console.log('Download template clicked');
          downloadTemplateXlsxImproved().catch(err => console.error('Error:', err));
        });
        tmplBtn.dataset.bound = '1';
      }

      const exportBtn = document.getElementById('exportExcel');

      if (exportBtn && !exportBtn.dataset.bound) {
        exportBtn.addEventListener('click', function(e) {
          e.preventDefault();
          console.log('Export excel clicked');
          exportAllToXlsx().catch(err => console.error('Error:', err));
        });
        exportBtn.dataset.bound = '1';
      }

      // Note: Upload handlers are now in the global script section below
      // This allows uploading to work even when there are no contracts

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

      buildColumnPanel();

      rebuildHeadAndCols();

      console.log('Initialization:', {
        'DATA.length': DATA.length,
        'columns.length': columns.length,
        'state.visibleCols': state.visibleCols,
        'defaultVisible': defaultVisible,
        'table exists': !!table,
        'thead exists': !!thead,
        'tbody exists': !!tbody
      });

      if (DATA.length === 0) {
        console.warn('WARNING: DATA array is empty!');
      } else {
        console.log('First contract:', DATA[0]);
      }

      render();

      console.log('After render - tbody has', tbody.children.length, 'rows');
      if (tbody.children.length === 0) {
        console.warn('WARNING: No rows rendered in tbody!');
      }

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
        // Always start with no filters to show all data
        // Filters will be preserved during the session but cleared on page refresh
        return {};
      }

      function saveFiltersToStorage() {
        // Save filters to localStorage for this session only
        localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));
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
          const qs = Number(new URLSearchParams(location.search).get('page'));
          if (!Number.isNaN(qs) && qs > 0) return qs;
          const p = Number(localStorage.getItem(LS_KEYS.page));
          return !Number.isNaN(p) && p > 0 ? p : 1;
        } catch {
          return 1;
        }
      }

      function loadLimit() {
        try {
          const qs = Number(new URLSearchParams(location.search).get('limit'));
          if (!Number.isNaN(qs) && qs > 0) return qs;
          const l = Number(localStorage.getItem(LS_KEYS.limit));
          return !Number.isNaN(l) && l > 0 ? l : 50;
        } catch {
          return 50;
        }
      }

      function saveAll() {
        localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));
        saveFiltersToStorage(); // Use the new storage function
        localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));
        localStorage.setItem(LS_KEYS.widths, JSON.stringify(state.widths));
        savePageAndLimit();
      }

      function savePageAndLimit() {
        localStorage.setItem(LS_KEYS.page, String(state.page));
        localStorage.setItem(LS_KEYS.limit, String(state.limit));
      }

      function visibleColumns() {
        return state.visibleCols.map(id => columns.find(c => c.id === id)).filter(Boolean);
      }

      function buildColumnPanel() {

        columnCheckboxes.innerHTML = '';

        columns.forEach(col => {

          if (col.id === 'actions') return;

          const id = `colchk_${col.id}`;

          const wrap = document.createElement('label');

          wrap.className = 'form-check d-flex align-items-center gap-2';

          wrap.innerHTML = `<input class="form-check-input" type="checkbox" id="${id}" ${state.visibleCols.includes(col.id)?'checked':''}><span class="form-check-label">${col.label}</span>`;

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

      function normalizeTableStructure() {

        if (!table.tHead) {
          const th = document.createElement('thead');
          table.insertBefore(th, table.firstChild);
        }

        const th = table.tHead;

        if (headerRow.parentNode !== th) th.appendChild(headerRow);

        if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);

        if (!table.tBodies || table.tBodies.length === 0) table.appendChild(tbody);

        const tb = table.tBodies[0];

        tb.querySelectorAll('tr#headerRow, tr#filtersRow').forEach(tr => {

          if (tr.id === 'filtersRow') th.insertBefore(tr, th.firstChild);

          else th.appendChild(tr);

        });

        Array.from(th.children).forEach(node => {
          if (node.nodeName !== 'TR') node.remove();
        });

      }

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

              tf.innerHTML = `<div class="d-grid" style="grid-template-columns:1fr 1fr; gap:.25rem;">

<input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="from" value="${escapeAttr(state.filters[col.id]?.from || '')}">

<input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="to" value="${escapeAttr(state.filters[col.id]?.to || '')}">

</div>`;

            } else if (ft === 'boolean') {

              const cur = state.filters[col.id]?.val ?? '';

              tf.innerHTML = `<select class="form-select form-select-sm" data-key="${col.id}" data-kind="bool">

<option value="" ${cur===''?'selected':''}>— Tümü —</option>

<option value="true" ${cur==='true'?'selected':''}>true</option>

<option value="false" ${cur==='false'?'selected':''}>false</option>

</select>`;

            } else {

              const cur = state.filters[col.id]?.val ?? '';

              tf.innerHTML = `<input type="text" class="form-control form-control-sm" placeholder="Ara..." data-key="${col.id}" data-kind="text" value="${escapeAttr(cur)}">`;

            }

          }

          filtersRow.appendChild(tf);

        });

        // Filtre eventleri

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

          const avRaw = a[col.id],
            bvRaw = b[col.id];

          if (col.id === 'id') {

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

<a class="btn btn-light btn-icon" href="/contracts/edit?id=${escapeAttr(String(r.id ?? r.uuid ?? ''))}" title="Düzenle"><i class="bi bi-pencil"></i></a>

<form method="post" action="/contracts/delete" onsubmit="return confirm('Silinsin mi?')" style="display:inline;">

<input type="hidden" name="uuid" value="${escapeAttr(String(r.uuid ?? ''))}">

<button class="btn btn-light btn-icon text-danger" type="submit" title="Sil"><i class="bi bi-trash"></i></button>

</form>

</div>`;

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

      async function loadXlsxIfNeeded() {
        // XLSX is already loaded at the top of the page via CDN
        // Just wait for it to be available
        const timeout = 10000;
        const start = Date.now();

        while (!window.XLSX && (Date.now() - start) < timeout) {
          await new Promise(resolve => setTimeout(resolve, 100));
        }

        if (!window.XLSX) {
          throw new Error('XLSX kütüphanesi yüklenemedi. Lütfen sayfayı yenileyin.');
        }
      }

      async function downloadTemplateXlsx() {
        console.log('downloadTemplateXlsx called');

        try {
          await loadXlsxIfNeeded();
        } catch (e) {
          alert('XLSX kütüphanesi yüklenemedi: ' + (e?.message || e));
          return;
        }

        try {
          // Fetch template data from backend (projects, disciplines, etc.)
          const response = await fetch('/contracts/template-data');
          if (!response.ok) {
            throw new Error('Template verisi alınamadı');
          }
          const templateData = await response.json();

          // Sheet 1: Data entry sheet with minimal required columns
          const dataHeaders = [
            'contract_date',
            'end_date',
            'subject',
            'project_id',
            'discipline_id',
            'branch_id',
            'contract_title',
            'amount',
            'currency_code',
            'amount_in_words'
          ];

          const sample1 = [
            '2026-01-02',
            '2026-12-31',
            'Yazılım Geliştirme',
            '1',
            '',
            '',
            '',
            '100000.00',
            'TRY',
            'Yüz bin Türk Lirası'
          ];

          const sample2 = [
            '2026-02-01',
            '2026-06-30',
            'Yazılım Bakımı',
            '2',
            '4',
            '51',
            '',
            '50000.00',
            'USD',
            'Fifty thousand US Dollars'
          ];

          const dataRows = [dataHeaders, sample1, sample2];

          const wsData = XLSX.utils.aoa_to_sheet(dataRows);

          wsData['!cols'] = dataHeaders.map(h => ({
            wch: Math.min(Math.max(String(h).length + 2, 15), 25)
          }));

          const wsInfo = XLSX.utils.aoa_to_sheet([

            ['Kılavuz'],

            ['Zorunlu alanlar: contractor_company_id, subcontractor_company_id, contract_date'],

            ['Tarih formatı: YYYY-MM-DD (contract_date, end_date)'],

            ['is_active: true|false'],

            ['Not: Data sheet’teki başlıkları değiştirmeyin.']

          ]);

          const wb = XLSX.utils.book_new();

          XLSX.utils.book_append_sheet(wb, wsData, 'Data');

          XLSX.utils.book_append_sheet(wb, wsInfo, 'Açıklamalar');

          XLSX.writeFile(wb, 'contracts_template_full.xlsx', {
            compression: true
          });

          console.log('Template downloaded successfully');
        } catch (e) {
          console.error('Template download error:', e);
          alert('Şablon indirme hatası: ' + (e?.message || e));
        }

      }

      async function exportAllToXlsx() {

        try {
          await loadXlsxIfNeeded();
        } catch (e) {
          alert('Excel’e aktarmak için XLSX kütüphanesi yüklenemedi.\nDetay: ' + (e?.message || e));
          return;
        }

        try {
          // Fetch data from backend
          const response = await fetch('/contracts/export-to-excel');
          if (!response.ok) {
            alert('Veri alınamadı: ' + response.statusText);
            return;
          }
          const result = await response.json();

          if (!result.ok) {
            alert('Hata: ' + (result.error || 'Bilinmeyen hata'));
            return;
          }

          const contracts = result.contracts || [];
          const payments = result.payments || [];

          // Helper function to generate contract title dynamically
          function generateContractTitleForExport(contract) {
            if (!contract) return '';

            function extractProjectCode(text) {
              if (!text) return 'XXXXXX';
              text = text.toUpperCase();
              text = text.replace(/[^A-Z0-9\s]/g, '');
              text = text.trim();
              if (!text) return 'XXXXXX';
              const words = text.split(/\s+/).filter(w => w.length > 0);
              if (words.length === 0) return 'XXXXXX';
              if (words.length === 1) {
                return (words[0] + 'XXXXXX').substring(0, 6);
              }
              const code = words[0].substring(0, 3) + words[1].substring(0, 3);
              return (code + 'XXXXXX').substring(0, 6);
            }

            function extractCode(text, length) {
              if (!text) return 'X'.repeat(length);
              text = text.toUpperCase();
              text = text.replace(/[^A-Z0-9]/g, '');
              if (!text) return 'X'.repeat(length);
              return (text + 'X'.repeat(length)).substring(0, length);
            }

            function formatDateForTitle(dateStr) {
              if (!dateStr) return '00000000';
              try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return '00000000';
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}${month}${day}`;
              } catch (e) {
                return '00000000';
              }
            }

            const projectName = contract.project_name || 'PROJECT';
            const subject = contract.subject || '';
            const subcontractorName = contract.subcontractor_company_name || contract.contractor_name || '';
            const contractDate = contract.contract_date || '';

            const prj = extractProjectCode(projectName);
            const subj = extractCode(subject, 8);
            const cont = extractCode(subcontractorName, 8);
            const dateFormatted = formatDateForTitle(contractDate);

            return `SZL_${prj}_${subj}_${cont}_${dateFormatted}`;
          }

          // Generate titles for all contracts
          const contractsWithTitles = contracts.map(c => ({
            ...c,
            contract_title: generateContractTitleForExport(c)
          }));

          // Sheet 1: Contracts with total amounts
          const contractHeaders = [
            'ID', 'Sözleşme Başlığı', 'Proje', 'İşveren Firma', 'Yüklenici Firma',
            'Tarih', 'Bitiş Tariması', 'Konu', 'Tutar', 'Para Birimi', 'Durum'
          ];

          const contractRows = contractsWithTitles.map(c => [
            c.id || '',
            c.contract_title || '',
            c.project_name || '',
            c.contractor_name || '',
            c.subcontractor_name || '',
            c.contract_date || '',
            c.end_date || '',
            c.subject || '',
            c.amount || '',
            c.currency_code || 'TRY',
            c.is_active ? 'Aktif' : 'Pasif'
          ]);

          const contractData = [contractHeaders, ...contractRows];
          const wsContracts = XLSX.utils.aoa_to_sheet(contractData);

          // Set column widths for contracts sheet
          wsContracts['!cols'] = [{
              wch: 8
            }, // ID
            {
              wch: 25
            }, // Başlık
            {
              wch: 20
            }, // Proje
            {
              wch: 20
            }, // İşveren
            {
              wch: 20
            }, // Yüklenici
            {
              wch: 12
            }, // Tarih
            {
              wch: 12
            }, // Bitiş
            {
              wch: 20
            }, // Konu
            {
              wch: 15
            }, // Tutar
            {
              wch: 10
            }, // Para Birimi
            {
              wch: 10
            } // Durum
          ];

          // Sheet 2: Payments (detailed rows)
          const paymentHeaders = [
            'Sözleşme ID', 'Sözleşme Başlığı', 'Toplam Tutar', 'Ödeme Türü', 'Vade Tarihi', 'Ödeme Tutarı', 'Para Birimi'
          ];

          const paymentRows = payments.map(p => {
            // Find the corresponding contract to get the generated title
            const contract = contractsWithTitles.find(c => c.id === p.contract_id);
            return [
              p.contract_id || '',
              contract?.contract_title || p.contract_title || '',
              p.amount || '',
              getPaymentType(p.type) || p.type || '',
              p.due_date || '',
              p.payment_amount || '',
              p.currency || 'TRY'
            ];
          });

          const paymentData = [paymentHeaders, ...paymentRows];
          const wsPayments = XLSX.utils.aoa_to_sheet(paymentData);

          // Set column widths for payments sheet
          wsPayments['!cols'] = [{
              wch: 12
            }, // Sözleşme ID
            {
              wch: 25
            }, // Başlık
            {
              wch: 15
            }, // Toplam
            {
              wch: 15
            }, // Ödeme Türü
            {
              wch: 12
            }, // Vade
            {
              wch: 15
            }, // Tutar
            {
              wch: 10
            } // Para Birimi
          ];

          // Create workbook with both sheets
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, wsContracts, 'Sözleşmeler');
          XLSX.utils.book_append_sheet(wb, wsPayments, 'Ödemeler');

          // Add ListObject tables to both sheets for filtering and sorting
          // Table must include header row (row 0) and all data rows
          if (contractRows.length > 0) {
            // Range: from A1 (header) to last column and last data row
            const contractRange = XLSX.utils.encode_range({
              s: {
                r: 0,
                c: 0
              },
              e: {
                r: contractRows.length, // contractRows.length gives us the correct end row (0-indexed header + data rows)
                c: contractHeaders.length - 1
              }
            });

            if (!wsContracts['!tables']) wsContracts['!tables'] = [];
            wsContracts['!tables'].push({
              name: 'ContractTable',
              ref: contractRange,
              totalsRow: false,
              tableStyleInfo: {
                name: 'TableStyleMedium2',
                showFirstColumn: false,
                showLastColumn: false,
                showRowStripes: true,
                showColumnStripes: false
              }
            });
          }

          if (paymentRows.length > 0) {
            // Range: from A1 (header) to last column and last data row
            const paymentRange = XLSX.utils.encode_range({
              s: {
                r: 0,
                c: 0
              },
              e: {
                r: paymentRows.length, // paymentRows.length gives us the correct end row
                c: paymentHeaders.length - 1
              }
            });

            if (!wsPayments['!tables']) wsPayments['!tables'] = [];
            wsPayments['!tables'].push({
              name: 'PaymentTable',
              ref: paymentRange,
              totalsRow: false,
              tableStyleInfo: {
                name: 'TableStyleMedium2',
                showFirstColumn: false,
                showLastColumn: false,
                showRowStripes: true,
                showColumnStripes: false
              }
            });
          }

          XLSX.writeFile(wb, 'sozlesmeler_' + new Date().toISOString().split('T')[0] + '.xlsx', {
            compression: true
          });

        } catch (e) {
          console.error('Export error:', e);
          alert('Dışa aktarma hatası: ' + (e?.message || e));
        }

      }

      // Upload handlers are now in the global script section
      // This allows uploading to work even when there are no contracts

      function escapeHtml(str) {
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return String(str).replace(/[&<>"']/g, m => map[m]);
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
        } catch {}
      }

      function getCurrencyCode(currencyId) {
        const currencies = {
          949: 'TRY',
          978: 'EUR',
          840: 'USD'
        };
        return currencies[currencyId] || 'TRY';
      }

      function getPaymentType(type) {
        const types = {
          'cash': 'Nakit',
          'cheque': 'Çek',
          'transfer': 'Havale/EFT',
          'BARTER': 'Takas'
        };
        return types[type] || type;
      }

    })();
  </script>

<?php else: ?>

  <div class="alert alert-light border d-flex align-items-center" role="alert">

    <i class="bi bi-inboxes me-2"></i> Hiç sözleşme yok.

  </div>

<?php endif; ?>

<!-- Upload Modal - ALWAYS AVAILABLE -->
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
        <form id="uploadSubmitForm" method="post" action="/contracts/bulk-upload" class="ms-auto">
          <input type="hidden" name="payload" id="uploadPayload">
          <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Sunucuya Yükle</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Upload Script - ALWAYS AVAILABLE -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
  // Upload helper functions - available even when no contracts
  async function loadXlsxIfNeeded() {
    if (window.XLSX) return;
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
      script.onload = () => resolve();
      script.onerror = () => reject(new Error('XLSX library load failed'));
      document.head.appendChild(script);
    });
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

  function buildPreviewTable(rows) {
    const allFields = [
      'id', 'uuid', 'contractor_company_id', 'contractor_name', 'subcontractor_company_id', 'subcontractor_name',
      'contract_date', 'end_date', 'subject', 'project_id', 'project_name', 'discipline_id', 'branch_id',
      'contract_title', 'amount', 'currency_id', 'currency_code', 'amount_in_words', 'is_active',
      'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'
    ];

    const maxRows = Math.min(rows.length, 10);
    const headers = rows[0] || [];
    const bodyRows = rows.slice(1, maxRows);

    function escapeHtml(str) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return String(str).replace(/[&<>"']/g, m => map[m]);
    }

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
Beklenen başlıklar: ${escapeHtml(allFields.join(', '))}.
• Zorunlu: contractor_company_id, subcontractor_company_id, contract_date
• is_active: true|false
• Tarih: YYYY-MM-DD
</div>`;

    return {
      html
    };
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
      if (name.endsWith('.xlsx') || name.endsWith('.xls')) rows = await readXlsx(file);
      else throw new Error('Sadece .xlsx, .xls dosyaları desteklenir.');

      if (!rows || rows.length === 0) throw new Error('Boş dosya veya okunamadı.');

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

  // Setup upload file input handler
  const uploadFileInput = document.getElementById('uploadFile');
  if (uploadFileInput) {
    uploadFileInput.addEventListener('change', handleUpload);
  }

  // Setup upload form submission handler
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
      const formCsrf = uploadForm.querySelector('input[name="_token"]')?.value;
      const csrf = metaCsrf || formCsrf;

      if (csrf) headers['X-CSRF-TOKEN'] = csrf;

      const formData = new FormData();
      if (formCsrf) formData.append('_token', formCsrf);
      formData.append('payload', val);

      try {
        const resp = await fetch(uploadForm.getAttribute('action') || '/contracts/bulk-upload', {
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

        alert('Yükleme başarılı: ' + (data?.inserted || 0) + ' sözleşme eklendi.');

        // Hide modal and redirect
        const modalEl = document.getElementById('uploadModal');
        const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
        modal.hide();

        // Reload page to show new contracts
        setTimeout(() => {
          window.location.reload();
        }, 500);

      } catch (err) {
        alert('İstek gönderilemedi: ' + (err?.message || err));
      }
    });
  }
</script>

<?php

// Layout will be included by Controller
// DO NOT include layout here - it's already being handled
// Controller's view() method handles ob_start/ob_get_clean

?>