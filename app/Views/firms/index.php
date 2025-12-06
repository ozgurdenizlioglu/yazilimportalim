<?php
use App\Core\Helpers;
ob_start();
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Firmalar') ?></h1>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="/firms/create"><i class="bi bi-plus-lg me-1"></i>Yeni Firma</a>
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

<?php $firmsJson = json_encode($firms ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>

<?php if (!empty($firms)): ?>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-wrap p-2 pt-0">
        <div id="columnPanel" class="column-panel" hidden>
          <strong>Görünen Kolonlar</strong>
          <div id="columnCheckboxes" class="columns"></div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="firmsTable">
            <thead>
              <tr id="headerRow"></tr>
              <tr id="filtersRow"></tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
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
          <form id="uploadSubmitForm" method="post" action="/firms/bulk-upload" enctype="multipart/form-data" class="ms-auto">
            <input type="hidden" name="payload" id="uploadPayload">
            <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Sunucuya Yükle</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php ob_start(); ?>
  <script>
  (function() {
    const DATA = <?= $firmsJson ?: '[]'; ?>;

    const allFields = [
      { id: 'id', label: 'ID' },{ id: 'uuid', label: 'UUID' },
      { id: 'created_at', label: 'Oluşturma' },{ id: 'updated_at', label: 'Güncelleme' },{ id: 'deleted_at', label: 'Silinme' },
      { id: 'name', label: 'Ad' },{ id: 'short_name', label: 'Kısa Ad' },{ id: 'legal_type', label: 'Hukuki Tip' },
      { id: 'registration_no', label: 'Sicil No' },{ id: 'mersis_no', label: 'MERSİS No' },
      { id: 'tax_office', label: 'Vergi Dairesi' },{ id: 'tax_number', label: 'Vergi No' },
      { id: 'email', label: 'E-posta' },{ id: 'phone', label: 'Telefon' },{ id: 'secondary_phone', label: 'İkinci Telefon' },
      { id: 'fax', label: 'Faks' },{ id: 'website', label: 'Web Sitesi' },
      { id: 'address_line1', label: 'Adres 1' },{ id: 'address_line2', label: 'Adres 2' },{ id: 'city', label: 'Şehir' },{ id: 'state_region', label: 'Eyalet/Bölge' },
      { id: 'postal_code', label: 'Posta Kodu' },{ id: 'country_code', label: 'Ülke (ISO-2)' },
      { id: 'latitude', label: 'Enlem' },{ id: 'longitude', label: 'Boylam' },
      { id: 'industry', label: 'Sektör' },{ id: 'status', label: 'Durum' },{ id: 'currency_code', label: 'Para Birimi' },{ id: 'timezone', label: 'Zaman Dilimi' },
      { id: 'vat_exempt', label: 'KDV Muaf' },{ id: 'e_invoice_enabled', label: 'E-Fatura' },{ id: 'logo_url', label: 'Logo URL' },{ id: 'notes', label: 'Notlar' },{ id: 'is_active', label: 'Aktif' },
      { id: 'created_by', label: 'Oluşturan' },{ id: 'updated_by', label: 'Güncelleyen' },
    ];

    const columns = [
      { id: 'actions', label: 'İşlemler', isAction: true, className: 'col-actions' },
      { id: 'id', label: 'ID', filterType: 'text', className: 'text-end' },
      { id: 'uuid', label: 'UUID', filterType: 'text' },
      { id: 'name', label: 'Ad', filterType: 'text' },
      { id: 'short_name', label: 'Kısa Ad', filterType: 'text' },
      { id: 'legal_type', label: 'Hukuki Tip', filterType: 'text' },
      { id: 'registration_no', label: 'Sicil No', filterType: 'text' },
      { id: 'mersis_no', label: 'MERSİS No', filterType: 'text' },
      { id: 'tax_office', label: 'Vergi Dairesi', filterType: 'text' },
      { id: 'tax_number', label: 'Vergi No', filterType: 'text' },
      { id: 'email', label: 'E-posta', filterType: 'text' },
      { id: 'phone', label: 'Telefon', filterType: 'text' },
      { id: 'secondary_phone', label: 'İkinci Telefon', filterType: 'text' },
      { id: 'fax', label: 'Faks', filterType: 'text' },
      { id: 'website', label: 'Web Sitesi', filterType: 'text' },
      { id: 'address_line1', label: 'Adres 1', filterType: 'text' },
      { id: 'address_line2', label: 'Adres 2', filterType: 'text' },
      { id: 'city', label: 'Şehir', filterType: 'text' },
      { id: 'state_region', label: 'Eyalet/Bölge', filterType: 'text' },
      { id: 'postal_code', label: 'Posta Kodu', filterType: 'text' },
      { id: 'country_code', label: 'Ülke (ISO-2)', filterType: 'text' },
      { id: 'latitude', label: 'Enlem', filterType: 'text' },
      { id: 'longitude', label: 'Boylam', filterType: 'text' },
      { id: 'industry', label: 'Sektör', filterType: 'text' },
      { id: 'status', label: 'Durum', filterType: 'text' },
      { id: 'currency_code', label: 'Para Birimi', filterType: 'text' },
      { id: 'timezone', label: 'Zaman Dilimi', filterType: 'text' },
      { id: 'vat_exempt', label: 'KDV Muaf', filterType: 'boolean' },
      { id: 'e_invoice_enabled', label: 'E-Fatura', filterType: 'boolean' },
      { id: 'logo_url', label: 'Logo URL', filterType: 'text' },
      { id: 'notes', label: 'Notlar', filterType: 'text' },
      { id: 'is_active', label: 'Aktif', filterType: 'boolean' },
      { id: 'created_by', label: 'Oluşturan', filterType: 'text' },
      { id: 'updated_by', label: 'Güncelleyen', filterType: 'text' },
      { id: 'created_at', label: 'Oluşturma', filterType: 'date' },
      { id: 'updated_at', label: 'Güncelleme', filterType: 'date' },
      { id: 'deleted_at', label: 'Silinme', filterType: 'date' },
    ];

    const defaultVisible = ['actions','id','name','email','phone','status'];
    const LS_KEYS = { visibleCols: 'firms.visibleCols', filters: 'firms.filters', sort: 'firms.sort' };
    let state = { visibleCols: loadVisibleCols(), filters: loadFilters(), sort: loadSort() };

    const headerRow = document.getElementById('headerRow');
    const filtersRow = document.getElementById('filtersRow');
    const tbody = document.getElementById('tableBody');
    const columnPanel = document.getElementById('columnPanel');
    const columnCheckboxes = document.getElementById('columnCheckboxes');

    document.getElementById('toggleColumnPanel').addEventListener('click', ()=> columnPanel.hidden = !columnPanel.hidden);
    document.getElementById('resetView').addEventListener('click', ()=>{
      state.visibleCols = [...defaultVisible]; state.filters = {}; state.sort = { by:'id', dir:'asc' };
      saveAll(); buildColumnPanel(); buildHead(); render();
    });
    document.getElementById('downloadTemplate').addEventListener('click', downloadTemplate);
    document.getElementById('exportExcel').addEventListener('click', exportAllToExcel);

    const uploadInput = document.getElementById('uploadFile');
    uploadInput.addEventListener('change', handleUpload);

    buildColumnPanel(); buildHead(); render();

    function loadVisibleCols(){
      try {
        const raw = localStorage.getItem(LS_KEYS.visibleCols);
        const arr = raw ? JSON.parse(raw) : null;
        let cols = arr && Array.isArray(arr) ? arr : [...defaultVisible];
        cols = cols.filter(id => columns.some(c=>c.id===id));
        cols = ['actions', ...cols.filter(id=>id!=='actions')];
        return cols;
      } catch { return [...defaultVisible]; }
    }
    function loadFilters(){ try { return JSON.parse(localStorage.getItem(LS_KEYS.filters) || '{}'); } catch { return {}; } }
    function loadSort(){
      try {
        const s = JSON.parse(localStorage.getItem(LS_KEYS.sort) || '{"by":"id","dir":"asc"}');
        return columns.find(c=>c.id===s.by) ? s : { by:'id', dir:'asc' };
      } catch { return { by:'id', dir:'asc' }; }
    }
    function saveAll(){
      localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));
      localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));
      localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));
    }
    function visibleColumns(){ return state.visibleCols.map(id=>columns.find(c=>c.id===id)).filter(Boolean); }

    function buildColumnPanel(){
      columnCheckboxes.innerHTML = '';
      columns.forEach(col=>{
        if (col.id === 'actions') return;
        const id = `colchk_${col.id}`;
        const wrap = document.createElement('label');
        wrap.className = 'colchk';
        wrap.innerHTML = `<input type="checkbox" id="${id}" ${state.visibleCols.includes(col.id)?'checked':''}> <span>${col.label}</span>`;
        wrap.querySelector('input').addEventListener('change', (e)=>{
          if (e.target.checked) { if (!state.visibleCols.includes(col.id)) state.visibleCols.push(col.id); }
          else { state.visibleCols = state.visibleCols.filter(x=>x!==col.id); }
          state.visibleCols = ['actions', ...state.visibleCols.filter(x=>x!=='actions')];
          saveAll(); buildHead(); render();
        });
        columnCheckboxes.appendChild(wrap);
      });
    }

    function buildHead(){
      headerRow.innerHTML = ''; filtersRow.innerHTML = '';
      const cols = visibleColumns();
      cols.forEach(col=>{
        const th = document.createElement('th');
        th.textContent = col.label;
        if (!col.isAction) {
          th.classList.add('sortable');
          th.addEventListener('click', ()=>toggleSort(col.id));
          if (state.sort.by === col.id) th.dataset.sort = state.sort.dir;
        } else {
          th.classList.add('col-actions');
        }
        headerRow.appendChild(th);

        const tf = document.createElement('th');
        tf.className = 'filter-cell';
        if (col.isAction) {
          tf.innerHTML = '';
        } else if (col.filterType === 'date') {
          tf.innerHTML = `
            <div class="d-grid" style="grid-template-columns:1fr 1fr; gap:.25rem;">
              <input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="from" value="${escapeAttr(state.filters[col.id]?.from || '')}">
              <input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="to" value="${escapeAttr(state.filters[col.id]?.to || '')}">
            </div>
          `;
        } else if (col.filterType === 'boolean') {
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
        filtersRow.appendChild(tf);
      });

      filtersRow.querySelectorAll('input[data-kind="text"]').forEach(inp=>{
        inp.addEventListener('input', debounce(()=>{ const key=inp.dataset.key; state.filters[key]={ val:inp.value }; saveAll(); render(); },200));
      });
      filtersRow.querySelectorAll('select[data-kind="bool"]').forEach(sel=>{
        sel.addEventListener('change', ()=>{ const key=sel.dataset.key; state.filters[key]={ val: sel.value }; saveAll(); render(); });
      });
      filtersRow.querySelectorAll('input[data-kind="from"], input[data-kind="to"]').forEach(inp=>{
        inp.addEventListener('change', ()=>{ const key=inp.dataset.key, kind=inp.dataset.kind; state.filters[key]=state.filters[key]||{}; state.filters[key][kind]=inp.value; saveAll(); render(); });
      });
    }

    function toggleSort(colId){
      if (state.sort.by === colId) state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
      else { state.sort.by = colId; state.sort.dir = 'asc'; }
      saveAll(); buildHead(); render();
    }

    function applyFilters(rows){
      return rows.filter(r=>{
        for (const col of columns){
          const f = state.filters[col.id];
          if (!f) continue;
          if (col.filterType === 'text'){
            const needle = (f.val ?? '').trim().toLowerCase();
            if (needle){
              const hay = String(r[col.id] ?? '').toLowerCase();
              if (!hay.includes(needle)) return false;
            }
          } else if (col.filterType === 'boolean'){
            const val = String(f.val ?? '');
            if (val){
              const raw = r[col.id];
              const boolVal = (typeof raw === 'boolean') ? raw : ['1','true','on','yes','evet'].includes(String(raw).toLowerCase());
              if ((val === 'true') !== boolVal) return false;
            }
          } else if (col.filterType === 'date'){
            const from = f.from ? Date.parse(f.from) : null;
            const to   = f.to   ? Date.parse(f.to)   : null;
            const v    = Date.parse(r[col.id] ?? '');
            if (from && !(v >= from)) return false;
            if (to && !(v <= to)) return false;
          }
        }
        return true;
      });
    }

    function applySort(rows){
      const col = columns.find(c=>c.id===state.sort.by);
      if (!col) return rows;
      const dir = state.sort.dir === 'asc' ? 1 : -1;
      return [...rows].sort((a,b)=>{
        const av = String(a[col.id] ?? '').toLowerCase();
        const bv = String(b[col.id] ?? '').toLowerCase();
        if (av < bv) return -1 * dir;
        if (av > bv) return 1 * dir;
        return 0;
      });
    }

    function render(){
      const cols = visibleColumns();
      const sorted = applySort(applyFilters(DATA));
      tbody.innerHTML = '';
      for (const r of sorted){
        const tr = document.createElement('tr');
        for (const col of cols){
          const td = document.createElement('td');
          if (col.isAction){
            td.classList.add('col-actions');
            td.innerHTML = `
              <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-light" href="/firms/edit?id=${escapeAttr(String(r.id ?? r.uuid ?? ''))}" title="Düzenle">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" action="/firms/delete" onsubmit="return confirm('Silinsin mi?')" style="display:inline;">
                  <input type="hidden" name="uuid" value="${escapeAttr(String(r.uuid ?? ''))}">
                  <button class="btn btn-light text-danger" type="submit" title="Sil">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            `;
          } else if (col.id === 'email') {
            const email = String(r.email ?? '');
            td.innerHTML = email ? `<a href="mailto:${escapeAttr(email)}" class="truncate">${escapeHtml(email)}</a>` : '';
          } else if (col.id === 'website') {
            const url = String(r.website ?? '');
            td.innerHTML = url ? `<a href="${escapeAttr(url)}" target="_blank" rel="noopener" class="truncate">${escapeHtml(truncate(url, 40))}</a>` : '';
          } else if (['is_active','vat_exempt','e_invoice_enabled'].includes(col.id)) {
            const raw = r[col.id];
            const isTrue = typeof raw === 'boolean' ? raw : ['1','true','on','yes','evet'].includes(String(raw).toLowerCase());
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
      if (idx >= 0) headerRow.children[idx]?.setAttribute('data-sort', state.sort.dir);
    }

    function exportAllToExcel(){
      const exportCols = allFields;
      const headers = exportCols.map(c=>c.id);
      const rows = DATA.map(r => exportCols.map(c => formatForCsv(c.id, r[c.id])));
      const csv = toCsv([headers, ...rows]);
      const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
      triggerDownload(blob, 'firms_full_export.csv');
    }

    function downloadTemplate(){
      const headers = allFields.map(c => c.id);
      const sample1 = { id:'', uuid:'', created_at:'', updated_at:'', deleted_at:'',
        name:'Acme A.Ş.', short_name:'ACME', legal_type:'AS', registration_no:'IST-123456', mersis_no:'0123001230000012',
        tax_office:'Üsküdar VD', tax_number:'1234567890', email:'info@acme.com', phone:'02123334455', secondary_phone:'',
        fax:'', website:'https://www.acme.com', address_line1:'Mah. Cad. No:1', address_line2:'Kat 2', city:'İstanbul',
        state_region:'Üsküdar', postal_code:'34660', country_code:'TR', latitude:'41.025', longitude:'29.02', industry:'Manufacturing',
        status:'active', currency_code:'TRY', timezone:'Europe/Istanbul', vat_exempt:'false', e_invoice_enabled:'true',
        logo_url:'', notes:'Örnek not', is_active:'true', created_by:'', updated_by:''
      };
      const sample2 = { id:'', uuid:'', created_at:'', updated_at:'', deleted_at:'',
        name:'Beta Ltd.', short_name:'BETA', legal_type:'LTD', registration_no:'ANK-987654', mersis_no:'',
        tax_office:'Çankaya VD', tax_number:'9988776655', email:'iletisim@beta.com', phone:'03124567890', secondary_phone:'',
        fax:'', website:'http://beta.com', address_line1:'Sokak 10', address_line2:'', city:'Ankara',
        state_region:'Çankaya', postal_code:'06680', country_code:'TR', latitude:'', longitude:'', industry:'IT Services',
        status:'prospect', currency_code:'TRY', timezone:'Europe/Istanbul', vat_exempt:'true', e_invoice_enabled:'false',
        logo_url:'', notes:'', is_active:'false', created_by:'', updated_by:''
      };
      const rows = [headers, ...[sample1, sample2].map(s => headers.map(h => formatForCsv(h, s[h])))];
      const csv = toCsv(rows);
      const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
      triggerDownload(blob, 'firms_template_full.csv');
    }

    // Upload
    async function handleUpload(e){
      const file = e.target.files?.[0]; if (!file) return;
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
      } catch (err){
        alert('Dosya okunamadı: ' + (err?.message || err));
        e.target.value = '';
      }
    }

    function readCsv(file){
      return new Promise((resolve, reject)=>{
        const reader = new FileReader();
        reader.onerror = ()=>reject(new Error('Okuma hatası'));
        reader.onload = ()=>{ try { resolve(parseCsv(String(reader.result))); } catch(e){ reject(e); } };
        reader.readAsText(file, 'utf-8');
      });
    }
    function parseCsv(text){
      const rows=[]; let row=[], val='', inQuotes=false;
      for (let i=0;i<text.length;i++){
        const ch=text[i];
        if (inQuotes){
          if (ch === '"'){ if (text[i+1] === '"'){ val+='"'; i++; } else { inQuotes=false; } }
          else { val += ch; }
        } else {
          if (ch === '"') inQuotes=true;
          else if (ch === ','){ row.push(val); val=''; }
          else if (ch === '\r'){ /* skip */ }
          else if (ch === '\n'){ row.push(val); rows.push(row); row=[]; val=''; }
          else { val += ch; }
        }
      }
      if (val.length || row.length){ row.push(val); rows.push(row); }
      return rows;
    }
    function tryReadAsTextCsv(file){
      return new Promise((resolve, reject)=>{
        const reader = new FileReader();
        reader.onerror = ()=>reject(new Error('Okuma hatası'));
        reader.onload = ()=>{
          const text = String(reader.result || '');
          if (text.includes(',') || text.includes(';')) resolve(parseCsv(text));
          else resolve([['Uyarı: XLSX istemci tarafında parse edilmedi. CSV yükleyin ya da SheetJS ekleyelim.']]);
        };
        reader.readAsText(file);
      });
    }

    function buildPreviewTable(rows){
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
          • status: active|prospect|lead|suspended|inactive
          • boolean: true|false (is_active, vat_exempt, e_invoice_enabled)
          • created_at/updated_at/deleted_at: ISO datetime (opsiyonel)
          • country_code: ISO-2 (TR, US, vb.)
        </div>
      `;
      return { html };
    }

    function formatForCsv(field, value){
      if (value == null) return '';
      const v = value;
      if (['is_active','vat_exempt','e_invoice_enabled'].includes(field)){
        if (typeof v === 'boolean') return v ? 'true' : 'false';
        const s = String(v).toLowerCase();
        return ['1','true','yes','on','evet'].includes(s) ? 'true' : (['0','false','no','off','hayir','hayır'].includes(s) ? 'false' : String(v));
      }
      if (['created_at','updated_at','deleted_at'].includes(field)){
        const d = new Date(v); return isNaN(d) ? String(v) : d.toISOString();
      }
      return String(v);
    }

    function toCsv(rows){ return rows.map(row => row.map(csvEscape).join(',')).join('\r\n'); }
    function csvEscape(v){ const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replaceAll('"','""')}"` : s; }
    function triggerDownload(blob, filename){ const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); setTimeout(()=>URL.revokeObjectURL(a.href),1000); }

    function escapeHtml(str){ return String(str).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;'); }
    function escapeAttr(str){ return escapeHtml(str).replaceAll('\n',' '); }
    function debounce(fn, wait){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; }
    function truncate(s, n){ s=String(s); return s.length>n ? s.slice(0,n-1)+'…' : s; }
  })();
  </script>
  <?php $pageScripts = ob_get_clean(); ?>
<?php else: ?>
  <div class="alert alert-light border d-flex align-items-center" role="alert">
    <i class="bi bi-inboxes me-2"></i> Hiç firma yok.
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';