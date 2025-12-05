<?php use App\Core\Helpers; ?>

<h1><?= Helpers::e(isset($title) ? $title : 'Giriş/Çıkış Raporu') ?></h1>

<form method="get" action="/attendance/report" style="margin:.5rem 0 1rem; display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">

<input type="hidden" name="page" value="1">

<label style="display:flex; flex-direction:column; font-size:.9rem;">

From

<input type="date" name="from" value="<?= Helpers::e(isset($filters['from']) ? $filters['from'] : '') ?>">

</label>

<label style="display:flex; flex-direction:column; font-size:.9rem;">

To

<input type="date" name="to" value="<?= Helpers::e(isset($filters['to']) ? $filters['to'] : '') ?>">

</label>

<label style="display:flex; flex-direction:column; font-size:.9rem;">

Tür

<select name="type">

<option value="">— Tümü —</option>

<option value="in" <?= ((isset($filters['type']) ? $filters['type'] : '')==='in')?'selected':''; ?>>in</option>

<option value="out" <?= ((isset($filters['type']) ? $filters['type'] : '')==='out')?'selected':''; ?>>out</option>

</select>

</label>

<label style="display:flex; flex-direction:column; font-size:.9rem; min-width:220px;">

Firma

<select name="company_id">

<option value="">— Tümü —</option>

<?php foreach ((isset($companies) ? $companies : []) as $c): ?>

<option value="<?= (int)$c['id'] ?>" <?= ((string)(isset($filters['company_id']) ? $filters['company_id'] : '') === (string)$c['id']) ? 'selected' : '' ?>>

<?= Helpers::e($c['name']) ?>

</option>

<?php endforeach; ?>

</select>

</label>

<label style="display:flex; flex-direction:column; font-size:.9rem;">

Ad/Soyad

<input type="text" name="name" value="<?= Helpers::e(isset($filters['name']) ? $filters['name'] : '') ?>" placeholder="Ad veya soyad">

</label>

<label style="display:flex; flex-direction:column; font-size:.9rem;">

Cihaz

<input type="text" name="device" value="<?= Helpers::e(isset($filters['device']) ? $filters['device'] : '') ?>" placeholder="Cihaz adı">

</label>

<button class="btn" type="submit">Filtrele</button>

<button class="btn" type="button" id="toggleColumnPanel">Kolonları Yönet</button>

<button class="btn" type="button" id="resetView">Görünümü Sıfırla</button>

<span class="spacer"></span>

<button class="btn" type="button" id="exportExcel">Excele Aktar</button>

</form>

<?php

$rowsJson = json_encode(isset($rows) ? $rows : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>

<?php if (!empty($rows)): ?>

<div class="table-wrap">

<div id="columnPanel" class="column-panel" hidden>

<strong>Görünen Kolonlar</strong>

<div id="columnCheckboxes" class="columns"></div>

</div>

<table class="table" id="attTable">

<thead>

<tr id="headerRow"></tr>

<tr id="filtersRow"></tr>

</thead>

<tbody id="tableBody"></tbody>

</table>

<?php

$total = (int)(isset($total) ? $total : 0);

$page = (int)(isset($page) ? $page : 1);

$limit = (int)(isset($limit) ? $limit : 200);

$pages = max(1, (int)ceil($total / max(1,$limit)));

$qs = $_GET; unset($qs['page']);

function buildUrl($p) { $qs = $_GET; $qs['page'] = $p; return '/attendance/report?' . http_build_query($qs); }

?>

<div style="margin-top:.75rem; display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">

<div>Toplam: <strong><?= $total ?></strong> | Sayfa: <?= $page ?>/<?= $pages ?></div>

<div class="spacer"></div>

<div class="pagination">

<?php if ($page > 1): ?>

<a class="btn" href="<?= Helpers::e(buildUrl(1)) ?>">« İlk</a>

<a class="btn" href="<?= Helpers::e(buildUrl($page-1)) ?>">‹ Önceki</a>

<?php endif; ?>

<?php if ($page < $pages): ?>

<a class="btn" href="<?= Helpers::e(buildUrl($page+1)) ?>">Sonraki ›</a>

<a class="btn" href="<?= Helpers::e(buildUrl($pages)) ?>">Son »</a>

<?php endif; ?>

</div>

</div>

</div>

<script>

(function(){

var DATA = <?php echo $rowsJson ? $rowsJson : '[]'; ?>;

var columns = [

{ id:'id', label:'ID', filterType:'text', className:'col-id' },

{ id:'scanned_at', label:'Zaman', filterType:'date' },

{ id:'type', label:'Tür', filterType:'text', className:'col-type' },

{ id:'first_name', label:'Ad', filterType:'text' },

{ id:'last_name', label:'Soyad', filterType:'text' },

{ id:'company_name', label:'Firma', filterType:'text' },

{ id:'source_device', label:'Cihaz', filterType:'text' },

{ id:'notes', label:'Notlar', filterType:'text' },

{ id:'user_id', label:'User ID', filterType:'text', className:'col-id' }

];

var defaultVisible = ['id','scanned_at','type','first_name','last_name','company_name','source_device','notes'];

var LS_KEYS = { visibleCols:'att.visibleCols', filters:'att.filters', sort:'att.sort' };

var state = {

visibleCols: loadVisibleCols(),

filters: loadFilters(),

sort: loadSort()

};

var headerRow = document.getElementById('headerRow');

var filtersRow = document.getElementById('filtersRow');

var tbody = document.getElementById('tableBody');

var columnPanel = document.getElementById('columnPanel');

var columnCheckboxes = document.getElementById('columnCheckboxes');

document.getElementById('toggleColumnPanel').addEventListener('click', function(){ columnPanel.hidden = !columnPanel.hidden; });

document.getElementById('resetView').addEventListener('click', function(){

state.visibleCols = defaultVisible.slice();

state.filters = {};

state.sort = { by:'scanned_at', dir:'desc' };

saveAll(); buildColumnPanel(); buildHead(); render();

});

document.getElementById('exportExcel').addEventListener('click', exportAllToCsv);

buildColumnPanel();

buildHead();

render();

function loadVisibleCols() {

try {

var raw = localStorage.getItem(LS_KEYS.visibleCols);

var arr = raw ? JSON.parse(raw) : null;

var cols = (arr && Object.prototype.toString.call(arr) === '[object Array]') ? arr : defaultVisible.slice();

cols = cols.filter(function(id){

for (var i=0;i<columns.length;i++){ if (columns[i].id === id) return true; }

return false;

});

return cols;

} catch (e) { return defaultVisible.slice(); }

}

function loadFilters() { try { var v = localStorage.getItem(LS_KEYS.filters) || '{}'; return JSON.parse(v); } catch (e) { return {}; } }

function loadSort() {

try {

var s = JSON.parse(localStorage.getItem(LS_KEYS.sort) || '{"by":"scanned_at","dir":"desc"}');

var exists = false;

for (var i=0;i<columns.length;i++){ if (columns[i].id === s.by) { exists=true; break; } }

return exists ? s : { by:'scanned_at', dir:'desc' };

} catch (e) { return { by:'scanned_at', dir:'desc' }; }

}

function saveAll() {

localStorage.setItem(LS_KEYS.visibleCols, JSON.stringify(state.visibleCols));

localStorage.setItem(LS_KEYS.filters, JSON.stringify(state.filters));

localStorage.setItem(LS_KEYS.sort, JSON.stringify(state.sort));

}

function visibleColumns() {

var list = [];

for (var i=0;i<state.visibleCols.length;i++){

var id = state.visibleCols[i];

for (var j=0;j<columns.length;j++){

if (columns[j].id === id) { list.push(columns[j]); break; }

}

}

return list;

}

function buildColumnPanel() {

columnCheckboxes.innerHTML = '';

for (var i=0;i<columns.length;i++){

var col = columns[i];

var id = 'colchk_' + col.id;

var wrap = document.createElement('label');

wrap.className = 'colchk';

wrap.innerHTML =

'<input type="checkbox" id="'+id+'" '+(state.visibleCols.indexOf(col.id) !== -1 ? 'checked' : '')+'>' +

'<span>'+col.label+'</span>';

wrap.querySelector('input').addEventListener('change', (function(colId){

return function(e){

var on = e.target.checked;

if (on) {

if (state.visibleCols.indexOf(colId) === -1) state.visibleCols.push(colId);

} else {

var tmp = [];

for (var k=0;k<state.visibleCols.length;k++){

if (state.visibleCols[k] !== colId) tmp.push(state.visibleCols[k]);

}

state.visibleCols = tmp;

}

saveAll(); buildHead(); render();

};

})(col.id));

columnCheckboxes.appendChild(wrap);

}

}

function buildHead() {

headerRow.innerHTML = '';

filtersRow.innerHTML = '';

var cols = visibleColumns();

for (var i=0;i<cols.length;i++){

var col = cols[i];

var th = document.createElement('th');

th.textContent = col.label;

th.className = col.className || '';

th.classList.add('sortable');

(function(colId){

th.addEventListener('click', function(){ toggleSort(colId); });

})(col.id);

if (state.sort.by === col.id) th.setAttribute('data-sort', state.sort.dir);

headerRow.appendChild(th);

var tf = document.createElement('th');

tf.className = 'filter-cell ' + (col.className || '');

if (col.filterType === 'date') {

var fromVal = (state.filters[col.id] && state.filters[col.id].from) ? state.filters[col.id].from : '';

var toVal = (state.filters[col.id] && state.filters[col.id].to) ? state.filters[col.id].to : '';

tf.innerHTML =

'<div class="filter-date">' +

'<input type="date" data-key="'+col.id+'" data-kind="from" value="'+escapeAttr(fromVal)+'">' +

'<input type="date" data-key="'+col.id+'" data-kind="to" value="'+escapeAttr(toVal)+'">' +

'</div>';

} else {

var cur = (state.filters[col.id] && state.filters[col.id].val) ? state.filters[col.id].val : '';

tf.innerHTML = '<input type="text" placeholder="Ara..." data-key="'+col.id+'" data-kind="text" value="'+escapeAttr(cur)+'">';

}

filtersRow.appendChild(tf);

}

var textInputs = filtersRow.querySelectorAll('input[data-kind="text"]');

for (var a=0; a<textInputs.length; a++){

(function(inp){

inp.addEventListener('input', debounce(function(){

var key = inp.getAttribute('data-key');

state.filters[key] = { val: inp.value };

saveAll(); render();

}, 200));

})(textInputs[a]);

}

var dateInputs = filtersRow.querySelectorAll('input[data-kind="from"], input[data-kind="to"]');

for (var b=0; b<dateInputs.length; b++){

(function(inp){

inp.addEventListener('change', function(){

var key = inp.getAttribute('data-key');

var kind = inp.getAttribute('data-kind');

state.filters[key] = state.filters[key] || {};

state.filters[key][kind] = inp.value;

saveAll(); render();

});

})(dateInputs[b]);

}

}

function toggleSort(colId) {

if (state.sort.by === colId) {

state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';

} else {

state.sort.by = colId;

state.sort.dir = 'asc';

}

saveAll(); buildHead(); render();

}

function applyFilters(rows) {

var out = [];

rowLoop:

for (var i=0;i<rows.length;i++){

var r = rows[i];

for (var j=0;j<columns.length;j++){

var col = columns[j];

var f = state.filters[col.id];

if (!f) continue;

if (col.filterType === 'text') {

var needle = (f.val ? String(f.val) : '').trim().toLowerCase();

if (needle) {

var hay = String(r[col.id] != null ? r[col.id] : '').toLowerCase();

if (hay.indexOf(needle) === -1) continue rowLoop;

}

} else if (col.filterType === 'date') {

var from = f.from ? Date.parse(f.from) : null;

var to = f.to ? Date.parse(f.to) : null;

var v = Date.parse(r[col.id] != null ? r[col.id] : '');

if (from && !(v >= from)) continue rowLoop;

if (to && !(v <= to)) continue rowLoop;

}

}

out.push(r);

}

return out;

}

function applySort(rows) {

var by = state.sort.by;

var dir = state.sort.dir === 'asc' ? 1 : -1;

return rows.slice().sort(function(a,b){

var av = String(a[by] != null ? a[by] : '');

var bv = String(b[by] != null ? b[by] : '');

if (av < bv) return -1 * dir;

if (av > bv) return 1 * dir;

return 0;

});

}

function render() {

var cols = visibleColumns();

var filtered = applyFilters(DATA);

var sorted = applySort(filtered);

tbody.innerHTML = '';

for (var i=0;i<sorted.length;i++){

var r = sorted[i];

var tr = document.createElement('tr');

for (var j=0;j<cols.length;j++){

var col = cols[j];

var td = document.createElement('td');

td.className = col.className || '';

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

var headThs = headerRow.querySelectorAll('th.sortable');

for (var k=0;k<headThs.length;k++){ headThs[k].removeAttribute('data-sort'); }

var idx = -1;

var vis = visibleColumns();

for (var m=0;m<vis.length;m++){ if (vis[m].id === state.sort.by) { idx = m; break; } }

if (idx >= 0) {

var th = headerRow.children[idx];

if (th) th.setAttribute('data-sort', state.sort.dir);

}

}

function exportAllToCsv() {

var cols = [];

for (var i=0;i<columns.length;i++){ cols.push(columns[i].id); }

var headers = [];

for (var j=0;j<columns.length;j++){ headers.push(columns[j].label); }

var rows = [];

for (var r=0;r<DATA.length;r++){

var row = [];

for (var c=0;c<cols.length;c++){

var id = cols[c];

var cell = (id==='scanned_at' && DATA[r][id]) ? new Date(DATA[r][id]).toISOString() : (DATA[r][id] == null ? '' : DATA[r][id]);

row.push(cell);

}

rows.push(row);

}

var merged = [headers];

for (var x=0;x<rows.length;x++) merged.push(rows[x]);

var csv = toCsv(merged);

var blob = new Blob(['\uFEFF' + csv], { type:'text/csv;charset=utf-8;' });

triggerDownload(blob, 'attendance_report.csv');

}

function toCsv(rows){

var out = [];

for (var i=0;i<rows.length;i++){

var line = [];

for (var j=0;j<rows[i].length;j++) line.push(csvEscape(rows[i][j]));

out.push(line.join(','));

}

return out.join('\r\n');

}

function csvEscape(v){

var s = String(v == null ? '' : v);

return /[",\n]/.test(s) ? '"' + s.replace(/"/g,'""') + '"' : s;

}

function triggerDownload(blob, filename){

var a = document.createElement('a');

a.href = URL.createObjectURL(blob);

a.download = filename;

a.click();

setTimeout(function(){ URL.revokeObjectURL(a.href); }, 1000);

}

function escapeHtml(str){

var s = String(str);

s = s.replace(/&/g,'&amp;');

s = s.replace(/</g,'&lt;');

s = s.replace(/>/g,'&gt;');

s = s.replace(/"/g,'&quot;');

s = s.replace(/'/g,'&#39;');

return s;

}

function escapeAttr(str){ return escapeHtml(String(str)).split('\n').join(' '); }

function debounce(fn, wait){

var t;

return function(){

var ctx=this, args=arguments;

clearTimeout(t);

t=setTimeout(function(){ fn.apply(ctx, args); }, wait);

};

}

})();

</script>

<?php else: ?>

<p>Hiç kayıt bulunamadı.</p>

<?php endif; ?>

<style>

.table-wrap { overflow:auto; }

.table { border-collapse: collapse; width: 100%; min-width: 1000px; table-layout: fixed; }

.table th, .table td { border:1px solid #ddd; padding:.5rem .6rem; text-align:left; vertical-align:top; }

.table thead th { background:#f8f8f8; position: sticky; top: 0; z-index: 2; }

.table thead tr:nth-child(2) th { top: 34px; background:#fcfcfc; z-index: 1; }

th.sortable { cursor:pointer; padding-right:1.2rem; position: sticky; }

th.sortable::after { content:'↕'; position:absolute; right:.4rem; color:#888; font-size:.9em; }

th.sortable[data-sort="asc"]::after { content:'↑'; color:#333; }

th.sortable[data-sort="desc"]::after { content:'↓'; color:#333; }

#filtersRow th { padding:.35rem .4rem; }

#filtersRow input[type="text"], #filtersRow input[type="date"], #filtersRow select {

width:100%; box-sizing:border-box; padding:.35rem .45rem; border:1px solid #ccc; border-radius:.375rem;

}

.filter-date { display:grid; grid-template-columns:1fr 1fr; gap:.25rem; }

.btn { display:inline-block; padding:.25rem .5rem; border:1px solid #888; border-radius:.375rem; text-decoration:none; color:#222; background:#fafafa; cursor:pointer; }

.btn:hover { background:#f0f0f0; }

.badge.ok { background:#e8f6ec; color:#1b7f3b; padding:.1rem .35rem; border-radius:.4rem; border:1px solid #bfe5cc; }

.badge.no { background:#fff2f2; color:#b51e1e; padding:.1rem .35rem; border-radius:.4rem; border:1px solid #f1c0c0; }

.spacer { flex:1 1 auto; }

.column-panel { border: 1px solid #ddd; padding: .5rem; border-radius: .5rem; margin-bottom: .5rem; background:#fafafa; }

.column-panel .columns { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .35rem .75rem; margin-top: .5rem; }

.column-panel .colchk { display: flex; align-items: center; gap: .4rem; }

</style>