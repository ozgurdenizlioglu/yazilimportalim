<?php
use App\Core\Helpers;

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 m-0"><?= Helpers::e($title ?? 'Giriş/Çıkış Raporu') ?></h1>
    <?php if (!empty($subtitle ?? null)): ?>
      <div class="text-muted small"><?= Helpers::e($subtitle) ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" action="/attendance/report" class="row gy-2 gx-2 align-items-end">
      <input type="hidden" name="page" value="1">
      <div class="col-6 col-md-2">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= Helpers::e($filters['from'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= Helpers::e($filters['to'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Tür</label>
        <select name="type" class="form-select">
          <option value="">— Tümü —</option>
          <option value="in"  <?= (($filters['type'] ?? '')==='in') ? 'selected':''; ?>>in</option>
          <option value="out" <?= (($filters['type'] ?? '')==='out') ? 'selected':''; ?>>out</option>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Firma</label>
        <select name="company_id" class="form-select">
          <option value="">— Tümü —</option>
          <?php foreach (($companies ?? []) as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((string)($filters['company_id'] ?? '') === (string)$c['id']) ? 'selected' : '' ?>>
              <?= Helpers::e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Ad/Soyad</label>
        <input type="text" name="name" class="form-control" value="<?= Helpers::e($filters['name'] ?? '') ?>" placeholder="Ad veya soyad">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Cihaz</label>
        <input type="text" name="device" class="form-control" value="<?= Helpers::e($filters['device'] ?? '') ?>" placeholder="Cihaz adı">
      </div>
      <div class="col-12 col-md-4 d-flex gap-2 mt-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filtrele</button>
        <button class="btn btn-outline-secondary" type="button" id="toggleColumnPanel"><i class="bi bi-columns-gap me-1"></i>Kolonları Yönet</button>
        <button class="btn btn-outline-secondary" type="button" id="resetView"><i class="bi bi-arrow-counterclockwise me-1"></i>Görünümü Sıfırla</button>
        <div class="ms-auto"></div>
        <button class="btn btn-success" type="button" id="exportExcel"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excele Aktar</button>
      </div>
    </form>
  </div>
</div>

<?php
$rowsJson = json_encode($rows ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<?php if (!empty($rows)): ?>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-wrap p-2 pt-0">
        <div id="columnPanel" class="column-panel" hidden>
          <strong>Görünen Kolonlar</strong>
          <div id="columnCheckboxes" class="columns"></div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="attTable">
            <thead>
              <tr id="headerRow"></tr>
              <tr id="filtersRow"></tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
    <?php
      $total = (int)($total ?? 0);
      $page  = (int)($page  ?? 1);
      $limit = (int)($limit ?? 200);
      $pages = max(1, (int)ceil($total / max(1, $limit)));
      $buildUrl = function($p){
        $qs = $_GET;
        $qs['page'] = $p;
        return '/attendance/report?' . http_build_query($qs);
      };
    ?>
    <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
      <div class="text-muted small">Toplam: <strong><?= $total ?></strong> | Sayfa: <?= $page ?>/<?= $pages ?></div>
      <div class="ms-auto"></div>
      <nav>
        <ul class="pagination mb-0">
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= Helpers::e($buildUrl(1)) ?>">« İlk</a>
          </li>
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= Helpers::e($buildUrl(max(1,$page-1))) ?>">‹ Önceki</a>
          </li>
          <li class="page-item disabled"><span class="page-link"><?= $page ?> / <?= $pages ?></span></li>
          <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
            <a class="page-link" href="<?= Helpers::e($buildUrl(min($pages,$page+1))) ?>">Sonraki ›</a>
          </li>
          <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
            <a class="page-link" href="<?= Helpers::e($buildUrl($pages)) ?>">Son »</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>

  <?php ob_start(); ?>
  <script>
  (function(){
    const DATA = <?= $rowsJson ?: '[]' ?>;

    // Kolon tanımları
    const columns = [
      { id:'id', label:'ID', filterType:'text', className:'text-end' },
      { id:'scanned_at', label:'Zaman', filterType:'date' },
      { id:'type', label:'Tür', filterType:'text' },
      { id:'first_name', label:'Ad', filterType:'text' },
      { id:'last_name', label:'Soyad', filterType:'text' },
      { id:'company_name', label:'Firma', filterType:'text' },
      { id:'source_device', label:'Cihaz', filterType:'text' },
      { id:'notes', label:'Notlar', filterType:'text' },
      { id:'user_id', label:'User ID', filterType:'text', className:'text-end' },
    ];

    const defaultVisible = ['id','scanned_at','type','first_name','last_name','company_name','source_device','notes'];

    const LS_KEYS = { visibleCols:'att.visibleCols', filters:'att.filters', sort:'att.sort' };
    let state = { visibleCols: loadVisibleCols(), filters: loadFilters(), sort: loadSort() };

    const headerRow = document.getElementById('headerRow');
    const filtersRow = document.getElementById('filtersRow');
    const tbody = document.getElementById('tableBody');
    const columnPanel = document.getElementById('columnPanel');
    const columnCheckboxes = document.getElementById('columnCheckboxes');

    document.getElementById('toggleColumnPanel').addEventListener('click', ()=> columnPanel.hidden = !columnPanel.hidden);
    document.getElementById('resetView').addEventListener('click', ()=>{
      state.visibleCols = [...defaultVisible];
      state.filters = {};
      state.sort = { by:'scanned_at', dir:'desc' };
      saveAll(); buildColumnPanel(); buildHead(); render();
    });
    document.getElementById('exportExcel').addEventListener('click', exportAllToCsv);

    buildColumnPanel(); buildHead(); render();

    function loadVisibleCols(){
      try {
        const raw = localStorage.getItem(LS_KEYS.visibleCols);
        const arr = raw ? JSON.parse(raw) : null;
        let cols = (arr && Array.isArray(arr)) ? arr : [...defaultVisible];
        cols = cols.filter(id => columns.find(c=>c.id===id));
        return cols;
      } catch { return [...defaultVisible]; }
    }
    function loadFilters(){ try { return JSON.parse(localStorage.getItem(LS_KEYS.filters) || '{}'); } catch { return {}; } }
    function loadSort(){
      try {
        const s = JSON.parse(localStorage.getItem(LS_KEYS.sort) || '{"by":"scanned_at","dir":"desc"}');
        return columns.find(c=>c.id===s.by) ? s : { by:'scanned_at', dir:'desc' };
      } catch { return { by:'scanned_at', dir:'desc' }; }
    }
    function saveAll(){
      localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));
      localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));
      localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));
    }
    function visibleColumns(){ return state.visibleCols.map(id => columns.find(c=>c.id===id)).filter(Boolean); }

    function buildColumnPanel(){
      columnCheckboxes.innerHTML = '';
      columns.forEach(col=>{
        const id = 'colchk_'+col.id;
        const wrap = document.createElement('label');
        wrap.className = 'colchk';
        wrap.innerHTML = `<input type="checkbox" id="${id}" ${state.visibleCols.includes(col.id)?'checked':''}> <span>${col.label}</span>`;
        wrap.querySelector('input').addEventListener('change', (e)=>{
          if (e.target.checked) { if (!state.visibleCols.includes(col.id)) state.visibleCols.push(col.id); }
          else { state.visibleCols = state.visibleCols.filter(x=>x!==col.id); }
          saveAll(); buildHead(); render();
        });
        columnCheckboxes.appendChild(wrap);
      });
    }

    function buildHead(){
      headerRow.innerHTML = ''; filtersRow.innerHTML = '';
      const cols = visibleColumns();
      cols.forEach(col=>{
        // Header hücresi
        const th = document.createElement('th');
        th.textContent = col.label;
        if (col.className) th.classList.add(...col.className.split(' '));
        th.classList.add('sortable');
        th.addEventListener('click', ()=>toggleSort(col.id));
        if (state.sort.by === col.id) th.setAttribute('data-sort', state.sort.dir);
        headerRow.appendChild(th);

        // Filtre hücresi
        const tf = document.createElement('th');
        tf.className = 'filter-cell';
        if (col.filterType === 'date') {
          const fromVal = state.filters[col.id]?.from || '';
          const toVal   = state.filters[col.id]?.to   || '';
          tf.innerHTML = `
            <div class="d-grid" style="grid-template-columns:1fr 1fr; gap:.25rem;">
              <input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="from" value="${escapeAttr(fromVal)}">
              <input type="date" class="form-control form-control-sm" data-key="${col.id}" data-kind="to" value="${escapeAttr(toVal)}">
            </div>
          `;
        } else {
          const cur = state.filters[col.id]?.val || '';
          tf.innerHTML = `<input type="text" class="form-control form-control-sm" placeholder="Ara..." data-key="${col.id}" data-kind="text" value="${escapeAttr(cur)}">`;
        }
        filtersRow.appendChild(tf);
      });

      // Dinleyiciler
      filtersRow.querySelectorAll('input[data-kind="text"]').forEach(inp=>{
        inp.addEventListener('input', debounce(()=>{
          const key = inp.dataset.key;
          state.filters[key] = { val: inp.value };
          saveAll(); render();
        }, 200));
      });
      filtersRow.querySelectorAll('input[data-kind="from"], input[data-kind="to"]').forEach(inp=>{
        inp.addEventListener('change', ()=>{
          const key = inp.dataset.key, kind = inp.dataset.kind;
          state.filters[key] = state.filters[key] || {};
          state.filters[key][kind] = inp.value;
          saveAll(); render();
        });
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
            const needle = (f.val || '').trim().toLowerCase();
            if (needle) {
              const hay = String(r[col.id] ?? '').toLowerCase();
              if (!hay.includes(needle)) return false;
            }
          } else if (col.filterType === 'date'){
            const from = f.from ? Date.parse(f.from) : null;
            const to   = f.to   ? Date.parse(f.to)   : null;
            const v    = Date.parse(r[col.id] ?? '');
            if (Number.isNaN(v)) return false;
            if (from && !(v >= from)) return false;
            if (to && !(v <= to)) return false;
          }
        }
        return true;
      });
    }

    function applySort(rows){
      const by = state.sort.by;
      const dir = state.sort.dir === 'asc' ? 1 : -1;
      return [...rows].sort((a,b)=>{
        const av = a[by];
        const bv = b[by];
        // Tarih ve sayı için basit kıyaslama
        if (by === 'id' || by === 'user_id') {
          const ai = Number(av ?? 0), bi = Number(bv ?? 0);
          return (ai < bi ? -1 : ai > bi ? 1 : 0) * dir;
        }
        if (by === 'scanned_at') {
          const at = Date.parse(av ?? '') || 0;
          const bt = Date.parse(bv ?? '') || 0;
          return (at < bt ? -1 : at > bt ? 1 : 0) * dir;
        }
        const as = String(av ?? '');
        const bs = String(bv ?? '');
        return (as < bs ? -1 : as > bs ? 1 : 0) * dir;
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
          if (col.className) td.className = col.className;
          if (col.id === 'scanned_at') {
            td.textContent = r.scanned_at ? new Date(r.scanned_at).toLocaleString() : '';
          } else if (col.id === 'type') {
            td.innerHTML = r.type === 'in'
              ? '<span class="badge ok">in</span>'
              : '<span class="badge no">out</span>';
          } else {
            td.textContent = r[col.id] == null ? '' : String(r[col.id]);
          }
          tr.appendChild(td);
        }
        tbody.appendChild(tr);
      }

      // Sıralama göstergesini güncelle
      headerRow.querySelectorAll('th.sortable').forEach(th => th.removeAttribute('data-sort'));
      const idx = visibleColumns().findIndex(c => c.id === state.sort.by);
      if (idx >= 0) headerRow.children[idx]?.setAttribute('data-sort', state.sort.dir);
    }

    function exportAllToCsv(){
      const cols = columns.map(c=>c.id);
      const headers = columns.map(c=>c.label);
      const rows = DATA.map(row => cols.map(id => {
        if (id === 'scanned_at' && row[id]) return new Date(row[id]).toISOString();
        return row[id] ?? '';
      }));
      const csv = toCsv([headers, ...rows]);
      const blob = new Blob(['\uFEFF' + csv], { type:'text/csv;charset=utf-8;' });
      triggerDownload(blob, 'attendance_report.csv');
    }

    // Yardımcılar
    function toCsv(rows){ return rows.map(r => r.map(csvEscape).join(',')).join('\r\n'); }
    function csvEscape(v){ const s=String(v??''); return /[",\n]/.test(s)?'"'+s.replace(/"/g,'""')+'"':s; }
    function triggerDownload(blob, filename){ const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); setTimeout(()=>URL.revokeObjectURL(a.href),1000); }

    function escapeHtml(str){ return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    function escapeAttr(str){ return escapeHtml(String(str)).replace(/\n/g,' '); }
    function debounce(fn, wait){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; }
  })();
  </script>
  <?php $pageScripts = ob_get_clean(); ?>
<?php else: ?>
  <div class="alert alert-light border d-flex align-items-center" role="alert">
    <i class="bi bi-inboxes me-2"></i> Hiç kayıt bulunamadı.
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';