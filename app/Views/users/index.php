<?php use App\Core\Helpers;

?>
<h1><?= Helpers::e($title ?? 'Kullanıcılar') ?></h1>

<p style="margin:.5rem 0 1rem; display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
  <a class="btn" href="/users/create">+ Yeni Kullanıcı</a>
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
  $usersJson = json_encode($users ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<?php if (!empty($users)): ?>
<div class="table-wrap">
  <div id="columnPanel" class="column-panel" hidden>
    <strong>Görünen Kolonlar</strong>
    <div id="columnCheckboxes" class="columns"></div>
  </div>

  <table class="table" id="usersTable">
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
    <form id="uploadSubmitForm" method="post" action="/users/bulk-upload" enctype="multipart/form-data" style="margin-top:.75rem;">
      <input type="hidden" name="payload" id="uploadPayload">
      <button class="btn primary" type="submit">Sunucuya Yükle</button>
    </form>
  </div>
</div>

<script>
(function() {
  const DATA = <?php echo $usersJson ?: '[]'; ?>;

  // Sabit kolon listesi (export ve şablon bu seti kullanır)
  const allFields = [
    // Kimlik/zaman damgaları
    { id: 'id', label: 'ID' },
    { id: 'uuid', label: 'UUID' },
    { id: 'created_at', label: 'Oluşturma' },
    { id: 'updated_at', label: 'Güncelleme' },
    { id: 'deleted_at', label: 'Silinme' },
    { id: 'last_login_at', label: 'Son Giriş' },

    // Kişisel bilgiler
    { id: 'first_name', label: 'Ad' },
    { id: 'middle_name', label: 'İkinci Ad' },
    { id: 'last_name', label: 'Soyad' },
    { id: 'gender', label: 'Cinsiyet' },
    { id: 'birth_date', label: 'Doğum Tarihi' },
    { id: 'nationality_code', label: 'Uyruk (ISO-2)' },
    { id: 'place_of_birth', label: 'Doğum Yeri' },
    { id: 'language', label: 'Dil (IETF, tr-TR)' },
    { id: 'timezone', label: 'Zaman Dilimi' },

    // İletişim
    { id: 'phone', label: 'Telefon' },
    { id: 'secondary_phone', label: 'İkinci Telefon' },
    { id: 'email', label: 'E-posta' },

    // Kimlik numaraları
    { id: 'national_id', label: 'TC/Ulusal ID' },
    { id: 'passport_no', label: 'Pasaport No' },

    // Medeni/diğer
    { id: 'marital_status', label: 'Medeni Hali' },

    // Adres
    { id: 'address_line1', label: 'Adres 1' },
    { id: 'address_line2', label: 'Adres 2' },
    { id: 'city', label: 'Şehir' },
    { id: 'state_region', label: 'Eyalet/Bölge' },
    { id: 'postal_code', label: 'Posta Kodu' },
    { id: 'country_code', label: 'Ülke (ISO-2)' },

    // Diğer
    { id: 'notes', label: 'Notlar' },
    { id: 'is_active', label: 'Aktif' },
  ];

  // Tabloda gösterilebilecek kolonlar (+ işlemler)
  const columns = [
    { id: 'actions', label: 'İşlemler', isAction: true },
    { id: 'id', label: 'ID', filterType: 'text' },
    { id: 'uuid', label: 'UUID', filterType: 'text' },
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

  const defaultVisible = ['actions','id','uuid','first_name','last_name','email','phone','is_active'];

  const LS_KEYS = { visibleCols: 'users.visibleCols', filters: 'users.filters', sort: 'users.sort' };

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
      if (!col.isAction) {
        th.classList.add('sortable');
        th.addEventListener('click', () => toggleSort(col.id));
        if (state.sort.by === col.id) th.dataset.sort = state.sort.dir;
      } else {
        th.style.minWidth = '160px';
      }
      headerRow.appendChild(th);

      const tf = document.createElement('th');
      tf.className = 'filter-cell';
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
            const raw = r.is_active;
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

        if (col.isAction) {
          td.className = 'actions';
          td.innerHTML = `
            <a class="btn" href="/users/edit?id=${escapeAttr(String(r.id ?? r.uuid ?? ''))}">Düzenle</a>
            <form method="post" action="/users/delete" onsubmit="return confirm('Silinsin mi?')" style="display:inline;">
              <input type="hidden" name="uuid" value="${escapeAttr(String(r.uuid ?? ''))}">
              <button class="btn danger" type="submit">Sil</button>
            </form>
          `;
        } else if (col.id === 'email') {
          const email = String(r.email ?? '');
          td.innerHTML = email ? `<a href="mailto:${escapeAttr(email)}">${escapeHtml(email)}</a>` : '';
        } else if (col.id === 'is_active') {
          const raw = r.is_active;
          const isActive = typeof raw === 'boolean' ? raw : ['1','true','on','yes','evet','true'].includes(String(raw).toLowerCase());
          td.innerHTML = `<span class="badge ${isActive ? 'ok' : 'no'}">${isActive}</span>`;
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
    const headers = exportCols.map(c => c.id); // şablonla aynı id başlıklar
    const rows = DATA.map(r => exportCols.map(c => formatForCsv(c.id, r[c.id])));

    const csv = toCsv([headers, ...rows]);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    triggerDownload(blob, 'users_full_export.csv');
  }

  // Şablon indir: tüm alan başlıkları + örnek satırlar
  function downloadTemplate() {
    const headers = allFields.map(c => c.id);

    const sample1 = {
      id: '', // boş bırakın: sunucu üretebilir
      uuid: '', // boş bırakın: sunucu üretebilir
      created_at: '', // boş: sistem ekler
      updated_at: '',
      deleted_at: '',
      last_login_at: '',
      first_name: 'Ahmet',
      middle_name: '',
      last_name: 'Yılmaz',
      gender: 'male',
      birth_date: '1990-01-15',
      phone: '5551234567',
      secondary_phone: '',
      national_id: '12345678901',
      passport_no: '',
      marital_status: 'married',
      nationality_code: 'TR',
      place_of_birth: 'İstanbul',
      timezone: 'Europe/Istanbul',
      language: 'tr-TR',
      address_line1: 'Mah. Cad. No:1',
      address_line2: 'Daire 5',
      city: 'İstanbul',
      state_region: 'Kadıköy',
      postal_code: '34710',
      country_code: 'TR',
      notes: 'Not örneği',
      is_active: 'true'
    };

    const sample2 = {
      id: '',
      uuid: '',
      created_at: '',
      updated_at: '',
      deleted_at: '',
      last_login_at: '',
      first_name: 'Ayşe',
      middle_name: 'Nur',
      last_name: 'Demir',
      gender: 'female',
      birth_date: '1992-07-03',
      phone: '5329876543',
      secondary_phone: '',
      national_id: '',
      passport_no: 'U1234567',
      marital_status: 'single',
      nationality_code: 'TR',
      place_of_birth: 'Ankara',
      timezone: 'Europe/Istanbul',
      language: 'tr-TR',
      address_line1: 'Sokak 10',
      address_line2: '',
      city: 'Ankara',
      state_region: 'Çankaya',
      postal_code: '06680',
      country_code: 'TR',
      notes: '',
      is_active: 'false'
    };

    const rows = [headers, ...[sample1, sample2].map(s => headers.map(h => formatForCsv(h, s[h])))];
    const csv = toCsv(rows);
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    triggerDownload(blob, 'users_template_full.csv');
  }

  function formatForCsv(field, value) {
    if (value == null) return '';
    const v = value;

    // boolean: true/false
    if (field === 'is_active') {
      if (typeof v === 'boolean') return v ? 'true' : 'false';
      const s = String(v).toLowerCase();
      return ['1','true','yes','on','evet'].includes(s) ? 'true'
        : (['0','false','no','off','hayir','hayır'].includes(s) ? 'false' : String(v));
    }

    // tarih alanları
    if (['birth_date'].includes(field)) {
      // YYYY-MM-DD
      const d = new Date(v);
      if (!isNaN(d)) return d.toISOString().slice(0,10);
      const s = String(v);
      return /^\d{4}-\d{2}-\d{2}$/.test(s) ? s : s;
    }
    if (['created_at','updated_at','deleted_at','last_login_at'].includes(field)) {
      // ISO datetime
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

  // Upload işlemi (CSV temel; XLSX için SheetJS eklenebilir)
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
        - gender: male|female|nonbinary|unknown
        - marital_status: single|married|divorced|widowed|other
        - birth_date: YYYY-MM-DD
        - created_at/updated_at/deleted_at/last_login_at: ISO datetime (opsiyonel)
        - is_active: true|false
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
})();
</script>
<?php else: ?>
  <p>Hiç kullanıcı yok.</p>
<?php endif; ?>

<style>
  .table-wrap { overflow:auto; }

  .table { border-collapse: collapse; width: 100%; min-width: 1100px; table-layout: fixed; }
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
</style>