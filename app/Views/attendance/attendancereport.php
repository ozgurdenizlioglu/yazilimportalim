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

<div class="d-flex flex-wrap gap-2">

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

<?php

$rowsJson = json_encode($rows ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>

<?php if (!empty($rows)): ?>

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
    <table class="table table-hover align-middle mb-0" id="attTable">
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
          <form id="uploadSubmitForm" method="post" action="/attendance/bulk-upload" enctype="multipart/form-data" class="ms-auto">
            <input type="hidden" name="payload" id="uploadPayload">
            <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Sunucuya Yükle</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div> <!-- table-wrap -->
</div> <!-- card-body -->

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

@media (min-width: 576px) { .columns-grid { grid-template-columns: repeat(3, minmax(180px, 1fr)); } }

@media (min-width: 768px) { .columns-grid { grid-template-columns: repeat(4, minmax(180px, 1fr)); } }

@media (min-width: 992px) { .columns-grid { grid-template-columns: repeat(5, minmax(180px, 1fr)); } }

@media (min-width: 1200px){ .columns-grid { grid-template-columns: repeat(6, minmax(180px, 1fr)); } }

#attTable {

table-layout: auto;

border-collapse: separate;

border-spacing: 0;

}

#attTable thead { vertical-align: bottom; }

#attTable thead th {

position: relative;

background-clip: padding-box;

white-space: nowrap;

}

#attTable thead tr#filtersRow th {

padding: .25rem .5rem;

border-bottom: 0 !important;

}

#attTable thead tr#headerRow th {

padding-top: .25rem;

padding-bottom: .4rem;

border-bottom: 1px solid var(--bs-border-color) !important;

vertical-align: bottom;

}

#attTable thead .filter-cell > * {

display: block;

width: 100%;

max-width: 100%;

margin: 0;

}

#attTable thead input.form-control-sm,

#attTable thead select.form-select-sm {

min-height: 32px;

line-height: 1.2;

}

#attTable th.col-actions { padding-left: .25rem; padding-right: .25rem; }

#attTable th .col-resizer {

position: absolute; top: 0; right: 0; width: 10px; height: 100%;

cursor: col-resize; user-select: none; -webkit-user-select: none;

}

#attTable th.resizing,

#attTable th .col-resizer.active {

background-image: linear-gradient(to bottom, rgba(45,108,223,.15), rgba(45,108,223,.15));

background-repeat: no-repeat; background-position: right center; background-size: 2px 100%;

}

.btn-icon {

--btn-size: 28px; width: var(--btn-size); height: var(--btn-size);

padding: 0 !important; display: inline-flex; align-items: center; justify-content: center; border-radius: .25rem;

}

.btn-icon.btn-light { border: 1px solid (var(--bs-border-color)); }

.btn-icon i { font-size: 14px; }

#attTable td .btn-group { gap: 4px; }

#attTable td .btn-group .btn { border-width: 1px; }

#attTable thead th.sortable { cursor: pointer; }

#attTable thead th.sortable[data-sort="asc"]::after { content: " ↑"; opacity: .6; }

#attTable thead th.sortable[data-sort="desc"]::after { content: " ↓"; opacity: .6; }

/* Rozetler */

.badge.ok { background: var(--bs-success-bg-subtle, #d1e7dd); color: var(--bs-success-text, #0f5132); border: 1px solid var(--bs-success-border-subtle, #badbcc); }

.badge.no { background: var(--bs-danger-bg-subtle, #f8d7da); color: var(--bs-danger-text, #842029); border: 1px solid var(--bs-danger-border-subtle, #f5c2c7); }

</style>

<?php ob_start(); ?>

<script>

(function() {

// Veri

const DATA = <?= $rowsJson ?: '[]' ?>;

// Alanlar (export/template/upload için)

const allFields = [

{ id:'id', label:'ID' },

{ id:'scanned_at', label:'Zaman' },

{ id:'type', label:'Tür' }, // in|out

{ id:'user_id', label:'User ID' },

{ id:'first_name', label:'Ad' },

{ id:'last_name', label:'Soyad' },

{ id:'company_id', label:'Firma ID' },

{ id:'company_name', label:'Firma' },

{ id:'source_device', label:'Cihaz' },

{ id:'notes', label:'Notlar' },

{ id:'created_at', label:'Oluşturma' },

{ id:'updated_at', label:'Güncelleme' },

];

// Kolonlar (UI)

const columns = [

{ id:'id', label:'ID', filterType:'text', className:'text-end' },

{ id:'scanned_at', label:'Zaman', filterType:'date' },

{ id:'type', label:'Tür', filterType:'text' },

{ id:'first_name', label:'Ad', filterType:'text' },

{ id:'last_name', label:'Soyad', filterType:'text' },

{ id:'company_name', label:'Firma', filterType:'text' },

{ id:'company_id', label:'Firma ID', filterType:'text', className:'text-end' },

{ id:'source_device', label:'Cihaz', filterType:'text' },

{ id:'notes', label:'Notlar', filterType:'text' },

{ id:'user_id', label:'User ID', filterType:'text', className:'text-end' },

{ id:'created_at', label:'Oluşturma', filterType:'date' },

{ id:'updated_at', label:'Güncelleme', filterType:'date' },

];

const defaultVisible = ['id','scanned_at','type','first_name','last_name','company_name','source_device','notes'];

const LS_KEYS = {

visibleCols: 'att.visibleCols',

filters: 'att.filters',

sort: 'att.sort',

widths: 'att.widths',

page: 'att.page',

limit: 'att.limit'

};

// DOM

const table = document.getElementById('attTable');

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

// UI eventleri

document.getElementById('toggleColumnPanel').addEventListener('click', () => columnPanel.hidden = !columnPanel.hidden);

document.getElementById('resetView').addEventListener('click', () => {

state.visibleCols = [...defaultVisible];

state.filters = {};

state.sort = { by: 'scanned_at', dir: 'desc' };

state.widths = {};

state.page = 1;

state.limit = 50;

saveAll(); buildColumnPanel(); rebuildHeadAndCols(); render();

});

document.getElementById('downloadTemplate').addEventListener('click', downloadTemplate);

document.getElementById('exportExcel').addEventListener('click', exportAllToCsv);

document.getElementById('uploadFile').addEventListener('change', handleUpload);

// Pager

pager.addEventListener('click', (e)=>{

const a = e.target.closest('a[data-page]');

if (!a) return;

e.preventDefault();

const action = a.dataset.page;

const { totalPages } = currentTotals();

if (action === 'first') state.page = 1;

else if (action === 'prev') state.page = Math.max(1, state.page - 1);

else if (action === 'next') state.page = Math.min(totalPages, state.page + 1);

else if (action === 'last') state.page = totalPages;

savePageAndLimit();

render();

});

initPageSizeSelect();

pageSizeSelect.addEventListener('change', ()=>{

state.limit = Number(pageSizeSelect.value) || 50;

state.page = 1;

savePageAndLimit();

render();

});

// Başlat

buildColumnPanel();

rebuildHeadAndCols();

render();

// Storage

function loadVisibleCols(){

try {

const raw = localStorage.getItem(LS_KEYS.visibleCols);

const arr = raw ? JSON.parse(raw) : null;

let cols = arr && Array.isArray(arr) ? arr : defaultVisible;

cols = cols.filter(id => columns.some(c => c.id === id));

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

function loadWidths(){ try { return JSON.parse(localStorage.getItem(LS_KEYS.widths) || '{}') || {}; } catch { return {}; } }

function loadPage(){

try {

const fromQs = Number(new URLSearchParams(location.search).get('page'));

if (!Number.isNaN(fromQs) && fromQs > 0) return fromQs;

const p = Number(localStorage.getItem(LS_KEYS.page));

return !Number.isNaN(p) && p > 0 ? p : 1;

} catch { return 1; }

}

function loadLimit(){

try {

const fromQs = Number(new URLSearchParams(location.search).get('limit'));

if (!Number.isNaN(fromQs) && fromQs > 0) return fromQs;

const l = Number(localStorage.getItem(LS_KEYS.limit));

return !Number.isNaN(l) && l > 0 ? l : 50;

} catch { return 50; }

}

function saveAll(){

localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));

localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));

localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));

localStorage.setItem(LS_KEYS.widths, JSON.stringify(state.widths));

savePageAndLimit();

}

function savePageAndLimit(){

localStorage.setItem(LS_KEYS.page, String(state.page));

localStorage.setItem(LS_KEYS.limit, String(state.limit));

}

// Yapı koruması

function normalizeTableStructure(){

if (!table.tHead) {

const th = document.createElement('thead');

table.insertBefore(th, table.firstChild);

}

const th = table.tHead;

if (headerRow.parentNode !== th) th.appendChild(headerRow);

if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);

if (!table.tBodies || table.tBodies.length === 0) table.appendChild(tbody);

const tb = table.tBodies[0];

tb.querySelectorAll('tr#headerRow, tr#filtersRow').forEach(tr=>{

if (tr.id === 'filtersRow') th.insertBefore(tr, th.firstChild);

else th.appendChild(tr);

});

Array.from(th.children).forEach(node => { if (node.nodeName !== 'TR') node.remove(); });

}

function visibleColumns(){ return state.visibleCols.map(id => columns.find(c => c.id === id)).filter(Boolean); }

// Kolon paneli

function buildColumnPanel(){

columnCheckboxes.innerHTML = '';

columns.forEach(col=>{

const id = `colchk_${col.id}`;

const wrap = document.createElement('label');

wrap.className = 'form-check d-flex align-items-center gap-2';

wrap.innerHTML = `

<input class="form-check-input" type="checkbox" id="${id}" ${state.visibleCols.includes(col.id) ? 'checked' : ''}>

<span class="form-check-label">${col.label}</span>

`;

wrap.querySelector('input').addEventListener('change', (e)=>{

const on = e.target.checked;

if (on) { if (!state.visibleCols.includes(col.id)) state.visibleCols.push(col.id); }

else { state.visibleCols = state.visibleCols.filter(x => x !== col.id); }

saveAll();

rebuildHeadAndCols();

state.page = 1;

savePageAndLimit();

render();

});

columnCheckboxes.appendChild(wrap);

});

}

function initPageSizeSelect(){

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

function rebuildHeadAndCols(){

normalizeTableStructure();

const th = table.tHead;

if (headerRow.parentNode !== th) th.appendChild(headerRow);

if (filtersRow.parentNode !== th) th.insertBefore(filtersRow, th.firstChild);

headerRow.replaceChildren();

filtersRow.replaceChildren();

colGroup.replaceChildren();

const cols = visibleColumns();

cols.forEach((col)=>{

const c = document.createElement('col');

c.dataset.colId = col.id;

const savedW = Number(state.widths[col.id] || 0);

if (savedW > 0) c.style.width = savedW + 'px';

colGroup.appendChild(c);

const thCell = document.createElement('th');

thCell.textContent = col.label;

if (col.className) thCell.className = col.className;

thCell.classList.add('sortable');

if (state.sort.by === col.id) thCell.dataset.sort = state.sort.dir;

thCell.addEventListener('click', (ev)=>{

if ((ev.target).classList?.contains('col-resizer')) return;

toggleSort(col.id);

});

const resizer = document.createElement('div');

resizer.className = 'col-resizer';

resizer.addEventListener('mousedown', (e)=>startResize(e, col.id));

thCell.appendChild(resizer);

headerRow.appendChild(thCell);

const tf = document.createElement('th');

tf.className = 'filter-cell';

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

filtersRow.appendChild(tf);

});

// Filtre bindings

filtersRow.querySelectorAll('input[data-kind="text"]').forEach(inp=>{

inp.addEventListener('input', debounce(()=>{

const key = inp.dataset.key;

state.filters[key] = { val: inp.value };

state.page = 1;

saveAll(); render();

}, 200));

});

filtersRow.querySelectorAll('select[data-kind="bool"]').forEach(sel=>{

sel.addEventListener('change', ()=>{

const key = sel.dataset.key;

state.filters[key] = { val: sel.value };

state.page = 1;

saveAll(); render();

});

});

filtersRow.querySelectorAll('input[data-kind="from"], input[data-kind="to"]').forEach(inp=>{

inp.addEventListener('change', ()=>{

const key = inp.dataset.key, kind = inp.dataset.kind;

state.filters[key] = state.filters[key] || {};

state.filters[key][kind] = inp.value;

state.page = 1;

saveAll(); render();

});

});

normalizeTableStructure();

}

// Sütun genişliği

function startResize(e, colId){

e.preventDefault();

const startX = e.pageX;

const colEl = [...colGroup.children].find(c => c.dataset.colId === colId);

const startWidth = (colEl && colEl.style.width) ? parseInt(colEl.style.width, 10) : getComputedWidth(colId);

const min = colId === 'id' || colId === 'user_id' || colId === 'company_id' ? 56 : 70;

const th = [...headerRow.children][visibleColumns().findIndex(c => c.id === colId)];

if (th) th.classList.add('resizing');

const resizer = th?.querySelector('.col-resizer');

if (resizer) resizer.classList.add('active');

function onMouseMove(ev){

const dx = ev.pageX - startX;

const newW = Math.max(min, Math.round(startWidth + dx));

if (colEl) colEl.style.width = newW + 'px';

}

function onMouseUp(){

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

function getComputedWidth(colId){

const idx = visibleColumns().findIndex(c=>c.id===colId);

if (idx < 0) return 100;

const th = headerRow.children[idx];

if (!th) return 100;

return Math.round(th.getBoundingClientRect().width);

}

// Sıralama

function toggleSort(colId){

if (state.sort.by === colId) state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';

else { state.sort.by = colId; state.sort.dir = 'asc'; }

state.page = 1;

saveAll();

rebuildHeadAndCols();

render();

}

// Filtre + sıralama

function applyFilters(rows){

return rows.filter(r=>{

for (const col of columns){

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

function applySort(rows){

const col = columns.find(c => c.id === state.sort.by);

if (!col) return rows;

const dir = state.sort.dir === 'asc' ? 1 : -1;

return [...rows].sort((a,b)=>{

const avRaw = a[col.id];

const bvRaw = b[col.id];

if (['id','user_id','company_id'].includes(col.id)) {

const av = Number(avRaw ?? 0), bv = Number(bvRaw ?? 0);

return (av < bv ? -1 : av > bv ? 1 : 0) * dir;

}

if (col.id === 'scanned_at' || col.filterType === 'date') {

const at = Date.parse(avRaw ?? '') || 0;

const bt = Date.parse(bvRaw ?? '') || 0;

return (at < bt ? -1 : at > bt ? 1 : 0) * dir;

}

const av = String(avRaw ?? '').toLowerCase();

const bv = String(bvRaw ?? '').toLowerCase();

if (av < bv) return -1 * dir;

if (av > bv) return 1 * dir;

return 0;

});

}

// Toplam ve sayfa bilgisi

function currentTotals(){

const filtered = applyFilters(DATA);

const total = filtered.length;

const totalPages = Math.max(1, Math.ceil(total / Math.max(1, state.limit)));

if (state.page > totalPages) state.page = totalPages;

return { filtered, total, totalPages };

}

// Render

function render(){

normalizeTableStructure();

const cols = visibleColumns();

const { filtered, total, totalPages } = currentTotals();

const sorted = applySort(filtered);

const startIndex = (Math.max(1, state.page) - 1) * Math.max(1, state.limit);

const pageRows = sorted.slice(startIndex, startIndex + state.limit);

tbody.innerHTML = '';

for (const r of pageRows){

const tr = document.createElement('tr');

for (const col of cols){

const td = document.createElement('td');

if (col.className) td.className = col.className;

if (col.id === 'scanned_at') {

const v = r.scanned_at;

td.textContent = v ? new Date(v).toLocaleString() : '';

} else if (col.id === 'type') {

const t = String(r.type ?? '').toLowerCase();

td.innerHTML = t === 'in'

? '<span class="badge ok">in</span>'

: '<span class="badge no">out</span>';

} else {

td.textContent = r[col.id] == null ? '' : String(r[col.id]);

}

tr.appendChild(td);

}

tbody.appendChild(tr);

}

// Sort indikatörü

headerRow.querySelectorAll('th.sortable').forEach(th => th.removeAttribute('data-sort'));

const idx = visibleColumns().findIndex(c => c.id === state.sort.by);

if (idx >= 0) headerRow.children[idx].dataset.sort = state.sort.dir;

// Footer/pager

footerStats.innerHTML = `Toplam: <strong>${total}</strong> | Sayfa: ${state.page}/${totalPages}`;

pageIndicator.textContent = `${state.page} / ${totalPages}`;

setPagerState(totalPages);

updateQueryParams({ page: state.page, limit: state.limit });

}

function setPagerState(totalPages){

const firstLi = pager.querySelector('a[data-page="first"]').closest('.page-item');

const prevLi = pager.querySelector('a[data-page="prev"]').closest('.page-item');

const nextLi = pager.querySelector('a[data-page="next"]').closest('.page-item');

const lastLi = pager.querySelector('a[data-page="last"]').closest('.page-item');

if (state.page <= 1){ firstLi.classList.add('disabled'); prevLi.classList.add('disabled'); }

else { firstLi.classList.remove('disabled'); prevLi.classList.remove('disabled'); }

if (state.page >= totalPages){ nextLi.classList.add('disabled'); lastLi.classList.add('disabled'); }

else { nextLi.classList.remove('disabled'); lastLi.classList.remove('disabled'); }

}

// Export / Template / Upload

function exportAllToCsv(){

const exportCols = allFields;

const headers = exportCols.map(c => c.id);

const rows = DATA.map(r => exportCols.map(c => formatForCsv(c.id, r[c.id])));

const csv = toCsv([headers, ...rows]);

const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });

triggerDownload(blob, 'attendance_full_export.csv');

}

function downloadTemplate(){

const headers = allFields.map(c => c.id);

const sample1 = {

id:'', scanned_at:'2025-01-01T08:30:00Z', type:'in',

user_id:'123', first_name:'Ahmet', last_name:'Yılmaz',

company_id:'', company_name:'Örnek Firma AŞ',

source_device:'Turnike-1',

notes:'', created_at:'', updated_at:''

};

const sample2 = {

id:'', scanned_at:'2025-01-01T17:45:00Z', type:'out',

user_id:'123', first_name:'Ahmet', last_name:'Yılmaz',

company_id:'', company_name:'Örnek Firma AŞ',

source_device:'Turnike-1',

notes:'Geç çıkış', created_at:'', updated_at:''

};

const rows = [headers, ...[sample1, sample2].map(s => headers.map(h => formatForCsv(h, s[h])))];

const csv = toCsv(rows);

const blob = new Blob(['\uFEFF' + csv], { type:'text/csv;charset=utf-8;' });

triggerDownload(blob, 'attendance_template_full.csv');

}

async function handleUpload(e){

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

} catch (err){

alert('Dosya okunamadı: ' + (err?.message || err));

e.target.value = '';

}

}

// CSV ve yardımcılar

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

return new Promise((resolve)=>{

const reader = new FileReader();

reader.onerror = () => resolve([['Okuma hatası']]);

reader.onload = ()=>{

const text = String(reader.result || '');

if (text.includes(',') || text.includes(';')) resolve(parseCsv(text));

else resolve([['Uyarı: XLSX istemci tarafında parse edilmedi. CSV kullanın ya da XLSX desteği ekleyin.']]);

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

• type: in|out

• scanned_at: ISO datetime (ör. 2025-01-01T08:30:00Z)

• date alanları: ISO (created_at/updated_at)

• sayısal id alanları: id, user_id, company_id

</div>

`;

return { html };

}

function toCsv(rows){ return rows.map(row => row.map(csvEscape).join(',')).join('\r\n'); }

function csvEscape(v){ const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replaceAll('"','""')}"` : s; }

function triggerDownload(blob, filename){ const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); setTimeout(()=>URL.revokeObjectURL(a.href),1000); }

function escapeHtml(str){ return String(str).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;'); }

function escapeAttr(str){ return escapeHtml(str).replaceAll('\n',' '); }

function debounce(fn, wait){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; }

function formatForCsv(field, value){

if (value == null) return '';

if (field === 'scanned_at' || ['created_at','updated_at'].includes(field)) {

const d = new Date(value);

return isNaN(d) ? String(value) : d.toISOString();

}

return String(value);

}

function updateQueryParams(obj){

try {

const url = new URL(window.location.href);

if (obj.page) url.searchParams.set('page', String(obj.page));

if (obj.limit) url.searchParams.set('limit', String(obj.limit));

window.history.replaceState({}, '', url.toString());

} catch { /* no-op */ }

}

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