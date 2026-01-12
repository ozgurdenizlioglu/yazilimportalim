<?php

use App\Core\Helpers;

// Beklenen girişler:
// - $c: array (form değerleri; create için boş veya varsayılanlar)
// - $action: string (örn: "/projects/store" veya "/projects/update")
// - $submitLabel: string (örn: "Kaydet" veya "Güncelle")
// - $title: string (örn: "Proje Ekle" veya "Projeyi Düzenle")
// - $showIdHidden: bool (edit’te true, create’te false)
// - $backUrl: string (örn: "/projects")
// - $companies: mixed (opsiyonel; firma listesi. Dizi veya map. Örn: [ ['id'=>1,'name'=>'ABC'], ... ] ya da [1=>'ABC', 2=>'XYZ'])

$checked = function ($v) {
  if (is_bool($v)) return $v;
  if ($v === null) return false;
  $v = strtolower((string)$v);
  return in_array($v, ['1', 'true', 'on', 'yes', 'evet'], true);
};

// companies verisini normalize edelim (id => name map’e dönüştür)
$companyMap = [];
if (!empty($companies) && is_array($companies)) {
  foreach ($companies as $k => $v) {
    if (is_array($v) && isset($v['id'], $v['name'])) {
      $companyMap[(string)$v['id']] = (string)$v['name'];
    } elseif (is_object($v) && isset($v->id, $v->name)) {
      $companyMap[(string)$v->id] = (string)$v->name;
    } else {
      // id => name şeklinde verilmişse
      $companyMap[(string)$k] = (string)$v;
    }
  }
}
$currentCompanyId = isset($c['company_id']) ? (string)$c['company_id'] : '';

?>

<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>
  <div class="d-flex gap-2">
    <a href="<?= Helpers::e($backUrl ?? '/projects') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
  </div>
</div>

<form method="post" action="<?= Helpers::e($action) ?>" enctype="multipart/form-data" novalidate>
  <?php if (!empty($showIdHidden)): ?>
    <input type="hidden" name="id" value="<?= Helpers::e((string)($c['id'] ?? '')) ?>">
  <?php endif; ?>

  <style>
    .form-label {
      display: block;
      margin-bottom: .5rem;
      line-height: 1.3;
      font-weight: 600;
      font-size: 1rem;
    }

    .card .form-control,
    .card .form-select,
    .card textarea {
      margin-bottom: 1rem;
      padding: .75rem 1rem;
      font-size: 1rem;
    }

    .card {
      border-radius: .6rem;
    }

    .card-header {
      font-weight: 700;
      background: #f8f9fb;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
      justify-content: start !important;
      align-content: start;
      margin-left: 0 !important;
      margin-right: 0 !important;
    }

    @media (min-width: 992px) {
      .form-grid {
        grid-template-columns: minmax(640px, 1fr) minmax(640px, 1fr);
        gap: 2.5rem;
      }
    }

    .form-card {
      width: 100%;
    }
  </style>

  <div class="form-grid-wrapper">
    <div class="form-grid">
      <!-- Kimlik Bilgileri -->
      <div>
        <div class="card shadow-sm h-100 form-card">
          <div class="card-header"><strong>Kimlik Bilgileri</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <label class="form-label">Proje Adı (name) *</label>
                <input type="text" name="name" required maxlength="200"
                  value="<?= Helpers::e($c['name'] ?? '') ?>" class="form-control"
                  placeholder="Örn: Yeni Web Uygulaması">
              </div>
              <div class="col-12">
                <label class="form-label">Kısa Ad (short_name)</label>
                <input type="text" name="short_name" maxlength="100"
                  value="<?= Helpers::e($c['short_name'] ?? '') ?>" class="form-control"
                  placeholder="Örn: WebApp">
              </div>
              <div class="col-12">
                <label class="form-label">Proje Yolu/Path (project_path)</label>
                <input type="text" name="project_path" maxlength="200"
                  value="<?= Helpers::e($c['project_path'] ?? '') ?>" class="form-control"
                  placeholder="/var/www/project-x">
              </div>

              <!-- Firma seçimi: company_id için firma adlarıyla select + yeni firma (+) -->
              <div class="col-12">
                <label class="form-label">Firma (company_id)</label>
                <div style="position: relative;">
                  <!-- Hidden input for selected company_id -->
                  <input type="hidden" name="company_id" id="company_id_input" value="<?= Helpers::e((string)($c['company_id'] ?? '')) ?>">

                  <!-- Display input for company name -->
                  <div class="input-group">
                    <input
                      type="text"
                      id="company_filter"
                      class="form-control"
                      placeholder="Firma adı ile ara..."
                      autocomplete="off">
                    <a href="/firms/create" class="btn btn-outline-secondary" title="Yeni firma ekle">
                      <i class="bi bi-plus-lg"></i>
                    </a>
                  </div>

                  <!-- Dropdown container -->
                  <div id="company_dropdown" class="dropdown-menu" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; min-width: 100%;">
                    <div style="padding: 10px; background: #f8f9fb; border-bottom: 1px solid #dee2e6; position: sticky; top: 0; z-index: 1001;">
                      <input
                        type="text"
                        id="company_search"
                        class="form-control form-control-sm"
                        placeholder="Ara..."
                        autocomplete="off"
                        style="margin: 0;">
                    </div>
                    <div id="company_groups" style="max-height: 380px; overflow-y: auto; padding: 0;">
                      <!-- Groups will be populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>

              <style>
                .dropdown-menu {
                  display: none !important;
                }

                .dropdown-menu.show {
                  display: block !important;
                }
              </style>

              <script>
                (function() {
                  const filterInput = document.getElementById('company_filter');
                  const searchInput = document.getElementById('company_search');
                  const dropdown = document.getElementById('company_dropdown');
                  const groupsDiv = document.getElementById('company_groups');
                  const hiddenInput = document.getElementById('company_id_input');

                  let allCompanies = [];
                  let recentlySelected = [];
                  const RECENT_KEY = 'selectedCompanies';

                  // Load recently selected companies from localStorage
                  function loadRecentlySelected() {
                    const stored = localStorage.getItem(RECENT_KEY);
                    if (stored) {
                      try {
                        recentlySelected = JSON.parse(stored);
                      } catch (e) {
                        recentlySelected = [];
                      }
                    }
                  }

                  // Save recently selected company
                  function saveRecentlySelected(id, name) {
                    const existing = recentlySelected.find(c => c.id == id);
                    if (existing) {
                      existing.timestamp = Date.now();
                    } else {
                      recentlySelected.unshift({
                        id,
                        name,
                        timestamp: Date.now()
                      });
                      recentlySelected = recentlySelected.slice(0, 5); // Keep last 5
                    }
                    localStorage.setItem(RECENT_KEY, JSON.stringify(recentlySelected));
                  }

                  // Load companies from API
                  async function loadCompanies(searchTerm = '') {
                    try {
                      const url = `/projects/company-list?q=${encodeURIComponent(searchTerm)}`;
                      const response = await fetch(url);
                      const result = await response.json();
                      if (result.success) {
                        allCompanies = result.data;
                        renderDropdown(searchTerm);
                      }
                    } catch (error) {
                      console.error('Error loading companies:', error);
                    }
                  }

                  // Render dropdown with sections
                  function renderDropdown(searchTerm = '') {
                    const searchLower = searchTerm.toLowerCase();

                    // Separate into sections
                    const searchResults = allCompanies.filter(c =>
                      c.name.toLowerCase().includes(searchLower)
                    );

                    const recentIds = new Set(recentlySelected.map(c => c.id));
                    const recent = recentlySelected.filter(c => recentIds.has(c.id)).slice(0, 5);

                    let html = '';

                    // Arama Sonuçları (on top)
                    if (searchTerm) {
                      html += '<div style="padding: 5px 0;">';
                      html += '<div style="padding: 8px 10px; background: #f0f0f0; font-weight: bold; font-size: 0.85rem;">Arama Sonuçları</div>';
                      if (searchResults.length > 0) {
                        searchResults.forEach(c => {
                          html += `<div class="company-item" data-id="${c.id}" data-name="${c.name}" style="padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #eee;">
                            ${c.name}
                          </div>`;
                        });
                      } else {
                        html += '<div style="padding: 8px 10px; color: #999;">Sonuç bulunamadı</div>';
                      }
                      html += '</div>';
                    }

                    // Son Seçilenler
                    if (recent.length > 0 && !searchTerm) {
                      html += '<div style="padding: 5px 0;">';
                      html += '<div style="padding: 8px 10px; background: #f0f0f0; font-weight: bold; font-size: 0.85rem;">Son Seçilenler</div>';
                      recent.forEach(c => {
                        html += `<div class="company-item" data-id="${c.id}" data-name="${c.name}" style="padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #eee;">
                          ${c.name}
                        </div>`;
                      });
                      html += '</div>';
                    }

                    // All companies
                    if (!searchTerm && allCompanies.length > 0) {
                      html += '<div style="padding: 5px 0;">';
                      html += '<div style="padding: 8px 10px; background: #f0f0f0; font-weight: bold; font-size: 0.85rem;">Tüm Firmalar</div>';
                      allCompanies.forEach(c => {
                        const isRecent = recentIds.has(c.id);
                        if (!isRecent) {
                          html += `<div class="company-item" data-id="${c.id}" data-name="${c.name}" style="padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #eee;">
                            ${c.name}
                          </div>`;
                        }
                      });
                      html += '</div>';
                    }

                    groupsDiv.innerHTML = html;

                    // Attach click handlers to company items
                    document.querySelectorAll('.company-item').forEach(item => {
                      item.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const name = this.getAttribute('data-name');
                        hiddenInput.value = id;
                        filterInput.value = name;
                        dropdown.classList.remove('show');
                        saveRecentlySelected(id, name);
                      });
                    });
                  }

                  // Event listeners
                  filterInput.addEventListener('focus', function() {
                    loadCompanies();
                    dropdown.classList.add('show');
                  });

                  filterInput.addEventListener('input', function() {
                    const value = this.value;
                    searchInput.value = value;
                    loadCompanies(value);
                    if (value || recentlySelected.length > 0 || allCompanies.length > 0) {
                      dropdown.classList.add('show');
                    }
                  });

                  searchInput.addEventListener('input', function() {
                    const value = this.value;
                    filterInput.value = value;
                    loadCompanies(value);
                  });

                  // Close dropdown on outside click
                  document.addEventListener('click', function(e) {
                    if (!filterInput.contains(e.target) && !dropdown.contains(e.target)) {
                      dropdown.classList.remove('show');
                    }
                  });

                  // Initialize
                  loadRecentlySelected();

                  // Pre-fill display if company_id is set
                  const currentId = hiddenInput.value;
                  if (currentId && Array.isArray(<?php echo json_encode($companyMap); ?>)) {
                    const map = <?php echo json_encode($companyMap); ?>;
                    if (map[currentId]) {
                      filterInput.value = map[currentId];
                    }
                  }
                })();
              </script>

              <div class="col-12">
                <label class="form-label">Başlangıç Tarihi (start_date)</label>
                <input type="date" name="start_date"
                  value="<?= Helpers::e($c['start_date'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Bitiş Tarihi (end_date)</label>
                <input type="date" name="end_date"
                  value="<?= Helpers::e($c['end_date'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Bütçe (budget)</label>
                <input type="number" name="budget" step="0.01" min="0"
                  value="<?= Helpers::e((string)($c['budget'] ?? '')) ?>" class="form-control" placeholder="0.00">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Adres -->
      <div>
        <div class="card shadow-sm h-100 form-card">
          <div class="card-header"><strong>Adres</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <label class="form-label">Adres Satırı 1 (address_line1)</label>
                <input type="text" name="address_line1" maxlength="200"
                  value="<?= Helpers::e($c['address_line1'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Adres Satırı 2 (address_line2)</label>
                <input type="text" name="address_line2" maxlength="200"
                  value="<?= Helpers::e($c['address_line2'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Şehir (city)</label>
                <input type="text" name="city" maxlength="100"
                  value="<?= Helpers::e($c['city'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Eyalet/Bölge (state_region)</label>
                <input type="text" name="state_region" maxlength="100"
                  value="<?= Helpers::e($c['state_region'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Posta Kodu (postal_code)</label>
                <input type="text" name="postal_code" maxlength="20"
                  value="<?= Helpers::e($c['postal_code'] ?? '') ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Ülke Kodu (ISO-2)</label>
                <input type="text" name="country_code" maxlength="2" pattern="[A-Za-z]{2}"
                  value="<?= Helpers::e($c['country_code'] ?? '') ?>" class="form-control" placeholder="TR">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Operasyonel -->
      <div>
        <div class="card shadow-sm h-100 form-card">
          <div class="card-header"><strong>Operasyonel</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <label class="form-label">Durum (status)</label>
                <?php
                $status = $c['status'] ?? 'active';
                $statusOpts = ['active', 'planned', 'in_progress', 'on_hold', 'completed', 'cancelled'];
                ?>
                <select name="status" class="form-select">
                  <?php foreach ($statusOpts as $s): ?>
                    <option value="<?= Helpers::e($s) ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= Helpers::e($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Para Birimi (currency_code)</label>
                <input type="text" name="currency_code" maxlength="3" pattern="[A-Za-z]{3}"
                  value="<?= Helpers::e($c['currency_code'] ?? '') ?>" class="form-control" placeholder="TRY">
              </div>
              <div class="col-12">
                <label class="form-label">Zaman Dilimi (timezone)</label>
                <input type="text" name="timezone" maxlength="50"
                  value="<?= Helpers::e($c['timezone'] ?? '') ?>" class="form-control" placeholder="Europe/Istanbul">
              </div>

              <div class="col-12">
                <div class="d-flex flex-column gap-2 pt-1">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                      <?= $checked($c['is_active'] ?? true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Aktif (is_active)</label>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Proje Görseli (image)</label>
                <?php if (!empty($c['image_url'])): ?>
                  <div class="mb-2">
                    <img src="<?= Helpers::e($c['image_url']) ?>" alt="Project Image" style="max-height: 150px; max-width: 300px;" class="img-thumbnail">
                    <p class="text-muted small mt-1">Mevcut görsel</p>
                  </div>
                <?php endif; ?>
                <input type="file" name="image_file" accept="image/*" class="form-control">
                <input type="hidden" name="existing_image_url" value="<?= Helpers::e($c['image_url'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Notlar (notes)</label>
                <textarea name="notes" rows="3" class="form-control"><?= Helpers::e($c['notes'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /.form-grid -->
  </div><!-- /.form-grid-wrapper -->

  <div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-primary">
      <i class="bi <?= !empty($showIdHidden) ? 'bi-save' : 'bi-check2' ?> me-1"></i><?= Helpers::e($submitLabel) ?>
    </button>
    <a href="<?= Helpers::e($backUrl ?? '/projects') ?>" class="btn btn-outline-secondary">İptal</a>
  </div>
</form>