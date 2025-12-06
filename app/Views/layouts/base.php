<?php

use App\Core\Helpers;

$title = $title ?? 'Yönetim Paneli';

?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<title><?= Helpers::e($title) ?></title>

<style>
:root { --brand: #2563eb; }

body { background: #f6f7fb; }

/* Tablo sarmalayıcı – kaydırma burada */
.table-wrap { overflow: auto; }

/* Hızlı düzeltme:
   - Sticky sadece headerRow için aktif.
   - General sticky ve nth-child hack KALDIRILDI. */
.table thead th { position: static; } /* varsayılan davranış */
#usersTable thead tr#headerRow th {
  position: sticky;
  top: 0;
  z-index: 3;
  background: #f8f9fa;
}
/* Filtre satırı sticky olmasın (istenirse aşağıdaki satırları aktif et) */
#usersTable thead tr#filtersRow th {
  position: static;
  top: auto;
  z-index: auto;
  background: #fff;
}

/* Sadece imleç – sticky’yi th.sortable üzerinden VERME */
th.sortable { cursor: pointer; position: static; }

/* Sıralama ikonları (opsiyonel görsel ipucu) */
th.sortable::after { content: '↕'; float: right; color: #6c757d; font-size: .9em; }
th.sortable[data-sort="asc"]::after { content: '↑'; color: #212529; }
th.sortable[data-sort="desc"]::after { content: '↓'; color: #212529; }

/* Filtre satırı hücreleri ve input boyutları */
#filtersRow th { padding: .35rem .5rem; }
#filtersRow input[type="text"],
#filtersRow input[type="date"],
#filtersRow select {
  width: 100%;
  box-sizing: border-box;
  padding: .375rem .5rem;
}

/* Kolon paneli ve rozet yardımcıları */
.column-panel {
  border: 1px solid #e9ecef;
  padding: .75rem;
  border-radius: .5rem;
  background: #fff;
  margin-bottom: .75rem;
}
.column-panel .columns {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: .35rem .75rem;
}
.column-panel .colchk { display: flex; align-items: center; gap: .4rem; }

.badge.ok { background: #e6f4ea; color: #1f7a3f; border: 1px solid #bfe5cc; }
.badge.no { background: #fde8e8; color: #b42318; border: 1px solid #f1c0c0; }

.col-actions { width: 140px; min-width: 120px; }

td a.truncate {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navbar-toggler { border-color: rgba(0,0,0,.1); }
.navbar-toggler-icon { background-image: var(--bs-navbar-toggler-icon-bg); }

/* Hiyerarşik hizalama – sola yasla */
main.container-fluid,
main .container,
main .container-sm,
main .container-md,
main .container-lg,
main .container-xl,
main .container-xxl {
  max-width: none !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
}
.main-inner, .page-inner {
  padding-left: 12px !important;
  padding-right: 12px !important;
}
.page-inner > .page-title-bar,
.page-inner > .form-grid-wrapper,
.page-inner > form,
.page-inner > * {
  margin-left: 0 !important;
  margin-right: 0 !important;
}
@media (min-width: 1400px) {
  .main-inner, .page-inner { padding-left: 12px !important; padding-right: 12px !important; }
}
</style>
</head>

<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold text-primary" href="/">MyApp</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Menüyü Aç">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topnav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="/attendance/report">Giriş/Çıkış Raporu</a></li>
        <li class="nav-item"><a class="nav-link" href="/firms">Firmalar</a></li>
      </ul>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/logout"><i class="bi bi-box-arrow-right me-1"></i>Çıkış</a>
      </div>
    </div>
  </div>
</nav>

<main class="container-fluid py-4">
  <div class="main-inner">
    <?php if (!empty($flash = $_SESSION['flash'] ?? null)): ?>
      <div class="alert alert-<?= Helpers::e($flash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= Helpers::e($flash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
      </div>
      <?php unset($_SESSION['flash']); endif; ?>

    <div class="page-inner">
      <?= $content ?? '' ?>
    </div>
  </div>
</main>

<footer class="border-top py-3 text-center text-muted small">
  © <?= date('Y') ?> MyApp
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>