<?php use App\Core\Helpers;

?>
<h1><?= Helpers::e($title ?? 'Firmalar') ?></h1>

<p style="margin:.5rem 0 1rem; display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
  <a class="btn" href="/firms/create">+ Yeni Firma</a>
  <button class="btn" type="button" id="toggleColumnPanel">Kolonları Yönet</button>
  <button class="btn" type="button" id="resetView">Görünümü Sıfırla</button>
  <span class="spacer"></span>
  <button class="btn" type="button" id="exportExcel">Excele Aktar</button>
  <button class="btn" type="button" id="downloadTemplate">Şablon İndir</button>
  <label class="btn file-btn">
    Upload Et
    <input type="file" id="uploadFile" accept=".xlsx,.xls,.csv" hidden>
  </label>
</p>

<?php
  $firmsJson = json_encode($firms ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<?php if (!empty($firms)): ?>
<div class="table-wrap">
  <div id="columnPanel" class="column-panel" hidden>
    <strong>Görünen Kolonlar</strong>
    <div id="columnCheckboxes" class="columns"></div>
  </div>

  <table class="table" id="firmsTable">
    <thead>
      <tr id="headerRow"></tr>
      <tr id="filtersRow"></tr>
    </thead>
    <tbody id="tableBody"></tbody>
  </table>
</div>

<!-- Upload önizleme modal -->
<div id="uploadModal" class="modal" hidden>
  <div class="modal-content">
    <div class="modal-head">
      <strong>Yükleme Önizleme</strong>
      <button class="btn ghost" id="closeUploadModal">Kapat</button>
    </div>
    <div id="uploadPreview" class="upload-preview">Dosya okunuyor…</div>
    <form id="uploadSubmitForm" method="post" action="/firms/bulk-upload" enctype="multipart/form-data" style="margin-top:.75rem;">
      <input type="hidden" name="payload" id="uploadPayload">
      <button class="btn primary" type="submit">Sunucuya Yükle</button>
    </form>
  </div>
</div>

<script>
(function() {
  const DATA = <?php echo $firmsJson ?: '[]'; ?>;

  // Export/şablon için tüm alanlar (companies şemasına göre)
  const allFields = [
    // Kimlik ve zaman damgaları
    { id: 'id', label: 'ID' },
    { id: 'uuid', label: 'UUID' },
    { id: 'created_at', label: 'Oluşturma' },
    { id: 'updated_at', label: 'Güncelleme' },
    { id: 'deleted_at', label: 'Silinme' },

    // Temel firma bilgileri
    { id: 'name', label: 'Ad' },
    { id: 'short_name', label: 'Kısa Ad' },
    { id: 'legal_type', label: 'Hukuki Tip' },
    { id: 'registration_no', label: 'Sicil No' },
    { id: 'mersis_no', label: 'MERSİS No' },
    { id: 'tax_office', label: 'Vergi Dairesi' },
    { id: 'tax_number', label: 'Vergi No' },

    // İletişim
    { id: 'email', label: 'E-posta' },
    { id: 'phone', label: 'Telefon' },
    { id: 'secondary_phone', label: 'İkinci Telefon' },
    { id: 'fax', label: 'Faks' },
    { id: 'website', label: 'Web Sitesi' },

    // Adres
    { id: 'address_line1', label: 'Adres 1' },
    { id: 'address_line2', label: 'Adres 2' },
    { id: 'city', label: 'Şehir' },
    { id: 'state_region', label: 'Eyalet/Bölge' },
    { id: 'postal_code', label: 'Posta Kodu' },
    { id: 'country_code', label: 'Ülke (ISO-2)' },

    // Koordinatlar
    { id: 'latitude', label: 'Enlem' },
    { id: 'longitude', label: 'Boylam' },

    // Diğer iş alanları
    { id: 'industry', label: 'Sektör' },
    { id: 'status', label: 'Durum' },
    { id: 'currency_code', label: 'Para Birimi' },
    { id: 'timezone', label: 'Zaman Dilimi' },

    // Bayraklar ve ek alanlar
    { id: 'vat_exempt', label: 'KDV Muaf' },
    { id: 'e_invoice_enabled', label: 'E-Fatura' },
    { id: 'logo_url', label: 'Logo URL' },
    { id: 'notes', label: 'Notlar' },
    { id: 'is_active', label: 'Aktif' },

    // İlişkilendirme
    { id: 'created_by', label: 'Oluşturan' },
    { id: 'updated_by', label: 'Güncelleyen' },
  ];

  // Tabloda gösterilecek kullanılabilir kolonlar (+ işlemler)
  const columns = [
    { id: 'actions', label: 'İşlemler', isAction: true, className: 'col-actions' },

    // Önemli alanlar
    { id: 'id', label: 'ID', filterType: 'text' , className: 'col-id' },
    { id: 'uuid', label: 'UUID', filterType: 'text', className: 'col-uuid' },
    { id: 'name', label: 'Ad', filterType: 'text' , className: 'col-name' },
    { id: 'short_name', label: 'Kısa Ad', filterType: 'text' },
    { id: 'legal_type', label: 'Hukuki Tip', filterType: 'text' },
    { id: 'registration_no', label: 'Sicil No', filterType: 'text' },
    { id: 'mersis_no', label: 'MERSİS No', filterType: 'text' },
    { id: 'tax_office', label: 'Vergi Dairesi', filterType: 'text' },
    { id: 'tax_number', label: 'Vergi No', filterType: 'text' },

    // İletişim
    { id: 'email', label: 'E-posta', filterType: 'text' , className: 'col-email' },
    { id: 'phone', label: 'Telefon', filterType: 'text' , className: 'col-phone' },
    { id: 'secondary_phone', label: 'İkinci Telefon', filterType: 'text'  },
    { id: 'fax', label: 'Faks', filterType: 'text' },
    { id: 'website', label: 'Web Sitesi', filterType: 'text', className: 'col-url' },

    // Adres
    { id: 'address_line1', label: 'Adres 1', filterType: 'text' },
    { id: 'address_line2', label: 'Adres 2', filterType: 'text' },
    { id: 'city', label: 'Şehir', filterType: 'text' },
    { id: 'state_region', label: 'Eyalet/Bölge', filterType: 'text' },
    { id: 'postal_code', label: 'Posta Kodu', filterType: 'text' },
    { id: 'country_code', label: 'Ülke (ISO-2)', filterType: 'text' },

    // Koordinatlar
    { id: 'latitude', label: 'Enlem', filterType: 'text' },
    { id: 'longitude', label: 'Boylam', filterType: 'text' },

    // Diğer
    { id: 'industry', label: 'Sektör', filterType: 'text' },
    { id: 'status', label: 'Durum', filterType: 'text' , className: 'col-status' },
    { id: 'currency_code', label: 'Para Birimi', filterType: 'text' },
    { id: 'timezone', label: 'Zaman Dilimi', filterType: 'text' },

    // Bayraklar ve meta
    { id: 'vat_exempt', label: 'KDV Muaf', filterType: 'boolean' },
    { id: 'e_invoice_enabled', label: 'E-Fatura', filterType: 'boolean' },
    { id: 'logo_url', label: 'Logo URL', filterType: 'text' },
    { id: 'notes', label: 'Notlar', filterType: 'text' },
    { id: 'is_active', label: 'Aktif', filterType: 'boolean' , className: 'col-boolean' },
    { id: 'created_by', label: 'Oluşturan', filterType: 'text' },
    { id: 'updated_by', label: 'Güncelleyen', filterType: 'text' },

    // Zaman damgaları
    { id: 'created_at', label: 'Oluşturma', filterType: 'date' },
    { id: 'updated_at', label: 'Güncelleme', filterType: 'date' },
    { id: 'deleted_at', label: 'Silinme', filterType: 'date' },
  ];

  const defaultVisible = ['actions','id','name','email','phone','status'];

  const LS_KEYS = { visibleCols: 'firms.visibleCols', filters: 'firms.filters', sort: 'firms.sort' };

  let state = {
    visibleCols: loadVisibleCols(),
    filters: loadFilters(),
    sort: loadSort()
  };

  const headerRow = document.getElementById('headerRow');
  const filtersRow = document.getElementById('filtersRow');
  const tbody = document.getElementById('tableBody');
  const columnPanel = document.getElementById('columnPanel');
  const columnCheckboxes = document.getElementById('columnCheckboxes');

  document.getElementById('toggleColumnPanel').addEventListener('click', () => columnPanel.hidden = !columnPanel.hidden);
  document.getElementById('resetView').addEventListener('click', () => {
    state.visibleCols = [...defaultVisible];
    state.filters = {};
    state.sort = { by: 'id', dir: 'asc' };
    saveAll(); buildColumnPanel(); buildHead(); render();
  });

  document.getElementById('downloadTemplate').addEventListener('click', downloadTemplate);
  const uploadInput = document.getElementById('uploadFile');
  uploadInput.addEventListener('change', handleUpload);
  document.getElementById('closeUploadModal').addEventListener('click', () => {
    document.getElementById('uploadModal').hidden = true;
    uploadInput.value = '';
  });
  document.getElementById('exportExcel').addEventListener('click', exportAllToExcel);

  buildColumnPanel();
  buildHead();
  render();

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
  function loadFilters() {
    try { return JSON.parse(localStorage.getItem(LS_KEYS.filters) || '{}'); } catch { return {}; }
  }
  function loadSort() {
    try {
      const s = JSON.parse(localStorage.getItem(LS_KEYS.sort) || '{"by":"id","dir":"asc"}');
      return columns.find(c=>c.id===s.by) ? s : { by:'id', dir:'asc' };
    } catch { return { by:'id', dir:'asc' }; }
  }
  function saveAll() {
    localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));
    localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));
    localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));
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
      wrap.className = 'colchk';
      wrap.innerHTML = `
        <input type="checkbox" id="${id}" ${state.visibleCols.includes(col.id) ? 'checked' : ''}>
        <span>${col.label}</span>
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
        buildHead();
        render();
      });
      columnCheckboxes.appendChild(wrap);
    });
  }

  function buildHead() {
    headerRow.innerHTML = '';
    filtersRow.innerHTML = '';
    const cols = visibleColumns();

    cols.forEach((col) => {
      const th = document.createElement('th');
      th.textContent = col.label;
      if (col.className) th.classList.add(col.className);
      if (!col.isAction) {
        th.classList.add('sortable');
        th.addEventListener('click', () => toggleSort(col.id));
        if (state.sort.by === col.id) th.dataset.sort = state.sort.dir;
      } else {
        if (col.isAction) {
  th.classList.add('col-actions');
}
      }
      headerRow.appendChild(th);

      const tf = document.createElement('th');
      tf.className = 'filter-cell';

      if (col.className) tf.classList.add(col.className);
      if (col.isAction) {
        tf.innerHTML = '';
      } else if (col.filterType === 'date') {
        tf.innerHTML = `
          <div class="filter-date">
            <input type="date" data-key="${col.id}" data-kind="from" value="${escapeAttr(state.filters[col.id]?.from || '')}">
            <input type="date" data-key="${col.id}" data-kind="to" value="${escapeAttr(state.filters[col.id]?.to || '')}">
          </div>
        `;
      } else if (col.filterType === 'boolean') {
        const cur = state.filters[col.id]?.val ?? '';
        tf.innerHTML = `
          <select data-key="${col.id}" data-kind="bool">
            <option value="" ${cur===''?'selected':''}>— Tümü —</option>
            <option value="true" ${cur==='true'?'selected':''}>true</option>
            <option value="false" ${cur==='false'?'selected':''}>false</option>
          </select>
        `;
      } else {
        const cur = state.filters[col.id]?.val ?? '';
        tf.innerHTML = `<input type="text" placeholder="Ara..." data-key="${col.id}" data-kind="text" value="${escapeAttr(cur)}">`;
      }
      filtersRow.appendChild(tf);
    });

    filtersRow.querySelectorAll('input[data-kind="text"]').forEach(inp => {
      inp.addEventListener('input', debounce(() => {
        const key = inp.dataset.key;
        state.filters[key] = { val: inp.value };
        saveAll(); render();
      }, 200));
    });
    filtersRow.querySelectorAll('select[data-kind="bool"]').forEach(sel => {
      sel.addEventListener('change', () => {
        const key = sel.dataset.key;
        state.filters[key] = { val: sel.value };
        saveAll(); render();
      });
    });
    filtersRow.querySelectorAll('input[data-kind="from"], input[data-kind="to"]').forEach(inp => {
      inp.addEventListener('change', () => {
        const key = inp.dataset.key, kind = inp.dataset.kind;
        state.filters[key] = state.filters[key] || {};
        state.filters[key][kind] = inp.value;
        saveAll(); render();
      });
    });
  }

  function toggleSort(colId) {
    if (state.sort.by === colId) {
      state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
    } else {
      state.sort.by = colId;
      state.sort.dir = 'asc';
    }
    saveAll();
    buildHead();
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
            const boolVal = (typeof raw === 'boolean') ? raw : ['1','true','on','yes','evet','true'].includes(String(raw).toLowerCase());
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

  function render() {
    const cols = visibleColumns();
    const filtered = applyFilters(DATA);
    const sorted = applySort(filtered);

    tbody.innerHTML = '';
    for (const r of sorted) {
      const tr = document.createElement('tr');
      for (const col of cols) {
        const td = document.createElement('td');

        if (col.className) td.classList.add(col.className);
        if (col.isAction) {
  td.classList.add('actions');          // ek olarak
  td.classList.add('col-actions');      // genişlik sınıfı da TD’ye gelsin
  td.innerHTML = `
    <a class="btn" href="/firms/edit?id=${escapeAttr(String(r.id ?? r.uuid ?? ''))}">Düzenle</a>
    <form method="post" action="/firms/delete" onsubmit="return confirm('Silinsin mi?')" style="display:inline;">
      <input type="hidden" name="uuid" value="${escapeAttr(String(r.uuid ?? ''))}">
      <button class="btn danger" type="submit">Sil</button>
    </form>
  `;
        } else if (col.id === 'email') {
          const email = String(r.email ?? '');
          td.innerHTML = email ? `<a href="mailto:${escapeAttr(email)}">${escapeHtml(email)}</a>` : '';
        } else if (col.id === 'website') {
          const url = String(r.website ?? '');
          td.innerHTML = url ? `<a href="${escapeAttr(url)}" target="_blank" rel="noopener">${escapeHtml(truncate(url, 40))}</a>` : '';
        } else if (['is_active','vat_exempt','e_invoice_enabled'].includes(col.id)) {
          const raw = r[col.id];
          const isTrue = typeof raw === 'boolean' ? raw : ['1','true','on','yes','evet','true'].includes(String(raw).toLowerCase());
          td.innerHTML = `<span class="badge ${isTrue ? 'ok' : 'no'}">${isTrue}</span>`;
        } else {
          td.textContent = r[col.id] == null ? '' : String(r[col.id]);
        }

        tr.appendChild(td);
      }
      tbody.appendChild(tr);
    }

    headerRow.querySelectorAll('th.sortable').forEach(th => th.removeAttribute('data-sort'));
    const idx = visibleColumns().findIndex(c => c.id === state.sort.by);
    if (idx >= 0) {
      const th = headerRow.children[idx];
      if (th) th.dataset.sort = state.sort.dir;
    }
  }

  // Excele Aktar: tüm satırlar + tüm alanlar (işlemler hariç)
  function exportAllToExcel() {
    const exportCols = allFields; // tamamı
    const headers = exportCols.map(c => c.id);
    const rows = DATA.map(r => exportCols.map(c => formatForCsv(c.id, r[c.id])));
    const csv = toCsv([headers, ...rows]);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    triggerDownload(blob, 'firms_full_export.csv');
  }

  // Şablon indir: tüm alan başlıkları + örnek satırlar
  function downloadTemplate() {
    const headers = allFields.map(c => c.id);

    const sample1 = {
      id: '', uuid: '', created_at: '', updated_at: '', deleted_at: '',
      name: 'Acme A.Ş.',
      short_name: 'ACME',
      legal_type: 'AS',
      registration_no: 'IST-123456',
      mersis_no: '0123001230000012',
      tax_office: 'Üsküdar VD',
      tax_number: '1234567890',
      email: 'info@acme.com',
      phone: '02123334455',
      secondary_phone: '',
      fax: '',
      website: 'https://www.acme.com',
      address_line1: 'Mah. Cad. No:1',
      address_line2: 'Kat 2',
      city: 'İstanbul',
      state_region: 'Üsküdar',
      postal_code: '34660',
      country_code: 'TR',
      latitude: '41.025',
      longitude: '29.02',
      industry: 'Manufacturing',
      status: 'active',
      currency_code: 'TRY',
      timezone: 'Europe/Istanbul',
      vat_exempt: 'false',
      e_invoice_enabled: 'true',
      logo_url: '',
      notes: 'Örnek not',
      is_active: 'true',
      created_by: '',
      updated_by: ''
    };

    const sample2 = {
      id: '', uuid: '', created_at: '', updated_at: '', deleted_at: '',
      name: 'Beta Ltd.',
      short_name: 'BETA',
      legal_type: 'LTD',
      registration_no: 'ANK-987654',
      mersis_no: '',
      tax_office: 'Çankaya VD',
      tax_number: '9988776655',
      email: 'iletisim@beta.com',
      phone: '03124567890',
      secondary_phone: '',
      fax: '',
      website: 'http://beta.com',
      address_line1: 'Sokak 10',
      address_line2: '',
      city: 'Ankara',
      state_region: 'Çankaya',
      postal_code: '06680',
      country_code: 'TR',
      latitude: '',
      longitude: '',
      industry: 'IT Services',
      status: 'prospect',
      currency_code: 'TRY',
      timezone: 'Europe/Istanbul',
      vat_exempt: 'true',
      e_invoice_enabled: 'false',
      logo_url: '',
      notes: '',
      is_active: 'false',
      created_by: '',
      updated_by: ''
    };

    const rows = [headers, ...[sample1, sample2].map(s => headers.map(h => formatForCsv(h, s[h])))];
    const csv = toCsv(rows);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    triggerDownload(blob, 'firms_template_full.csv');
  }

  function formatForCsv(field, value) {
    if (value == null) return '';
    const v = value;

    // boolean alanlar
    if (['is_active','vat_exempt','e_invoice_enabled'].includes(field)) {
      if (typeof v === 'boolean') return v ? 'true' : 'false';
      const s = String(v).toLowerCase();
      return ['1','true','yes','on','evet'].includes(s) ? 'true'
        : (['0','false','no','off','hayir','hayır'].includes(s) ? 'false' : String(v));
    }

    // tarih alanları (created/updated/deleted)
    if (['created_at','updated_at','deleted_at'].includes(field)) {
      const d = new Date(v);
      if (!isNaN(d)) return d.toISOString();
      return String(v);
    }

    return String(v);
  }

  function toCsv(rows) {
    return rows.map(row => row.map(csvEscape).join(',')).join('\r\n');
  }

  function csvEscape(v) {
    const s = String(v ?? '');
    if (/[",\n]/.test(s)) return `"${s.replaceAll('"','""')}"`;
    return s;
  }

  function triggerDownload(blob, filename) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  // Upload: basit CSV okuyucu (XLSX için SheetJS eklenebilir)
  async function handleUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    const name = file.name.toLowerCase();
    try {
      let rows;
      if (name.endsWith('.csv')) {
        rows = await readCsv(file);
      } else {
        rows = await tryReadAsTextCsv(file);
      }
      if (!rows || rows.length === 0) throw new Error('Boş dosya veya okunamadı.');
      const preview = buildPreviewTable(rows);
      document.getElementById('uploadPreview').innerHTML = preview.html;
      document.getElementById('uploadPayload').value = JSON.stringify({ rows });
      document.getElementById('uploadModal').hidden = false;
    } catch (err) {
      alert('Dosya okunamadı: ' + (err?.message || err));
      e.target.value = '';
    }
  }

  function readCsv(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onerror = () => reject(new Error('Okuma hatası'));
      reader.onload = () => {
        try {
          const text = reader.result;
          const rows = parseCsv(String(text));
          resolve(rows);
        } catch (e) { reject(e); }
      };
      reader.readAsText(file, 'utf-8');
    });
  }

  function parseCsv(text) {
    const rows = [];
    let row = [], val = '', inQuotes = false;
    for (let i = 0; i < text.length; i++) {
      const ch = text[i];
      if (inQuotes) {
        if (ch === '"') {
          if (text[i+1] === '"') { val += '"'; i++; }
          else { inQuotes = false; }
        } else {
          val += ch;
        }
      } else {
        if (ch === '"') { inQuotes = true; }
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
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onerror = () => reject(new Error('Okuma hatası'));
      reader.onload = () => {
        const text = String(reader.result || '');
        if (text.includes(',') || text.includes(';')) {
          resolve(parseCsv(text));
        } else {
          const note = [['Uyarı: XLSX istemci tarafında parse edilmedi. CSV kullanın ya da XLSX desteği için SheetJS ekleyelim.']];
          resolve(note);
        }
      };
      reader.readAsText(file);
    });
  }

  function buildPreviewTable(rows) {
    const maxRows = Math.min(rows.length, 10);
    const headers = rows[0] || [];
    const bodyRows = rows.slice(1, maxRows);
    const html = `
      <div style="font-size:.9rem; color:#555; margin-bottom:.5rem;">
        Toplam satır: ${Math.max(0, rows.length - 1)} (başlık hariç). Önizleme ilk ${Math.max(0, maxRows - 1)} satır.
      </div>
      <div class="table-wrap">
        <table class="table small">
          <thead><tr>${headers.map(h=>`<th>${escapeHtml(h)}</th>`).join('')}</tr></thead>
          <tbody>
            ${bodyRows.map(r=>`<tr>${headers.map((_,i)=>`<td>${escapeHtml(String(r[i] ?? ''))}</td>`).join('')}</tr>`).join('')}
          </tbody>
        </table>
      </div>
      <div style="margin-top:.5rem; font-size:.85rem; color:#666;">
        Beklenen başlıklar: ${escapeHtml(allFields.map(f=>f.id).join(', '))}.
        - status: active|prospect|lead|suspended|inactive
        - boolean alanlar: true|false (is_active, vat_exempt, e_invoice_enabled)
        - created_at/updated_at/deleted_at: ISO datetime (opsiyonel)
        - country_code: ISO-2 (TR, US, vb.)
      </div>
    `;
    return { html };
  }

  // Utils
  function escapeHtml(str) {
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#39;');
  }
  function escapeAttr(str) { return escapeHtml(str).replaceAll('\n',' '); }
  function debounce(fn, wait) { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; }
  function truncate(s, n) { s = String(s); return s.length > n ? s.slice(0,n-1) + '…' : s; }
})();
</script>
<?php else: ?>
  <p>Hiç firma yok.</p>
<?php endif; ?>

<style>
  .table-wrap { overflow:auto; }

  .table { border-collapse: collapse; width: 100%; min-width: 1200px; table-layout: fixed; }
  .table.small { min-width: 0; table-layout: auto; }
  .table th, .table td { border: 1px solid #ddd; padding: .5rem .6rem; text-align: left; vertical-align: top; }
  .table thead th { background: #f8f8f8; position: sticky; top: 0; z-index: 2; }
  .table thead tr:nth-child(2) th { top: 34px; background: #fcfcfc; z-index: 1; }

  th.sortable { cursor: pointer; padding-right: 1.2rem; position: sticky; }
  th.sortable::after {
    content: '↕';
    position: absolute; right: .4rem; color: #888; font-size: .9em;
  }
  th.sortable[data-sort="asc"]::after { content: '↑'; color: #333; }
  th.sortable[data-sort="desc"]::after { content: '↓'; color: #333; }

  #filtersRow th { padding: .35rem .4rem; }
  #filtersRow input[type="text"], #filtersRow input[type="date"], #filtersRow select {
    width: 100%; box-sizing: border-box; padding: .35rem .45rem; border: 1px solid #ccc; border-radius: .375rem;
  }
  .filter-date { display: grid; grid-template-columns: 1fr 1fr; gap: .25rem; }

  .actions { display: flex; gap: .4rem; align-items: center; }
  .btn { display: inline-block; padding: .25rem .5rem; border: 1px solid #888; border-radius: .375rem; text-decoration: none; color: #222; background: #fafafa; cursor: pointer; }
  .btn:hover { background: #f0f0f0; }
  .btn.danger { border-color: #c33; color: #a00; background: #fff5f5; }
  .btn.danger:hover { background: #ffecec; }
  .btn.primary { border-color: #2d6cdf; background: #2d6cdf; color: #fff; }
  .btn.primary:hover { background: #2357b6; }
  .btn.ghost { background: transparent; border-color: #bbb; }
  .file-btn { position: relative; overflow: hidden; }
  .file-btn input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }

  .spacer { flex: 1 1 auto; }

  .column-panel { border: 1px solid #ddd; padding: .5rem; border-radius: .5rem; margin-bottom: .5rem; background:#fafafa; }
  .column-panel .columns { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .35rem .75rem; margin-top: .5rem; }
  .column-panel .colchk { display: flex; align-items: center; gap: .4rem; }

  .modal { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: grid; place-items: center; z-index: 50; }
  .modal[hidden] { display: none; }
  .modal-content { background:#fff; width: min(1100px, calc(100vw - 2rem)); max-height: min(80vh, 800px); overflow:auto; border-radius: .5rem; padding: 1rem; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
  .modal-head { display:flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }

  .upload-preview { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }

  .badge.ok { background: #e8f6ec; color: #1b7f3b; padding: .1rem .35rem; border-radius: .4rem; border: 1px solid #bfe5cc; }
  .badge.no { background: #fff2f2; color: #b51e1e; padding: .1rem .35rem; border-radius: .4rem; border: 1px solid #f1c0c0; }
  /* Genel okunabilirlik */
.table th, .table td {
  padding: .6rem .7rem;        /* biraz ferahlık */
  line-height: 1.35;           /* satır yüksekliği */
  word-break: break-word;      /* uzun metinler kırılabilsin */
}

/* Başlıklar iki satır olduğundan sticky top değerleri */
.table thead th { top: 0; }
.table thead tr:nth-child(2) th { top: 40px; }

/* Varsayılan sabit düzeni biraz esnetelim */
.table { table-layout: fixed; min-width: 1100px; }

/* Kolon bazlı genişlikler (gerektikçe ayarlayın) */
.col-actions {
  width: 120px;     /* sabit isterseniz */
  min-width: 110px; /* alt sınır */
  max-width: 140px; /* üst sınır (isterseniz kaldırın) */
}

/* Butonları sığdırmak isterseniz */
td.actions { display: flex; gap: .3rem; align-items: center; }
td.actions .btn { padding: .22rem .4rem; font-size: .9rem; white-space: nowrap; }
.col-id      { width: 64px; min-width: 56px; max-width: 80px; text-align: right; }
.col-uuid    { width: 220px; min-width: 180px; max-width: 260px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .92em; }
.col-name    { min-width: 200px; max-width: 320px; font-weight: 600; }
.col-email   { min-width: 200px; max-width: 280px; }
.col-phone   { width: 140px; max-width: 160px; white-space: nowrap; }
.col-status  { width: 120px; max-width: 140px; }
.col-boolean { width: 90px; text-align: center; }
.col-url     { min-width: 200px; max-width: 320px; }

/* Posta kodu, ülke kodu gibi kısa alanlar isterseniz: */
.col-country_code { width: 90px; text-transform: uppercase; text-align: center; }
.col-postal_code  { width: 110px; white-space: nowrap; }

/* Filtre input'larının kolon genişliğiyle uyumu */
#filtersRow th.col-id input[type="text"] { text-align: right; }
#filtersRow th.col-boolean select { text-align: center; }

/* Uzun link ve e-postalar için görünüm */
td.col-email a, td.col-url a {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Badge'leri merkezle */
td.col-boolean .badge { display: inline-block; min-width: 48px; text-align: center; }

/* Satır araları ve zebra şerit */
.table tbody tr:nth-child(even) td { background: #fcfcfe; }

/* Küçük tabloda (modal önizleme) sıkılaştırma */
.table.small th, .table.small td {
  padding: .4rem .5rem;
  line-height: 1.25;
}

/* Dar ekran optimizasyonları */
@media (max-width: 900px) {
  .table { min-width: 900px; }   /* yatay kaydırma zaten var */
  .col-actions { min-width: 100px; }
  .col-name { min-width: 180px; }
}
</style>