<?php

use App\Core\Helpers;

ob_start();

?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Kullanıcılar') ?></h1>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="/users/create"><i class="bi bi-plus-lg me-1"></i>Yeni Kullanıcı</a>
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

<?php $usersJson = json_encode($users ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>

<?php if (!empty($users)): ?>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-wrap p-2 pt-0">

      <div id="columnPanel" class="column-panel card card-body py-2 mb-2" hidden>
        <strong class="mb-2">Görünen Kolonlar</strong>
        <div id="columnCheckboxes" class="columns-grid"></div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="usersTable">
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
              <form id="uploadSubmitForm" method="post" action="/users/bulk-upload" enctype="multipart/form-data" class="ms-auto">
                <input type="hidden" name="payload" id="uploadPayload">
                <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Sunucuya Yükle</button>
              </form>
            </div>
          </div>
        </div>
      </div>

<style>
/* Kolon paneli checkbox grid */
.columns-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(180px, 1fr));
  gap: .35rem .75rem;
}
@media (min-width: 576px) { .columns-grid { grid-template-columns: repeat(3, minmax(180px, 1fr)); } }
@media (min-width: 768px) { .columns-grid { grid-template-columns: repeat(4, minmax(180px, 1fr)); } }
@media (min-width: 992px) { .columns-grid { grid-template-columns: repeat(5, minmax(180px, 1fr)); } }
@media (min-width: 1200px){ .columns-grid { grid-template-columns: repeat(6, minmax(180px, 1fr)); } }

/* Tablo: varsayılan akış, hizalama bozulmasın */
#usersTable {
  table-layout: auto;
  border-collapse: separate;
  border-spacing: 0;
}

/* Thead ve hücreleri – display değiştirmeyin */
#usersTable thead { vertical-align: bottom; }
#usersTable thead th {
  position: relative;
  background-clip: padding-box;
  white-space: nowrap;
}

/* 1. satır (filtreler): alt çizgi yok, minimal padding */
#usersTable thead tr#filtersRow th {
  padding: .25rem .5rem;
  border-bottom: 0 !important;
}

/* 2. satır (başlıklar): tek bir alt çizgi */
#usersTable thead tr#headerRow th {
  padding-top: .25rem;
  padding-bottom: .4rem;
  border-bottom: 1px solid var(--bs-border-color) !important;
  vertical-align: bottom;
}

/* Filtre kontrolleri tam genişlikte */
#usersTable thead .filter-cell > * {
  display: block;
  width: 100%;
  max-width: 100%;
  margin: 0;
}
#usersTable thead input.form-control-sm,
#usersTable thead select.form-select-sm {
  min-height: 32px;
  line-height: 1.2;
}

/* İşlemler sütunu daha dar */
#usersTable th.col-actions { padding-left: .25rem; padding-right: .25rem; }

/* Kolon yeniden boyutlandırma sapı (kullanıyorsanız) */
#usersTable th .col-resizer {
  position: absolute; top: 0; right: 0; width: 10px; height: 100%;
  cursor: col-resize; user-select: none; -webkit-user-select: none;
}
#usersTable th.resizing,
#usersTable th .col-resizer.active {
  background-image: linear-gradient(to bottom, rgba(45,108,223,.15), rgba(45,108,223,.15));
  background-repeat: no-repeat; background-position: right center; background-size: 2px 100%;
}

/* Buton ve sort */
.btn-icon {
  --btn-size: 28px; width: var(--btn-size); height: var(--btn-size);
  padding: 0 !important; display: inline-flex; align-items: center; justify-content: center; border-radius: .25rem;
}
.btn-icon.btn-light { border: 1px solid var(--bs-border-color); }
.btn-icon i { font-size: 14px; }

#usersTable td .btn-group { gap: 4px; }
#usersTable td .btn-group .btn { border-width: 1px; }

#usersTable thead th.sortable { cursor: pointer; }
#usersTable thead th.sortable[data-sort="asc"]::after { content: " ↑"; opacity: .6; }
#usersTable thead th.sortable[data-sort="desc"]::after { content: " ↓"; opacity: .6; }
</style>

<?php ob_start(); ?>

<script>
(function() {
  // Veri
  const DATA = <?= $usersJson ?: '[]'; ?>;

  // Alanlar
  const allFields = [
    { id: 'id', label: 'ID' }, { id: 'uuid', label: 'UUID' },
    { id: 'created_at', label: 'Oluşturma' }, { id: 'updated_at', label: 'Güncelleme' }, { id: 'deleted_at', label: 'Silinme' }, { id: 'last_login_at', label: 'Son Giriş' },
    { id: 'company_id', label: 'Firma ID' }, { id: 'company_name', label: 'Firma' },
    { id: 'first_name', label: 'Ad' }, { id: 'middle_name', label: 'İkinci Ad' }, { id: 'last_name', label: 'Soyad' },
    { id: 'gender', label: 'Cinsiyet' }, { id: 'birth_date', label: 'Doğum Tarihi' },
    { id: 'nationality_code', label: 'Uyruk (ISO-2)' }, { id: 'place_of_birth', label: 'Doğum Yeri' },
    { id: 'language', label: 'Dil (IETF, tr-TR)' }, { id: 'timezone', label: 'Zaman Dilimi' },
    { id: 'phone', label: 'Telefon' }, { id: 'secondary_phone', label: 'İkinci Telefon' }, { id: 'email', label: 'E-posta' },
    { id: 'national_id', label: 'TC/Ulusal ID' }, { id: 'passport_no', label: 'Pasaport No' },
    { id: 'marital_status', label: 'Medeni Hali' },
    { id: 'address_line1', label: 'Adres 1' }, { id: 'address_line2', label: 'Adres 2' }, { id: 'city', label: 'Şehir' }, { id: 'state_region', label: 'Eyalet/Bölge' },
    { id: 'postal_code', label: 'Posta Kodu' }, { id: 'country_code', label: 'Ülke (ISO-2)' },
    { id: 'notes', label: 'Notlar' }, { id: 'is_active', label: 'Aktif' },
  ];

  // Kolonlar
  const columns = [
    { id: 'actions', label: 'İşlemler', isAction: true, className: 'col-actions' },
    { id: 'id', label: 'ID', filterType: 'text', className: 'text-end' },
    { id: 'uuid', label: 'UUID', filterType: 'text' },
    { id: 'company_name', label: 'Firma', filterType: 'text' },
    { id: 'company_id', label: 'Firma ID', filterType: 'text' },
    { id: 'first_name', label: 'Ad', filterType: 'text' },
    { id: 'middle_name', label: 'İkinci Ad', filterType: 'text' },
    { id: 'last_name', label: 'Soyad', filterType: 'text' },
    { id: 'email', label: 'E-posta', filterType: 'text' },
    { id: 'phone', label: 'Telefon', filterType: 'text' },
    { id: 'secondary_phone', label: 'İkinci Telefon', filterType: 'text' },
    { id: 'gender', label: 'Cinsiyet', filterType: 'text' },
    { id: 'birth_date', label: 'Doğum Tarihi', filterType: 'date' },
    { id: 'marital_status', label: 'Medeni Hali', filterType: 'text' },
    { id: 'is_active', label: 'Aktif', filterType: 'boolean' },
    { id: 'national_id', label: 'Ulusal ID', filterType: 'text' },
    { id: 'passport_no', label: 'Pasaport No', filterType: 'text' },
    { id: 'nationality_code', label: 'Uyruk (ISO-2)', filterType: 'text' },
    { id: 'place_of_birth', label: 'Doğum Yeri', filterType: 'text' },
    { id: 'language', label: 'Dil (IETF)', filterType: 'text' },
    { id: 'timezone', label: 'Zaman Dilimi', filterType: 'text' },
    { id: 'address_line1', label: 'Adres 1', filterType: 'text' },
    { id: 'address_line2', label: 'Adres 2', filterType: 'text' },
    { id: 'city', label: 'Şehir', filterType: 'text' },
    { id: 'state_region', label: 'Eyalet/Bölge', filterType: 'text' },
    { id: 'postal_code', label: 'Posta Kodu', filterType: 'text' },
    { id: 'country_code', label: 'Ülke (ISO-2)', filterType: 'text' },
    { id: 'notes', label: 'Notlar', filterType: 'text' },
    { id: 'created_at', label: 'Oluşturma', filterType: 'text' },
    { id: 'updated_at', label: 'Güncelleme', filterType: 'text' },
    { id: 'deleted_at', label: 'Silinme', filterType: 'text' },
    { id: 'last_login_at', label: 'Son Giriş', filterType: 'text' },
  ];

  const defaultVisible = ['actions','id','first_name','last_name','company_name','email','phone','is_active'];

  const LS_KEYS = {
    visibleCols: 'users.visibleCols',
    filters: 'users.filters',
    sort: 'users.sort',
    widths: 'users.widths'
  };

  // DOM
  const table = document.getElementById('usersTable');
  const thead = document.getElementById('tableHead');
  const tbody = document.getElementById('tableBody');
  const headerRow = document.getElementById('headerRow');
  const filtersRow = document.getElementById('filtersRow');
  const colGroup = document.getElementById('colGroup');
  const columnPanel = document.getElementById('columnPanel');
  const columnCheckboxes = document.getElementById('columnCheckboxes');

  // State
  let state = {
    visibleCols: loadVisibleCols(),
    filters: loadFilters(),
    sort: loadSort(),
    widths: loadWidths()
  };

  // UI butonları
  document.getElementById('toggleColumnPanel').addEventListener('click', () => columnPanel.hidden = !columnPanel.hidden);
  document.getElementById('resetView').addEventListener('click', () => {
    state.visibleCols = [...defaultVisible];
    state.filters = {};
    state.sort = { by: 'id', dir: 'asc' };
    state.widths = {};
    saveAll(); buildColumnPanel(); rebuildHeadAndCols(); render();
  });
  document.getElementById('downloadTemplate').addEventListener('click', downloadTemplate);
  document.getElementById('exportExcel').addEventListener('click', exportAllToExcel);
  document.getElementById('uploadFile').addEventListener('change', handleUpload);

  // Başlat
  buildColumnPanel();
  rebuildHeadAndCols();
  render();

  // Helpers: storage
  function loadVisibleCols() {
    try {
      const raw = localStorage.getItem(LS_KEYS.visibleCols);
      const arr = raw ? JSON.parse(raw) : null;
      let cols = arr && Array.isArray(arr) ? arr : defaultVisible;
      cols = cols.filter(id => columns.some(c => c.id === id));
      cols = ['actions', ...cols.filter(id => id !== 'actions')];
      return cols;
    } catch { return [...defaultVisible]; }
  }
  function loadFilters() { try { return JSON.parse(localStorage.getItem(LS_KEYS.filters) || '{}'); } catch { return {}; } }
  function loadSort() {
    try {
      const s = JSON.parse(localStorage.getItem(LS_KEYS.sort) || '{"by":"id","dir":"asc"}');
      return columns.find(c=>c.id===s.by) ? s : { by:'id', dir:'asc' };
    } catch { return { by:'id', dir:'asc' }; }
  }
  function loadWidths() { try { return JSON.parse(localStorage.getItem(LS_KEYS.widths) || '{}') || {}; } catch { return {}; } }
  function saveAll() {
    localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));
    localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));
    localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));
    localStorage.setItem(LS_KEYS.widths, JSON.stringify(state.widths));
  }

  // Yapı koruması
 function normalizeTableStructure() {
  // thead yoksa oluştur
  if (!table.tHead) {
    const th = document.createElement('thead');
    table.insertBefore(th, table.firstChild);
  }
  const th = table.tHead;

  // header/filters TR’lerini kesin olarak THEAD içine al
  if (headerRow.parentNode !== th) th.appendChild(headerRow);
  if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);

  // tbody yoksa oluştur
  if (!table.tBodies || table.tBodies.length === 0) {
    table.appendChild(tbody);
  }
  const tb = table.tBodies[0];

  // Yanlışlıkla TBODY’ye sızmış thead satırlarını geri taşı
  tb.querySelectorAll('tr#headerRow, tr#filtersRow').forEach(tr => {
    if (tr.id === 'filtersRow') th.insertBefore(tr, th.firstChild);
    else th.appendChild(tr);
  });

  // THEAD içinde sadece TR olsun; başka node varsa kaldır
  Array.from(th.children).forEach(node => {
    if (node.nodeName !== 'TR') node.remove();
  });
}

  // Kolon görünürleri
  function visibleColumns() { return state.visibleCols.map(id => columns.find(c => c.id === id)).filter(Boolean); }

  // Kolon paneli
  function buildColumnPanel() {
    columnCheckboxes.innerHTML = '';
    columns.forEach(col => {
      if (col.id === 'actions') return;
      const id = `colchk_${col.id}`;
      const wrap = document.createElement('label');
      wrap.className = 'form-check d-flex align-items-center gap-2';
      wrap.innerHTML = `
        <input class="form-check-input" type="checkbox" id="${id}" ${state.visibleCols.includes(col.id) ? 'checked' : ''}>
        <span class="form-check-label">${col.label}</span>
      `;
      wrap.querySelector('input').addEventListener('change', (e) => {
        const on = e.target.checked;
        if (on) { if (!state.visibleCols.includes(col.id)) state.visibleCols.push(col.id); }
        else { state.visibleCols = state.visibleCols.filter(x => x !== col.id); }
        state.visibleCols = ['actions', ...state.visibleCols.filter(x => x !== 'actions')];
        saveAll();
        rebuildHeadAndCols();
        render();
      });
      columnCheckboxes.appendChild(wrap);
    });
  }

  // Başlık ve filtreleri kur
  function rebuildHeadAndCols() {
  normalizeTableStructure(); // önce yapı sabitlensin

  // her ihtimale karşı tekrar sabitle
  const th = table.tHead;
  if (headerRow.parentNode !== th) th.appendChild(headerRow);
  if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);

  headerRow.replaceChildren();   // innerHTML='' yerine
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

  // event bindingler sonra...
  // ...

  normalizeTableStructure(); // rebuild sonunda tekrar sabitle
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
      const finalW = Math.max(min, parseInt((colEl?.style.width || startWidth) ,10));
      state.widths[colId] = finalW;
      saveAll();
    }
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
  }
  function getComputedWidth(colId) {
    const idx = visibleColumns().findIndex(c=>c.id===colId);
    if (idx < 0) return 100;
    const th = headerRow.children[idx];
    if (!th) return 100;
    return Math.round(th.getBoundingClientRect().width);
  }

  // Sıralama
  function toggleSort(colId) {
    if (state.sort.by === colId) state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
    else { state.sort.by = colId; state.sort.dir = 'asc'; }
    saveAll();
    rebuildHeadAndCols();
    render();
  }

  // Filtre + sıralama
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
            const boolVal = (typeof raw === 'boolean') ? raw : ['1','true','on','yes','evet'].includes(String(raw).toLowerCase());
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
    return [...rows].sort((a,b) => {
      const av = String(a[col.id] ?? '').toLowerCase();
      const bv = String(b[col.id] ?? '').toLowerCase();
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  // Render
  function render() {
    normalizeTableStructure();
    const cols = visibleColumns();
    const filtered = applyFilters(DATA);
    const sorted = applySort(filtered);

    tbody.innerHTML = '';
    for (const r of sorted) {
      const tr = document.createElement('tr');
      for (const col of cols) {
        const td = document.createElement('td');
        if (col.className) td.className = col.className;
        if (col.isAction) {
          td.innerHTML = `
            <div class="btn-group" role="group" aria-label="İşlemler">
              <a class="btn btn-light btn-icon" href="/users/edit?id=${escapeAttr(String(r.id ?? r.uuid ?? ''))}" title="Düzenle">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" action="/users/delete" onsubmit="return confirm('Silinsin mi?')" style="display:inline;">
                <input type="hidden" name="uuid" value="${escapeAttr(String(r.uuid ?? ''))}">
                <button class="btn btn-light btn-icon text-danger" type="submit" title="Sil">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>`;
        } else if (col.id === 'email') {
          const email = String(r.email ?? '');
          td.innerHTML = email ? `<a href="mailto:${escapeAttr(email)}" class="text-decoration-none">${escapeHtml(email)}</a>` : '';
        } else if (col.id === 'is_active') {
          const raw = r.is_active;
          const isActive = typeof raw === 'boolean' ? raw : ['1','true','on','yes','evet'].includes(String(raw).toLowerCase());
          td.innerHTML = `<span class="badge ${isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'}">${isActive}</span>`;
        } else {
          td.textContent = r[col.id] == null ? '' : String(r[col.id]);
        }
        tr.appendChild(td);
      }
      tbody.appendChild(tr);
    }

    // Sort göstergesi
    headerRow.querySelectorAll('th.sortable').forEach(th => th.removeAttribute('data-sort'));
    const idx = visibleColumns().findIndex(c => c.id === state.sort.by);
    if (idx >= 0) headerRow.children[idx].dataset.sort = state.sort.dir;
    normalizeTableStructure(); // render sonunda da
  }

  // İndir / Şablon / Upload
  function exportAllToExcel() {
    const exportCols = allFields;
    const headers = exportCols.map(c => c.id);
    const rows = DATA.map(r => exportCols.map(c => formatForCsv(c.id, r[c.id])));
    const csv = toCsv([headers, ...rows]);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    triggerDownload(blob, 'users_full_export.csv');
  }
  function downloadTemplate() {
    const headers = allFields.map(c => c.id);
    const sample1 = { id:'', uuid:'', created_at:'', updated_at:'', deleted_at:'', last_login_at:'', company_id:'', company_name:'Örnek Firma AŞ', first_name:'Ahmet', middle_name:'', last_name:'Yılmaz', gender:'male', birth_date:'1990-01-15', phone:'5551234567', secondary_phone:'', email:'ahmet@example.com', national_id:'12345678901', passport_no:'', marital_status:'married', nationality_code:'TR', place_of_birth:'İstanbul', timezone:'Europe/Istanbul', language:'tr-TR', address_line1:'Mah. Cad. No:1', address_line2:'Daire 5', city:'İstanbul', state_region:'Kadıköy', postal_code:'34710', country_code:'TR', notes:'Not örneği', is_active:'true' };
    const sample2 = { id:'', uuid:'', created_at:'', updated_at:'', deleted_at:'', last_login_at:'', company_id:'', company_name:'Başka Firma Ltd', first_name:'Ayşe', middle_name:'Nur', last_name:'Demir', gender:'female', birth_date:'1992-07-03', phone:'5329876543', secondary_phone:'', email:'ayse@example.com', national_id:'', passport_no:'U1234567', marital_status:'single', nationality_code:'TR', place_of_birth:'Ankara', timezone:'Europe/Istanbul', language:'tr-TR', address_line1:'Sokak 10', address_line2:'', city:'Ankara', state_region:'Çankaya', postal_code:'06680', country_code:'TR', notes:'', is_active:'false' };
    const rows = [headers, ...[sample1, sample2].map(s => headers.map(h => formatForCsv(h, s[h])))];
    const csv = toCsv(rows);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    triggerDownload(blob, 'users_template_full.csv');
  }
  async function handleUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    const name = file.name.toLowerCase();
    try {
      let rows;
      if (name.endsWith('.csv')) rows = await readCsv(file);
      else rows = await tryReadAsTextCsv(file);
      if (!rows || rows.length === 0) throw new Error('Boş dosya veya okunamadı.');
      const preview = buildPreviewTable(rows);
      document.getElementById('uploadPreview').innerHTML = preview.html;
      document.getElementById('uploadPayload').value = JSON.stringify({ rows });
      const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
      modal.show();
    } catch (err) {
      alert('Dosya okunamadı: ' + (err?.message || err));
      e.target.value = '';
    }
  }

  // CSV yardımcıları ve genel yardımcılar
  function readCsv(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onerror = () => reject(new Error('Okuma hatası'));
      reader.onload = () => { try { resolve(parseCsv(String(reader.result))); } catch (e) { reject(e); } };
      reader.readAsText(file, 'utf-8');
    });
  }
  function parseCsv(text) {
    const rows = []; let row = [], val = '', inQuotes = false;
    for (let i = 0; i < text.length; i++) {
      const ch = text[i];
      if (inQuotes) {
        if (ch === '"') { if (text[i+1] === '"') { val += '"'; i++; } else { inQuotes = false; } }
        else { val += ch; }
      } else {
        if (ch === '"') inQuotes = true;
        else if (ch === ',') { row.push(val); val = ''; }
        else if (ch === '\r') { /* skip */ }
        else if (ch === '\n') { row.push(val); rows.push(row); row = []; val = ''; }
        else { val += ch; }
      }
    }
    if (val.length || row.length) { row.push(val); rows.push(row); }
    return rows;
  }
  function tryReadAsTextCsv(file) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onerror = () => resolve([['Okuma hatası']]);
      reader.onload = () => {
        const text = String(reader.result || '');
        if (text.includes(',') || text.includes(';')) resolve(parseCsv(text));
        else resolve([['Uyarı: XLSX istemci tarafında parse edilmedi. CSV kullanın ya da XLSX desteği ekleyin.']]);
      };
      reader.readAsText(file);
    });
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
        • gender: male|female|nonbinary|unknown
        • marital_status: single|married|divorced|widowed|other
        • birth_date: YYYY-MM-DD
        • created_at/updated_at/deleted_at/last_login_at: ISO datetime (opsiyonel)
        • is_active: true|false
        • company_id: firma kimliği (tercihen bu alanı kullanın)
        • company_name: sağlanırsa company_id bulunamazsa fallback amaçlı kullanılabilir
      </div>
    `;
    return { html };
  }

  function toCsv(rows) { return rows.map(row => row.map(csvEscape).join(',')).join('\r\n'); }
  function csvEscape(v) { const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replaceAll('"','""')}"` : s; }
  function triggerDownload(blob, filename) { const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); setTimeout(()=>URL.revokeObjectURL(a.href),1000); }

  function escapeHtml(str) { return String(str).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;'); }
  function escapeAttr(str) { return escapeHtml(str).replaceAll('\n',' '); }
  function debounce(fn, wait) { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; }

  function formatForCsv(field, value) {
    if (value == null) return '';
    const v = value;
    if (field === 'is_active') {
      if (typeof v === 'boolean') return v ? 'true' : 'false';
      const s = String(v).toLowerCase();
      return ['1','true','yes','on','evet'].includes(s) ? 'true'
           : (['0','false','no','off','hayir','hayır'].includes(s) ? 'false' : String(v));
    }
    if (['birth_date'].includes(field)) {
      const d = new Date(v);
      if (!isNaN(d)) return d.toISOString().slice(0,10);
      const s = String(v);
      return /^\d{4}-\d{2}-\d{2}$/.test(s) ? s : s;
    }
    if (['created_at','updated_at','deleted_at','last_login_at'].includes(field)) {
      const d = new Date(v);
      if (!isNaN(d)) return d.toISOString();
      return String(v);
    }
    return String(v);
  }
})();
</script>




<?php $pageScripts = ob_get_clean(); ?>

<?php else: ?>
<div class="alert alert-light border d-flex align-items-center" role="alert">
  <i class="bi bi-people me-2"></i> Hiç kullanıcı yok.
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>