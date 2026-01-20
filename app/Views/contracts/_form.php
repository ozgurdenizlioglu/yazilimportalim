// FILE: views/contracts/_form.php
<?php

use App\Core\Helpers;

// Beklenen: $c, $action, $submitLabel, $title, $showIdHidden, $backUrl

$checked = function ($v) {
  if (is_bool($v)) return $v;
  if ($v === null) return false;
  $v = strtolower((string)$v);
  return in_array($v, ['1', 'true', 'on', 'yes', 'evet'], true);
};

?>
<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>
  <div class="d-flex gap-2">
    <a href="<?= Helpers::e($backUrl ?? '/contracts') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
  </div>
</div>

<form method="post" action="<?= Helpers::e($action) ?>" novalidate>
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

    /* Left column cards auto-sizing */
    @media (min-width: 992px) {
      .form-grid>div:first-child {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }

      .form-grid>div:first-child .form-card {
        overflow-y: auto;
      }
    }

    /* Right column cards auto-sizing */
    @media (min-width: 992px) {
      .form-grid>div:last-child {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }

      .form-grid>div:last-child .form-card {
        overflow-y: auto;
      }
    }

    .list-group-item-action {
      cursor: pointer;
    }

    .text-mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    .suggest-active {
      background-color: #e9f3ff;
    }

    .dropdown-menu.show {
      display: block;
    }

    .dropdown-item .muted {
      color: #6c757d;
      font-size: .85em;
    }

    .pill {
      border: 1px solid #dee2e6;
      border-radius: 999px;
      padding: .15rem .5rem;
      font-size: .75rem;
      color: #495057;
      background: #f8f9fa;
    }

    /* Dropdown menu improvements */
    .dropdown {
      position: relative;
    }

    .dropdown-menu {
      min-width: 100%;
      z-index: 1050;
      display: none !important;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .dropdown-menu.show {
      display: block !important;
    }

    .dropdown-menu>div:first-child {
      position: sticky;
      top: 0;
      z-index: 1001;
      background: white;
      border-bottom: 1px solid #dee2e6;
      padding: 0.5rem !important;
      margin: 0 !important;
    }

    .dropdown-menu #project_groups,
    .dropdown-menu #contractor_groups {
      max-height: 340px;
      overflow-y: auto;
      padding-top: 0.25rem;
    }

    .dropdown-menu .list-group {
      margin-bottom: 0;
    }

    .dropdown-menu .form-control-sm {
      font-size: 0.875rem;
      padding: 0.375rem 0.5rem;
      margin: 0;
    }

    .dropdown-menu .btn-sm {
      padding: 0.375rem 0.5rem;
      font-size: 0.875rem;
      white-space: nowrap;
    }
  </style>

  <div class="form-grid-wrapper">
    <div class="form-grid">
      <!-- SOL SÜTUN -->
      <div>
        <div class="card shadow-sm h-100 form-card mb-3">
          <div class="card-header"><strong>Temel Bilgiler</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <label class="form-label">Sözleşme Başlığı (Otomatik)</label>
                <div class="alert alert-info mb-3" role="alert">
                  <code style="font-size: 1.05rem; font-weight: 600; color: #0c63e4;" id="contractTitleDisplay">
                    SZL_XXXXXX_XXXXXXXX_XXXXXXXX_YYYYMMDD
                  </code>
                </div>
              </div>

              <!-- Proje -->
              <div class="col-12">
                <label class="form-label d-flex align-items-center justify-content-between">
                  <span>Proje (project_id) *</span>
                  <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddProject">
                    <i class="bi bi-plus"></i> Yeni Proje Ekle
                  </button>
                </label>
                <input type="hidden" name="project_id" id="project_id" value="<?= Helpers::e((string)($c['project_id'] ?? '')) ?>">
                <div class="dropdown">
                  <input
                    type="search"
                    name="project_name"
                    id="project_name"
                    class="form-control"
                    autocomplete="off"
                    value="<?= Helpers::e($c['project_name'] ?? '') ?>"
                    placeholder="Proje adı yazın veya aşağıdan seçin (en az 2 harf)"
                    aria-expanded="false">
                  <div id="project_dropdown" class="dropdown-menu w-100" style="max-height:450px; position:absolute; top:100%; left:0; right:0;">
                    <div class="d-flex gap-2 align-items-center" style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">
                      <input type="search" class="form-control form-control-sm flex-grow-1" id="project_filter_inmenu" placeholder="Filtrele">
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="project_clear_filter">Temizle</button>
                    </div>
                    <div id="project_groups" style="max-height:380px; overflow-y:auto;">
                      <div class="px-2 py-1 text-muted small">Arama Sonuçları</div>
                      <div id="project_search" class="list-group list-group-flush"></div>
                      <div class="px-2 py-1 text-muted small border-top">Son Seçilenler</div>
                      <div id="project_recent" class="list-group list-group-flush"></div>
                      <div class="px-2 py-1 text-muted small border-top">En Çok Kullanılanlar</div>
                      <div id="project_top" class="list-group list-group-flush"></div>
                    </div>
                    <div id="project_empty" class="p-3 text-center text-muted" style="display:none;">Sonuç yok</div>
                  </div>
                </div>
                <small class="text-muted d-block mt-1">Ok tuşlarıyla gezip Enter ile seçebilirsiniz.</small>
              </div>

              <div class="col-12">
                <label class="form-label">İşveren Firma (Proje’den otomatik)</label>
                <input type="hidden" name="contractor_company_id" id="contractor_company_id" value="<?= Helpers::e((string)($c['contractor_company_id'] ?? '')) ?>">
                <input type="text" id="employer_company_name" class="form-control" value="<?= Helpers::e($c['contractor_name'] ?? '') ?>" placeholder="Proje seçildiğinde otomatik dolacak" disabled>
              </div>

              <!-- Yüklenici -->
              <div class="col-12">
                <label class="form-label d-flex align-items-center justify-content-between">
                  <span>Yüklenici Firma (isim) *</span>
                  <div class="d-flex align-items-center gap-2">
                    <span id="contractor_selected_hint" class="pill" style="display:none;"></span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddContractor">
                      <i class="bi bi-plus"></i> Yeni Firma Ekle
                    </button>
                  </div>
                </label>
                <input type="hidden" name="subcontractor_company_id" id="subcontractor_company_id" value="<?= Helpers::e((string)($c['subcontractor_company_id'] ?? '')) ?>">
                <input
                  type="search"
                  name="contractor_company_name"
                  id="contractor_company_name"
                  class="form-control"
                  autocomplete="off"
                  value="<?= Helpers::e($c['subcontractor_name'] ?? '') ?>"
                  placeholder="Firma ismi yazın veya aşağıdan seçin (en az 2 harf)"
                  aria-expanded="false">
                <div id="contractor_dropdown" class="dropdown-menu w-100" style="max-height:450px; position:absolute; top:100%; left:0; right:0;">
                  <div class="d-flex gap-2 align-items-center" style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">
                    <input type="search" class="form-control form-control-sm flex-grow-1" id="contractor_filter_inmenu" placeholder="Filtrele">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="contractor_clear_filter">Temizle</button>
                  </div>
                  <div id="contractor_groups" style="max-height:380px; overflow-y:auto;">
                    <div class="px-2 py-1 text-muted small">Arama Sonuçları</div>
                    <div id="contractor_search" class="list-group list-group-flush"></div>
                    <div class="px-2 py-1 text-muted small border-top">Son Seçilenler</div>
                    <div id="contractor_recent" class="list-group list-group-flush"></div>
                    <div class="px-2 py-1 text-muted small border-top">En Çok Kullanılanlar</div>
                    <div id="contractor_top" class="list-group list-group-flush"></div>
                  </div>
                  <div id="contractor_empty" class="p-3 text-center text-muted" style="display:none;">Sonuç yok</div>
                </div>
              </div>
              <small class="text-muted d-block mt-1">Ok tuşlarıyla gezip Enter ile seçebilirsiniz.</small>
            </div>

          </div><!-- /.row -->
        </div><!-- /.card-body -->
      </div><!-- /.card Temel Bilgiler -->

      <!-- DİĞER BİLGİLER CARD -->
      <div class="card shadow-sm h-100 form-card mb-3">
        <div class="card-header"><strong>Diğer Bilgiler</strong></div>
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <label class="form-label">Sözleşme Tarihi (contract_date) *</label>
              <input type="date" name="contract_date" id="contract_date" class="form-control" required
                value="<?= Helpers::e($c['contract_date'] ?? date('Y-m-d')) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Bitiş Tarihi (end_date)</label>
              <input type="date" name="end_date" class="form-control"
                value="<?= Helpers::e($c['end_date'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Konu (subject)</label>
              <input type="text" name="subject" maxlength="255" class="form-control"
                value="<?= Helpers::e($c['subject'] ?? '') ?>" placeholder="Sözleşmenin konusu">
            </div>

            <div class="col-12">
              <label class="form-label">Disiplin (discipline_id)</label>
              <div class="input-group">
                <select name="discipline_id" id="discipline_id" class="form-select">
                  <option value="">Seçiniz...</option>
                </select>
                <button class="btn btn-outline-success" type="button" data-bs-toggle="modal"
                  data-bs-target="#createDisciplineModal" title="Yeni disiplin oluştur">
                  <i class="bi bi-plus-circle"></i>
                </button>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Alt Disiplin (branch_id)</label>
              <div class="input-group">
                <select name="branch_id" id="branch_id" class="form-select" disabled>
                  <option value="">Önce disiplin seçiniz...</option>
                </select>
                <button class="btn btn-outline-success" type="button" data-bs-toggle="modal"
                  data-bs-target="#createBranchModal" title="Yeni alt disiplin oluştur"
                  id="addBranchBtn" disabled>
                  <i class="bi bi-plus-circle"></i>
                </button>
              </div>
            </div>

          </div><!-- /.row -->
        </div><!-- /.card-body -->
      </div><!-- /.card Diğer Bilgiler -->

      <!-- PROJE BEDELİ CARD -->
      <div class="card shadow-sm h-100 form-card mb-3">
        <div class="card-header"><strong>Proje Bedeli</strong></div>
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <label class="form-label">Tutar (otomatik)</label>
              <input type="text" id="amount" class="form-control text-end" placeholder="0,00" disabled>
              <input type="hidden" name="amount" id="amount_hidden" value="<?= Helpers::e((string)($c['amount'] ?? '')) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Para Birimi (otomatik)</label>
              <input type="text" id="currency_name" class="form-control" placeholder="TRY/EUR/USD" disabled>
              <input type="hidden" name="currency_id" id="currency_id_hidden" value="<?= Helpers::e((string)($c['currency_id'] ?? '')) ?>">
              <input type="hidden" name="currency_name" id="currency_name_hidden" value="">
            </div>

            <div class="col-12">
              <label class="form-label">Tutar Yazıyla (otomatik)</label>
              <textarea id="amount_in_words" rows="2" class="form-control" placeholder="Yazıyla..." disabled><?= Helpers::e($c['amount_in_words'] ?? '') ?></textarea>
              <input type="hidden" name="amount_in_words" id="amount_in_words_hidden" value="<?= Helpers::e($c['amount_in_words'] ?? '') ?>">
            </div>

            <div class="col-12">
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                  <?= $checked($c['is_active'] ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktif (is_active)</label>
              </div>
            </div>

          </div><!-- /.row -->
        </div><!-- /.card-body -->
      </div><!-- /.card Proje Bedeli -->
    </div>

    <!-- SAĞ SÜTUN -->
    <div>
      <!-- Ödemeler Grid -->
      <div class="card shadow-sm h-100 form-card mb-3">
        <div class="card-header"><strong>Ödemeler</strong></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle" id="paymentsTable">
              <thead>
                <tr>
                  <th>Tür</th>
                  <th>Vade</th>
                  <th class="text-end">Tutar</th>
                  <th>Para Birimi</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddPayment"><i class="bi bi-plus-lg"></i> Satır Ekle</button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddCheque">Çek</button>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnAddCash">Nakit</button>
            <button type="button" class="btn btn-outline-warning btn-sm" id="btnAddTransfer">Havale/EFT</button>
            <button type="button" class="btn btn-outline-warning btn-sm" id="btnAddBarter">BARTER</button>
          </div>

          <input type="hidden" name="payments_payload" id="payments_payload" value="">
          <div class="small text-muted mt-2">
            Not: Tür “Çek” dışındakilerde Vade, sözleşme tarihine sabitlenir.
          </div>

          <div class="mt-3 pt-3 border-top">
            <label class="form-label">Ödemeler ile İlgili Notlar</label>
            <textarea name="payment_notes" id="payment_notes" rows="3" class="form-control" placeholder="Ödeme planı ile ilgili ek notlar (opsiyonel)"><?= Helpers::e($c['payment_notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="card shadow-sm h-100 form-card mb-3">
        <div class="card-header"><strong>Ek Bilgiler</strong></div>
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <label class="form-label">Notlar</label>
              <textarea class="form-control" rows="6" placeholder="İç notlar (opsiyonel)"></textarea>
              <small class="text-muted">Şimdilik DB’de alan yok; isterseniz ileride eklenecek.</small>
            </div>
            <div class="col-12">
              <label class="form-label">Word/PDF</label>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" id="btnGenerateWord" <?= empty($c['id']) ? 'disabled' : '' ?>>Word Oluştur</button>
                <button type="button" class="btn btn-outline-secondary" id="btnGeneratePdf" <?= empty($c['id']) ? 'disabled' : '' ?>>PDF Görüntüle</button>
                <button type="button" class="btn btn-outline-success" id="btnUploadPdf" <?= empty($c['id']) ? 'disabled' : '' ?>>PDF Yükle</button>
                <input type="file" id="pdfFileInput" accept=".pdf" style="display:none;">
              </div>
              <small class="text-muted d-block mt-1">Sözleşmeyi önce kaydedin, sonra belge oluşturabilirsiniz.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /.form-grid -->
  </div><!-- /.form-grid-wrapper -->

  <?php if (!empty($c['id'])): ?>
    <!-- Document Management Section -->
    <div class="card shadow-sm mt-4">
      <div class="card-header"><strong>📁 Dökümanlar (UUID: <?= Helpers::e(substr($c['uuid'] ?? '', 0, 8)) ?>)</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <label class="form-label">Belge Yükle</label>
            <div class="input-group">
              <input type="file" id="documentFileInput" class="form-control" accept=".pdf,.docx,.xlsx,.png,.jpg,.jpeg,.gif">
              <button type="button" class="btn btn-primary" id="btnUploadDocument">
                <i class="bi bi-cloud-upload me-1"></i>Yükle
              </button>
              <button type="button" class="btn btn-outline-secondary" id="btnOpenFolder" title="Klasörü Aç">
                <i class="bi bi-folder-open me-1"></i>Klasörü Aç
              </button>
              <button type="button" class="btn btn-outline-info" id="btnRefreshDocuments" title="Dökümanları Yenile">
                <i class="bi bi-arrow-clockwise me-1"></i>Yenile
              </button>
            </div>
            <small class="text-muted d-block mt-2">İzin verilen dosya türleri: PDF, DOCX, XLSX, PNG, JPG, GIF</small>
          </div>
          <div class="col-12 mt-3">
            <label class="form-label">Yüklenen Dökümanlar</label>
            <div id="documentsList" class="list-group">
              <div class="text-muted text-center py-3">Döküman yok</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-primary">
      <i class="bi <?= !empty($showIdHidden) ? 'bi-save' : 'bi-check2' ?> me-1"></i><?= Helpers::e($submitLabel) ?>
    </button>
    <a href="<?= Helpers::e($backUrl ?? '/contracts') ?>" class="btn btn-outline-secondary">İptal</a>
  </div>
</form>

<!-- Yeni Firma Ekle Modal -->
<div class="modal fade" id="newCompanyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Yeni Firma Ekle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Firma Adı</label>
        <input type="text" id="newCompanyName" class="form-control" placeholder="Firma adı">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="saveNewCompany">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Yeni Proje Ekle Modal -->
<div class="modal fade" id="newProjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Yeni Proje Ekle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Proje Adı</label>
          <input type="text" id="newProjectName" class="form-control" placeholder="Proje adı">
        </div>
        <div>
          <label class="form-label">İşveren Firma (opsiyonel)</label>
          <input type="text" id="newProjectEmployer" class="form-control" placeholder="Firma adı">
          <small class="text-muted">İşveren firma adı girerseniz proje ile ilişkilendirilecektir.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="saveNewProject">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    try {
      function esc(s) {
        return String(s ?? '').replaceAll('"', '&quot;');
      }

      function optionEl(value, label, selected = false) {
        const o = document.createElement('option');
        o.value = String(value ?? '');
        o.textContent = String(label ?? '');
        if (selected) o.selected = true;
        return o;
      }

      // ------- Proje alanı (değişmedi) -------
      const projectIdHidden = document.getElementById('project_id');
      const projectSelect = document.getElementById('project_select');
      const contractorIdInput = document.getElementById('contractor_company_id_display');
      const contractorNameInput = document.getElementById('contractor_company_name_display');

      async function fetchProjectList() {
        const r = await fetch('/contracts/project-list', {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!r.ok) throw new Error('Proje listesi alınamadı');
        return await r.json();
      }
      async function fetchProjectInfoById(id) {
        const r = await fetch(`/contracts/project-info?id=${encodeURIComponent(String(id))}`, {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!r.ok) throw new Error('Proje bulunamadı');
        return await r.json();
      }
      async function onProjectChanged(projectId) {
        projectIdHidden.value = projectId || '';
        if (!projectId) {
          empIdInput.value = '';
          empNameInput.value = '';
          await recalc();
          return;
        }
        try {
          const data = await fetchProjectInfoById(projectId);
          empIdInput.value = data.employer_company_id || '';
          empNameInput.value = data.employer_company_name || '';
        } catch (err) {
          empIdInput.value = '';
          empNameInput.value = '';
          console.error(err);
        }
        await recalc();
      }

      // ------- Disiplin & Alt Disiplin (id/text) -------
      const disciplineSelect = document.getElementById('discipline_id');
      const branchSelect = document.getElementById('branch_id');
      const currentDisciplineId = '<?= Helpers::e((string)($c["discipline_id"] ?? "")) ?>';
      const currentBranchId = '<?= Helpers::e((string)($c["branch_id"] ?? "")) ?>';

      async function fetchDisciplines() {
        const r = await fetch('/contracts/discipline-list', {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!r.ok) throw new Error('Disiplin listesi alınamadı');
        return await r.json(); // [{id, text}]
      }
      async function fetchBranches(disciplineId) {
        if (!disciplineId) return [];
        const r = await fetch('/contracts/discipline-branch-list?discipline_id=' + encodeURIComponent(String(disciplineId)), {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!r.ok) throw new Error('Alt disiplin listesi alınamadı');
        return await r.json(); // [{id, text}]
      }

      async function loadDisciplines() {
        try {
          const list = await fetchDisciplines();
          disciplineSelect.innerHTML = '';
          disciplineSelect.appendChild(optionEl('', 'Seçiniz...', false));
          (list || []).forEach(d => {
            const label = d.text || ('#' + d.id);
            const selected = currentDisciplineId && String(d.id) === String(currentDisciplineId);
            disciplineSelect.appendChild(optionEl(d.id, label, selected));
          });
          disciplineSelect.disabled = false;

          // Seçili disiplin varsa alt disiplinleri yükle
          if (disciplineSelect.value) {
            await loadBranchesFor(disciplineSelect.value, currentBranchId);
          }
        } catch (e) {
          console.error(e);
          disciplineSelect.innerHTML = '<option value="">Seçiniz...</option>';
          disciplineSelect.disabled = false;
        }
      }

      async function loadBranchesFor(disciplineId, selectedBranchId = null) {
        branchSelect.innerHTML = '';
        branchSelect.appendChild(optionEl('', 'Seçiniz...', false));
        branchSelect.disabled = true;
        try {
          const list = await fetchBranches(disciplineId);
          (list || []).forEach(b => {
            const label = b.text || ('#' + b.id);
            const selected = selectedBranchId && String(b.id) === String(selectedBranchId);
            branchSelect.appendChild(optionEl(b.id, label, selected));
          });
          branchSelect.disabled = false;
        } catch (e) {
          console.error(e);
          branchSelect.innerHTML = '<option value="">Seçiniz...</option>';
          branchSelect.disabled = false;
        }
      }

      disciplineSelect?.addEventListener('change', async (e) => {
        const val = e.target.value;
        await loadBranchesFor(val, null);
      });

      // ------- Yüklenici (mevcut gelişmiş seçim) -------
      const contrInput = document.getElementById('contractor_company_name');
      const contrIdHidden = document.getElementById('contractor_company_id');
      const dropdown = document.getElementById('contractor_dropdown');
      const inMenuFilter = document.getElementById('contractor_filter_inmenu');
      const clearFilterBtn = document.getElementById('contractor_clear_filter');
      const recentEl = document.getElementById('contractor_recent');
      const topEl = document.getElementById('contractor_top');
      const searchEl = document.getElementById('contractor_search');
      const emptyEl = document.getElementById('contractor_empty');
      const selectedHint = document.getElementById('contractor_selected_hint');

      let debounceTimer;
      let lastQuery = '';
      let lastResults = [];
      let activeIndex = -1;

      function showDropdown() {
        dropdown.classList.add('show');
        contrInput.setAttribute('aria-expanded', 'true');
      }

      function hideDropdown() {
        dropdown.classList.remove('show');
        contrInput.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
      }

      function bindListClicks(container) {
        container?.addEventListener('click', (e) => {
          const btn = e.target.closest('button.list-group-item');
          if (!btn) return;
          applySelection(btn.dataset.id, btn.dataset.name);
        });
      }

      function loadLocalRecent() {
        try {
          const arr = JSON.parse(localStorage.getItem('contractor_recent') || '[]');
          return Array.isArray(arr) ? arr.slice(0, 10) : [];
        } catch {
          return [];
        }
      }

      function saveLocalRecent(id, name) {
        try {
          const arr = loadLocalRecent().filter(x => String(x.id) !== String(id));
          arr.unshift({
            id,
            name
          });
          localStorage.setItem('contractor_recent', JSON.stringify(arr.slice(0, 10)));
        } catch {}
      }

      function updateSelectedHint() {
        const id = contrIdHidden?.value;
        const name = contrInput?.value?.trim();
        if (id && name) {
          selectedHint.textContent = `Seçili: ${name} (#${id})`;
          selectedHint.style.display = 'inline-flex';
        } else {
          selectedHint.style.display = 'none';
        }
      }

      async function fetchCompanies(q) {
        const res = await fetch(`/contracts/company-search?q=${encodeURIComponent(q)}`, {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!res.ok) return [];
        const data = await res.json();
        return Array.isArray(data) ? data : [];
      }
      async function fetchTopCompanies() {
        try {
          const res = await fetch('/contracts/company-top', {
            headers: {
              'Accept': 'application/json'
            }
          });
          if (!res.ok) return [];
          const data = await res.json();
          return Array.isArray(data) ? data : [];
        } catch {
          return [];
        }
      }

      function renderRecent() {
        const rec = loadLocalRecent();
        const html = rec.map(it => `<button type="button" class="list-group-item list-group-item-action" data-id="${esc(it.id)}" data-name="${esc(it.name)}">${esc(it.name)}</button>`).join('');
        document.getElementById('contractor_recent').innerHTML = html || '<div class="p-2 text-muted">Henüz yok</div>';
      }
      async function renderTop() {
        const list = await fetchTopCompanies();
        const html = list.map(it => `<button type="button" class="list-group-item list-group-item-action" data-id="${esc(it.id)}" data-name="${esc(it.name)}"><span>${esc(it.name)}</span><span class="muted">${it.usage_count ?? ''}</span></button>`).join('');
        document.getElementById('contractor_top').innerHTML = html || '<div class="p-2 text-muted">Kayıt yok</div>';
      }

      function renderSearch(items) {
        const searchEl = document.getElementById('contractor_search');
        const emptyEl = document.getElementById('contractor_empty');
        if (!Array.isArray(items) || items.length === 0) {
          searchEl.innerHTML = '';
          emptyEl.style.display = 'block';
        } else {
          emptyEl.style.display = 'none';
          searchEl.innerHTML = items.slice(0, 100).map(it => `<button type="button" class="list-group-item list-group-item-action" data-id="${esc(it.id)}" data-name="${esc(it.name)}">${esc(it.name)}</button>`).join('');
        }
      }

      function applySelection(id, name) {
        if (contrInput) contrInput.value = name || '';
        if (contrIdHidden) contrIdHidden.value = id || '';
        saveLocalRecent(id, name);
        updateSelectedHint();
        hideDropdown();
      }

      function moveActive(delta) {
        const buttons = Array.from(document.querySelectorAll('#contractor_search button.list-group-item'));
        if (buttons.length === 0) return;
        activeIndex = (activeIndex + delta + buttons.length) % buttons.length;
        buttons.forEach((b, i) => b.classList.toggle('suggest-active', i === activeIndex));
      }

      function selectActive() {
        const btns = document.querySelectorAll('#contractor_search button.list-group-item');
        if (activeIndex >= 0 && activeIndex < btns.length) {
          const btn = btns[activeIndex];
          applySelection(btn.dataset.id, btn.dataset.name);
        }
      }

      bindListClicks(document.getElementById('contractor_recent'));
      bindListClicks(document.getElementById('contractor_top'));
      bindListClicks(document.getElementById('contractor_search'));

      const contrInputEl = document.getElementById('contractor_company_name');
      contrInputEl?.addEventListener('focus', async () => {
        renderRecent();
        await renderTop();
        document.getElementById('contractor_empty').style.display = 'none';
        document.getElementById('contractor_search').innerHTML = '';
        showDropdown();
        activeIndex = -1;
      });
      contrInputEl?.addEventListener('click', () => {
        showDropdown();
      });
      contrInputEl?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        if (contrIdHidden) contrIdHidden.value = '';
        updateSelectedHint();
        const q = (contrInputEl.value || '').trim();
        if (q.length < 2) {
          document.getElementById('contractor_empty').style.display = 'none';
          document.getElementById('contractor_search').innerHTML = '';
          activeIndex = -1;
          showDropdown();
          return;
        }
        debounceTimer = setTimeout(async () => {
          try {
            if (q === lastQuery && lastResults.length > 0) {
              renderSearch(lastResults);
              return;
            }
            const items = await fetchCompanies(q);
            lastQuery = q;
            lastResults = items;
            renderSearch(items);
            activeIndex = -1;
            showDropdown();
          } catch (e) {
            console.error(e);
          }
        }, 160);
      });
      contrInputEl?.addEventListener('keydown', (e) => {
        if (!dropdown.classList.contains('show')) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          moveActive(1);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          moveActive(-1);
        } else if (e.key === 'Enter') {
          if (activeIndex >= 0) {
            e.preventDefault();
            selectActive();
          }
        } else if (e.key === 'Escape') {
          hideDropdown();
        }
      });
      document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && e.target !== contrInputEl) {
          hideDropdown();
        }
      });
      document.getElementById('contractor_filter_inmenu')?.addEventListener('input', (ev) => {
        const q = (ev.target.value || '').toLocaleLowerCase('tr-TR');
        ['contractor_recent', 'contractor_top', 'contractor_search'].forEach(id => {
          const el = document.getElementById(id);
          Array.from(el.querySelectorAll('button.list-group-item')).forEach(btn => {
            const name = (btn.dataset.name || '').toLocaleLowerCase('tr-TR');
            btn.style.display = name.includes(q) ? '' : 'none';
          });
        });
      });
      document.getElementById('contractor_clear_filter')?.addEventListener('click', () => {
        const f = document.getElementById('contractor_filter_inmenu');
        if (f) {
          f.value = '';
          f.dispatchEvent(new Event('input'));
        }
      });

      // ------- Proje dropdown (advanced with recent/top/search) -------
      const projInput = document.getElementById('project_name');
      const projIdHidden = document.getElementById('project_id');
      const projDropdown = document.getElementById('project_dropdown');
      const projInMenuFilter = document.getElementById('project_filter_inmenu');
      const projClearFilterBtn = document.getElementById('project_clear_filter');
      const projRecentEl = document.getElementById('project_recent');
      const projTopEl = document.getElementById('project_top');
      const projSearchEl = document.getElementById('project_search');
      const projEmptyEl = document.getElementById('project_empty');

      // Verify elements exist
      if (!projInput || !projDropdown || !projRecentEl) {
        console.error('Project dropdown elements not found:', {
          projInput,
          projDropdown,
          projRecentEl
        });
      } else {
        console.log('Project dropdown elements found successfully');
      }

      let projDebounceTimer;
      let projLastQuery = '';
      let projLastResults = '';
      let projActiveIndex = -1;

      function projShowDropdown() {
        projDropdown.classList.add('show');
        projInput.setAttribute('aria-expanded', 'true');
      }

      function projHideDropdown() {
        projDropdown.classList.remove('show');
        projInput.setAttribute('aria-expanded', 'false');
        projActiveIndex = -1;
      }

      function projLoadLocalRecent() {
        try {
          const arr = JSON.parse(localStorage.getItem('project_recent') || '[]');
          return Array.isArray(arr) ? arr.slice(0, 10) : [];
        } catch {
          return [];
        }
      }

      function projSaveLocalRecent(id, name) {
        try {
          const arr = projLoadLocalRecent().filter(x => String(x.id) !== String(id));
          arr.unshift({
            id,
            name
          });
          localStorage.setItem('project_recent', JSON.stringify(arr.slice(0, 10)));
        } catch {}
      }

      async function projFetchCompanies(q) {
        try {
          const res = await fetch(`/contracts/project-list`, {
            headers: {
              'Accept': 'application/json'
            }
          });
          if (!res.ok) return [];
          const data = await res.json();
          if (!Array.isArray(data)) return [];
          return data.filter(it => it.name.toLowerCase().includes(q.toLowerCase()));
        } catch {
          return [];
        }
      }

      async function projFetchTopCompanies() {
        try {
          const res = await fetch('/contracts/project-list', {
            headers: {
              'Accept': 'application/json'
            }
          });
          if (!res.ok) return [];
          const data = await res.json();
          return Array.isArray(data) ? data.slice(0, 10) : [];
        } catch {
          return [];
        }
      }

      function projRenderRecent() {
        const rec = projLoadLocalRecent();
        console.log('Rendering recent projects:', rec);
        const html = rec.map(it => `<button type="button" class="list-group-item list-group-item-action" data-proj-id="${esc(it.id)}" data-proj-name="${esc(it.name)}">${esc(it.name)}</button>`).join('');
        projRecentEl.innerHTML = html || '<div class="p-2 text-muted">Henüz yok</div>';
        projBindListClicks(projRecentEl);
      }

      async function projRenderTop() {
        try {
          const list = await projFetchTopCompanies();
          console.log('Top projects fetched:', list);
          const html = list.map(it => `<button type="button" class="list-group-item list-group-item-action" data-proj-id="${esc(it.id)}" data-proj-name="${esc(it.name)}">${esc(it.name)}</button>`).join('');
          projTopEl.innerHTML = html || '<div class="p-2 text-muted">Kayıt yok</div>';
          projBindListClicks(projTopEl);
        } catch (e) {
          console.error('Error in projRenderTop:', e);
        }
      }

      function projRenderSearch(items) {
        if (!Array.isArray(items) || items.length === 0) {
          projSearchEl.innerHTML = '';
          projEmptyEl.style.display = 'block';
        } else {
          projEmptyEl.style.display = 'none';
          projSearchEl.innerHTML = items.slice(0, 100).map(it => `<button type="button" class="list-group-item list-group-item-action" data-proj-id="${esc(it.id)}" data-proj-name="${esc(it.name)}">${esc(it.name)}</button>`).join('');
          projBindListClicks(projSearchEl);
        }
      }

      function projBindListClicks(container) {
        container?.addEventListener('click', (e) => {
          const btn = e.target.closest('button.list-group-item');
          if (!btn) return;
          projApplySelection(btn.dataset.projId, btn.dataset.projName);
        });
      }

      async function projApplySelection(id, name) {
        if (!id || !name) return;
        projIdHidden.value = id;
        projInput.value = name;
        projSaveLocalRecent(id, name);
        projHideDropdown();
        await onProjectChanged(id);
        updateContractTitle();
      }

      function projMoveActive(dir) {
        const btns = Array.from(
          projDropdown.querySelectorAll('button.list-group-item:not([style*="display: none"])')
        );
        if (btns.length === 0) return;
        projActiveIndex = Math.max(-1, Math.min(btns.length - 1, projActiveIndex + dir));
        btns.forEach((b, i) => {
          b.classList.toggle('suggest-active', i === projActiveIndex);
        });
        if (projActiveIndex >= 0 && btns[projActiveIndex]) {
          btns[projActiveIndex].scrollIntoView({
            block: 'nearest'
          });
        }
      }

      function projSelectActive() {
        const btns = Array.from(
          projDropdown.querySelectorAll('button.list-group-item:not([style*="display: none"])')
        );
        if (projActiveIndex >= 0 && btns[projActiveIndex]) {
          btns[projActiveIndex].click();
        }
      }

      projRenderRecent();
      projRenderTop().catch(e => console.error('Error rendering top projects:', e));

      projInput?.addEventListener('focus', async () => {
        console.log('Project input focused, showing dropdown');
        projRenderRecent();
        await projRenderTop();
        projEmptyEl.style.display = 'none';
        projSearchEl.innerHTML = '';
        projShowDropdown();
        projActiveIndex = -1;
      });

      projInput?.addEventListener('click', () => {
        console.log('Project input clicked');
        projShowDropdown();
      });

      projInput?.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        projLastQuery = q;
        clearTimeout(projDebounceTimer);
        if (!q) {
          projRenderSearch([]);
          projShowDropdown();
        } else {
          projDebounceTimer = setTimeout(async () => {
            try {
              const items = await projFetchCompanies(q);
              projLastResults = items;
              projRenderSearch(items);
              projActiveIndex = -1;
              projShowDropdown();
            } catch (e) {
              console.error(e);
            }
          }, 160);
        }
      });

      projInput?.addEventListener('keydown', (e) => {
        if (!projDropdown.classList.contains('show')) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          projMoveActive(1);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          projMoveActive(-1);
        } else if (e.key === 'Enter') {
          if (projActiveIndex >= 0) {
            e.preventDefault();
            projSelectActive();
          }
        } else if (e.key === 'Escape') {
          projHideDropdown();
        }
      });

      document.addEventListener('click', (e) => {
        if (!projDropdown.contains(e.target) && e.target !== projInput) {
          projHideDropdown();
        }
      });

      projInMenuFilter?.addEventListener('input', (ev) => {
        const q = (ev.target.value || '').toLowerCase();
        ['project_recent', 'project_top', 'project_search'].forEach(id => {
          const el = document.getElementById(id);
          Array.from(el.querySelectorAll('button.list-group-item')).forEach(btn => {
            const name = (btn.dataset.projName || '').toLowerCase();
            btn.style.display = name.includes(q) ? '' : 'none';
          });
        });
      });

      projClearFilterBtn?.addEventListener('click', () => {
        const f = projInMenuFilter;
        if (f) {
          f.value = '';
          f.dispatchEvent(new Event('input'));
        }
      });

      projBindListClicks(projRecentEl);
      projBindListClicks(projTopEl);
      projBindListClicks(projSearchEl);

      // Yeni firma modal
      const btnAddContr = document.getElementById('btnAddContractor');
      const companyModalEl = document.getElementById('newCompanyModal');
      let companyModal = null;
      try {
        if (window.bootstrap && companyModalEl) {
          companyModal = bootstrap.Modal?.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(companyModalEl) : new bootstrap.Modal(companyModalEl);
        }
      } catch {}
      const saveNewCompany = document.getElementById('saveNewCompany');
      const newCompanyName = document.getElementById('newCompanyName');
      btnAddContr?.addEventListener('click', () => {
        if (newCompanyName && contrInputEl) newCompanyName.value = contrInputEl.value.trim();
        companyModal?.show?.();
      });
      saveNewCompany?.addEventListener('click', async () => {
        const name = (newCompanyName?.value || '').trim();
        if (!name) {
          alert('Firma adı zorunlu');
          return;
        }
        try {
          const fd = new FormData();
          fd.append('name', name);
          const res = await fetch('/contracts/company-create', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          });
          const data = res.ok ? await res.json() : null;
          if (!res.ok || !data?.id) {
            alert('Firma kaydedilemedi');
            return;
          }
          if (contrInputEl) contrInputEl.value = data.name || name;
          if (contrIdHidden) contrIdHidden.value = String(data.id);
          saveLocalRecent(data.id, data.name || name);
          updateSelectedHint();
          companyModal?.hide?.();
        } catch (e) {
          console.error(e);
          alert(e?.message || e);
        }
      });

      // ------- Ödemeler -------
      const CUR = {
        TRY: 949,
        EUR: 978,
        USD: 840
      };
      const CUR_NAME = {
        949: 'TRY',
        978: 'EUR',
        840: 'USD'
      };
      async function getRates(dateStr) {
        try {
          const res = await fetch('/api/rates?date=' + encodeURIComponent(dateStr || ''), {
            headers: {
              'Accept': 'application/json'
            }
          });
          if (res.ok) {
            const data = await res.json();
            if (data && data.rates) {
              const eur = Number(data.rates.EUR ?? data.rates['978']);
              const usd = Number(data.rates.USD ?? data.rates['840']);
              if (eur > 0 && usd > 0) return {
                EUR: eur,
                USD: usd
              };
            }
          }
        } catch {}
        return {
          EUR: 35.00,
          USD: 32.00
        };
      }

      function numberToTextTR(n, currency = 'TRY') {
        const birler = ['', 'bir', 'iki', 'üç', 'dört', 'beş', 'altı', 'yedi', 'sekiz', 'dokuz'];
        const onlar = ['', 'on', 'yirmi', 'otuz', 'kırk', 'elli', 'altmış', 'yetmiş', 'seksen', 'doksan'];
        const binlikler = ['', 'bin', 'milyon', 'milyar', 'trilyon'];

        function three(x) {
          x = x % 1000;
          const y = Math.floor(x / 100);
          const z = x % 100;
          const o = Math.floor(z / 10);
          const b = z % 10;
          let s = '';
          if (y > 0) s += (y === 1 ? 'yüz' : birler[y] + ' yüz');
          if (o > 0) s += (s ? ' ' : '') + onlar[o];
          if (b > 0) s += (s ? ' ' : '') + birler[b];
          return s;
        }
        n = Math.floor(Number(n) || 0);
        if (n === 0) return 'sıfır ' + currency;
        let i = 0,
          words = [];
        while (n > 0 && i < binlikler.length) {
          const grp = n % 1000;
          if (grp > 0) {
            let part = three(grp);
            if (i === 1 && grp === 1) part = 'bin';
            else if (i > 0) part += ' ' + binlikler[i];
            words.unshift(part);
          }
          n = Math.floor(n / 1000);
          i++;
        }
        return words.join(' ') + ' ' + currency;
      }

      function formatTRYStyle(n) {
        if (n == null || n === '') return '';
        const v = Number(n);
        if (!isFinite(v)) return '';
        return v.toLocaleString('tr-TR', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      }

      function parseMoneyInput(str) {
        if (str == null) return 0;
        const s = String(str).trim().replace(/\./g, '').replace(',', '.');
        const n = parseFloat(s);
        return isFinite(n) ? n : 0;
      }

      const tb = document.querySelector('#paymentsTable tbody');
      const addBtn = document.getElementById('btnAddPayment');
      const addChequeBtn = document.getElementById('btnAddCheque');
      const addCashBtn = document.getElementById('btnAddCash');
      const addTransferBtn = document.getElementById('btnAddTransfer');
      const payload = document.getElementById('payments_payload');

      const contractDateInput = document.getElementById('contract_date');
      const amountInput = document.getElementById('amount');
      const currencyNameInput = document.getElementById('currency_name');
      const amountInWords = document.getElementById('amount_in_words');
      const amountHidden = document.getElementById('amount_hidden');
      const currencyIdHidden = document.getElementById('currency_id_hidden');
      const currencyNameHidden = document.getElementById('currency_name_hidden');
      const amountInWordsHidden = document.getElementById('amount_in_words_hidden');

      if (!tb) {
        console.error('paymentsTable tbody bulunamadı');
        return;
      }

      const PAYMENT_TYPES = [{
          value: 'cash',
          label: 'Nakit'
        },
        {
          value: 'cheque',
          label: 'Çek'
        },
        {
          value: 'transfer',
          label: 'Havale/EFT'
        },
        {
          value: 'BARTER',
          label: 'TAKAS'
        },
        {
          value: 'other',
          label: 'Diğer'
        }
      ];

      function attachMoneyFormatting(input) {
        input.addEventListener('focus', () => {
          const val = input.value;
          if (!val) return;
          const num = parseMoneyInput(val);
          if (num || num === 0) {
            input.value = String(num).replace('.', ',');
          }
        });
        input.addEventListener('blur', () => {
          const num = parseMoneyInput(input.value);
          input.value = (num || num === 0) ? formatTRYStyle(num) : '';
          recalc();
        });
        input.addEventListener('change', () => {
          recalc();
        });
      }

      function addRow(initial = {}) {
        const tr = document.createElement('tr');
        const t = initial.type || 'cash';
        const vade = initial.due_date || (contractDateInput?.value || '');
        const amt = initial.amount ?? '';
        const cur = initial.currency_id ?? 949;
        tr.innerHTML = `
        <td><select class="form-select form-select-sm" data-role="type">
          ${PAYMENT_TYPES.map(p=>`<option value="${p.value}" ${p.value===t?'selected':''}>${p.label}</option>`).join('')}
        </select></td>
        <td><input type="date" class="form-control form-control-sm" data-role="due_date" value="${esc(vade)}"></td>
        <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-end" data-role="amount" placeholder="0,00"></td>
        <td>
          <select class="form-select form-select-sm" data-role="currency_id">
            <option value="949" ${cur==949?'selected':''}>TRY</option>
            <option value="978" ${cur==978?'selected':''}>EUR</option>
            <option value="840" ${cur==840?'selected':''}>USD</option>
          </select>
        </td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" title="Sil">&times;</button></td>`;
        const typeSel = tr.querySelector('[data-role="type"]');
        const dueInp = tr.querySelector('[data-role="due_date"]');
        const amtInp = tr.querySelector('[data-role="amount"]');
        const curSel = tr.querySelector('[data-role="currency_id"]');
        const delBtn = tr.querySelector('button');
        if (amt !== '' && amt !== null) {
          amtInp.value = formatTRYStyle(amt);
        }

        function syncDueState() {
          // Allow due_date to be editable for all payment types
          dueInp.disabled = false;
        }
        typeSel.addEventListener('change', () => {
          syncDueState();
          recalc();
        });
        dueInp.addEventListener('input', recalc);
        curSel.addEventListener('change', recalc);
        delBtn.addEventListener('click', () => {
          tr.remove();
          recalc();
        });
        attachMoneyFormatting(amtInp);
        tb.appendChild(tr);
        syncDueState();
        recalc();
      }

      async function recalc() {
        const rows = Array.from(tb.children).map(tr => {
          const type = tr.querySelector('[data-role="type"]').value;
          const due = tr.querySelector('[data-role="due_date"]').value || null;
          const amountStr = tr.querySelector('[data-role="amount"]').value;
          const amount = parseMoneyInput(amountStr);
          const currency_id = Number(tr.querySelector('[data-role="currency_id"]').value);
          return {
            type,
            due_date: due,
            amount,
            currency_id
          };
        }).filter(r => r.amount > 0);
        payload.value = JSON.stringify(rows);
        const uniq = Array.from(new Set(rows.map(r => r.currency_id)));
        let finalCurrencyId = 949;
        let needTRY = false;
        if (uniq.length === 1) finalCurrencyId = uniq[0];
        else if (uniq.length > 1) {
          finalCurrencyId = 949;
          needTRY = true;
        }
        let total = 0;
        if (!needTRY && finalCurrencyId !== 949) {
          total = rows.filter(r => r.currency_id === finalCurrencyId).reduce((s, r) => s + r.amount, 0);
        } else if (!needTRY && finalCurrencyId === 949) {
          total = rows.filter(r => r.currency_id === 949).reduce((s, r) => s + r.amount, 0);
        } else {
          const dateStr = contractDateInput?.value || '';
          const rates = await getRates(dateStr);
          total = rows.reduce((s, r) => {
            if (r.currency_id === 949) return s + r.amount;
            if (r.currency_id === 978) return s + r.amount * (Number(rates.EUR) || 0);
            if (r.currency_id === 840) return s + r.amount * (Number(rates.USD) || 0);
            return s;
          }, 0);
        }
        const curName = ({
          949: 'TRY',
          978: 'EUR',
          840: 'USD'
        })[finalCurrencyId] || 'TRY';
        amountInput.value = total ? formatTRYStyle(total) : '';
        currencyNameInput.value = curName;
        amountInWords.value = total ? numberToTextTR(total, curName) : '';
        amountHidden.value = total ? total.toFixed(2) : '';
        currencyIdHidden.value = String(finalCurrencyId);
        currencyNameHidden.value = curName;
        amountInWordsHidden.value = amountInWords.value || '';

        const cDate = contractDateInput?.value || '';
        Array.from(tb.children).forEach(tr => {
          const typeSel = tr.querySelector('[data-role="type"]');
          const dueInp = tr.querySelector('[data-role="due_date"]');
          if (typeSel && dueInp && typeSel.value !== 'cheque') {
            dueInp.value = cDate;
          }
        });
      }

      // Ödeme butonları
      document.getElementById('btnAddPayment')?.addEventListener('click', () => addRow());
      document.getElementById('btnAddCheque')?.addEventListener('click', () => addRow({
        type: 'cheque'
      }));
      document.getElementById('btnAddCash')?.addEventListener('click', () => addRow({
        type: 'cash'
      }));
      document.getElementById('btnAddTransfer')?.addEventListener('click', () => addRow({
        type: 'transfer'
      }));

      // Prefill payments
      (function initPayments() {
        try {
          const existing = <?= json_encode($c['payments'] ?? []) ?>;
          if (Array.isArray(existing) && existing.length > 0) {
            existing.forEach(r => addRow({
              type: r.type || 'cash',
              due_date: r.due_date || (document.getElementById('contract_date')?.value || ''),
              amount: r.amount ?? '',
              currency_id: r.currency_id ?? 949
            }));
          } else {
            addRow();
          }
        } catch (e) {
          console.error('Prefill okunamadı:', e);
          addRow();
        }
      })();

      // Submit kontrol
      const form = document.querySelector('form[action]');
      form?.addEventListener('submit', async (e) => {
        // Make sure recalc() has completed before submitting
        await recalc();

        // Ensure payments_payload is set
        const payload = document.getElementById('payments_payload');
        if (payload) {
          console.log('Payload value before submit:', payload.value);
        }

        if (!document.getElementById('contractor_company_id')?.value) {
          e.preventDefault();
          alert('Lütfen yüklenici firmayı listeden seçiniz.');
          document.getElementById('contractor_company_name')?.focus();
          return;
        }

        // Check if this is an edit form (has id field)
        const idField = document.querySelector('input[name="id"]');
        if (idField && idField.value) {
          // This is an update - handle with AJAX
          e.preventDefault();

          const formData = new FormData(form);

          try {
            const response = await fetch(form.action, {
              method: 'POST',
              body: formData
            });

            const contentType = response.headers.get('content-type');
            let result = {};

            if (contentType && contentType.includes('application/json')) {
              result = await response.json();
            }

            if (response.ok && result.ok) {
              // Success - show message and redirect
              alert(result.message || 'Sözleşme başarıyla güncellendi');
              window.location.href = '/contracts';
            } else {
              // Error
              alert(result.message || 'Güncelleme başarısız oldu');
            }
          } catch (error) {
            console.error('Error:', error);
            alert('Bir hata oluştu: ' + error.message);
          }
        }
      });

      document.getElementById('contract_date')?.addEventListener('change', recalc);

      // ------- Auto-generate contract title -------
      const contractTitleDisplay = document.getElementById('contractTitleDisplay');
      const projectNameInput = document.getElementById('project_name');
      const subjectInput = document.querySelector('input[name="subject"]');
      const contractorNameForTitle = document.getElementById('contractor_company_name');
      // contractDateInput already declared above for payment section

      function extractProjectCode(text) {
        if (!text) return 'XXXXXX';

        // Uppercase and remove non-alphanumeric
        text = text.toUpperCase();
        text = text.replace(/[^A-Z0-9\s]/g, '');
        text = text.trim();

        if (!text) return 'XXXXXX';

        // Split by spaces
        const words = text.split(/\s+/).filter(w => w.length > 0);

        if (words.length === 0) return 'XXXXXX';

        // Single word: take first 6 characters
        if (words.length === 1) {
          return (words[0] + 'XXXXXX').substring(0, 6);
        }

        // Multiple words: 3 chars from first + 3 chars from second
        const code = words[0].substring(0, 3) + words[1].substring(0, 3);
        return (code + 'XXXXXX').substring(0, 6);
      }

      function extractCode(text, length) {
        if (!text) return 'X'.repeat(length);

        // Uppercase and remove non-alphanumeric
        text = text.toUpperCase();
        text = text.replace(/[^A-Z0-9]/g, '');

        if (!text) return 'X'.repeat(length);

        // Pad with X if too short
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

      function updateContractTitle() {
        const projectName = projectNameInput?.value || '';
        const subject = subjectInput?.value || '';
        const contractorName = contractorNameForTitle?.value || '';
        const contractDate = contractDateInput?.value || '';

        // Extract codes
        const prj = extractProjectCode(projectName);
        const subj = extractCode(subject, 8);
        const cont = extractCode(contractorName, 8);
        const dateFormatted = formatDateForTitle(contractDate);

        const generatedTitle = `SZL_${prj}_${subj}_${cont}_${dateFormatted}`;

        if (contractTitleDisplay) {
          contractTitleDisplay.textContent = generatedTitle;
        }
      }

      // Listen for changes to trigger title update
      projectNameInput?.addEventListener('change', updateContractTitle);
      projectNameInput?.addEventListener('input', updateContractTitle);
      subjectInput?.addEventListener('change', updateContractTitle);
      subjectInput?.addEventListener('input', updateContractTitle);
      contractorNameForTitle?.addEventListener('change', updateContractTitle);
      contractorNameForTitle?.addEventListener('input', updateContractTitle);
      contractDateInput?.addEventListener('change', updateContractTitle);
      contractDateInput?.addEventListener('input', updateContractTitle);

      // Initialize title display on page load
      updateContractTitle();

      // Word ve PDF butonları (edit modu için)
      const contractId = '<?= Helpers::e((string)($c['id'] ?? '')) ?>';
      document.getElementById('btnGenerateWord')?.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!contractId) {
          alert('Lütfen önce sözleşmeyi kaydedin');
          return;
        }
        try {
          const response = await fetch('/contracts/generate-word?id=' + encodeURIComponent(contractId));
          if (response.ok) {
            const blob = await response.blob();
            const contractTitle = contractTitleDisplay?.textContent || 'sozlesme';
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${contractTitle}.docx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
          } else {
            alert('Word oluşturulamadı: ' + response.statusText);
          }
        } catch (err) {
          console.error(err);
          alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
        }
      });

      document.getElementById('btnGeneratePdf')?.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!contractId) {
          alert('Lütfen önce sözleşmeyi kaydedin');
          return;
        }
        try {
          // First try to show PDF by contract title if exists
          const contractTitle = document.getElementById('contractTitleDisplay')?.value?.trim();
          if (contractTitle && contractTitle !== 'SZL_XXXXXX_XXXXXXXX_XXXXXXXX_YYYYMMDD') {
            const titleResponse = await fetch('/contracts/get-pdf-by-title?title=' + encodeURIComponent(contractTitle));
            if (titleResponse.ok) {
              const blob = await titleResponse.blob();
              const url = URL.createObjectURL(blob);
              window.open(url, '_blank');
              setTimeout(() => URL.revokeObjectURL(url), 100);
              return;
            }
          }

          // Second, try to show uploaded PDF if exists
          const uploadedResponse = await fetch('/contracts/get-uploaded-pdf?id=' + encodeURIComponent(contractId));
          if (uploadedResponse.ok) {
            const blob = await uploadedResponse.blob();
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 100);
            return;
          }

          // Fall back to generating PDF from contract data
          const response = await fetch('/contracts/generate-pdf?id=' + encodeURIComponent(contractId));
          if (response.ok) {
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 100);
          } else {
            alert('PDF oluşturulamadı: ' + response.statusText);
          }
        } catch (err) {
          console.error(err);
          alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
        }
      });

      // PDF Upload handler (now using UUID-based folder like document management)
      const btnUploadPdf = document.getElementById('btnUploadPdf');
      const pdfFileInput = document.getElementById('pdfFileInput');
      btnUploadPdf?.addEventListener('click', (e) => {
        e.preventDefault();
        if (!contractId) {
          alert('Lütfen önce sözleşmeyi kaydedin');
          return;
        }
        pdfFileInput?.click();
      });
      pdfFileInput?.addEventListener('change', async (e) => {
        const file = e.target.files?.[0];
        if (!file || file.type !== 'application/pdf') {
          alert('Lütfen bir PDF dosyası seçiniz');
          return;
        }
        if (!contractId) {
          alert('Sözleşme ID bulunamadı');
          return;
        }
        try {
          btnUploadPdf.disabled = true;
          const formData = new FormData();
          formData.append('id', contractId);
          formData.append('pdf', file);

          const response = await fetch('/contracts/upload-pdf', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
          });

          const data = await response.json();

          if (response.ok) {
            alert('PDF dosyası başarıyla yüklendi');
            pdfFileInput.value = '';
            // Refresh the documents list so the PDF appears there
            const btnRefreshDocuments = document.getElementById('btnRefreshDocuments');
            if (btnRefreshDocuments) btnRefreshDocuments.click();
          } else {
            alert('PDF yüklenemedi: ' + (data.error || 'Bilinmeyen hata'));
          }
        } catch (err) {
          alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
        } finally {
          btnUploadPdf.disabled = false;
        }
        // Reset file input
        pdfFileInput.value = '';
      });

      // İlk yükleme: disiplinler
      loadDisciplines();

      // ===== Document Management =====
      const btnUploadDocument = document.getElementById('btnUploadDocument');
      const documentFileInput = document.getElementById('documentFileInput');
      const btnOpenFolder = document.getElementById('btnOpenFolder');
      const btnRefreshDocuments = document.getElementById('btnRefreshDocuments');
      const documentsList = document.getElementById('documentsList');

      if (btnUploadDocument && contractId) {
        btnUploadDocument.addEventListener('click', async () => {
          if (!documentFileInput.files[0]) {
            alert('Lütfen bir dosya seçiniz');
            return;
          }

          const file = documentFileInput.files[0];
          const formData = new FormData();
          formData.append('contract_id', contractId);
          formData.append('document', file);

          try {
            btnUploadDocument.disabled = true;
            const response = await fetch('/contracts/upload-document', {
              method: 'POST',
              body: formData
            });

            const data = await response.json();
            if (response.ok) {
              alert('Döküman başarıyla yüklendi');
              documentFileInput.value = '';
              // Refresh the documents list
              if (btnRefreshDocuments) btnRefreshDocuments.click();
            } else {
              alert('Döküman yüklenemedi: ' + (data.error || 'Bilinmeyen hata'));
            }
          } catch (err) {
            alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
          } finally {
            btnUploadDocument.disabled = false;
          }
        });
      }

      if (btnRefreshDocuments && contractId) {
        const loadDocuments = async () => {
          try {
            const response = await fetch('/contracts/list-documents?id=' + encodeURIComponent(contractId));
            if (!response.ok) {
              documentsList.innerHTML = '<div class="text-danger text-center py-3">Dökümanlar yüklenemedi</div>';
              return;
            }

            const data = await response.json();
            if (!data.ok || !data.documents || data.documents.length === 0) {
              documentsList.innerHTML = '<div class="text-muted text-center py-3">Döküman yok</div>';
              return;
            }

            documentsList.innerHTML = data.documents.map(doc => {
              const isPdf = doc.name.toLowerCase().endsWith('.pdf');
              return `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <i class="bi bi-file me-2"></i>
                    <strong>${document.createTextNode(doc.name).textContent}</strong>
                    <br>
                    <small class="text-muted">${(doc.size / 1024 / 1024).toFixed(2)} MB • ${new Date(doc.modified * 1000).toLocaleString('tr-TR')}</small>
                  </div>
                  <div class="d-flex gap-2">
                    ${isPdf ? `<a href="${doc.url}" target="_blank" class="btn btn-sm btn-outline-info" title="Görüntüle"><i class="bi bi-eye"></i></a>` : ''}
                    <a href="${doc.url}" class="btn btn-sm btn-outline-primary" download title="İndir"><i class="bi bi-download"></i></a>
                    <button type="button" class="btn btn-sm btn-outline-danger deleteDocBtn" data-filename="${document.createTextNode(doc.name).textContent}" title="Sil"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
              `;
            }).join('');

            // Attach delete event listeners
            document.querySelectorAll('.deleteDocBtn').forEach(btn => {
              btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const filename = btn.getAttribute('data-filename');
                if (!confirm('Dosya silinecektir: ' + filename + '\n\nEmin misiniz?')) {
                  return;
                }
                try {
                  const response = await fetch('/contracts/delete-document', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(contractId) + '&file=' + encodeURIComponent(filename)
                  });
                  const data = await response.json();
                  if (response.ok) {
                    alert('Dosya başarıyla silindi');
                    loadDocuments();
                  } else {
                    alert('Dosya silinemedi: ' + (data.error || 'Bilinmeyen hata'));
                  }
                } catch (err) {
                  alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
                }
              });
            });
          } catch (err) {
            console.error('Error loading documents:', err);
            documentsList.innerHTML = '<div class="text-danger text-center py-3">Hata: ' + (err?.message || 'Bilinmeyen hata') + '</div>';
          }
        };

        btnRefreshDocuments.addEventListener('click', loadDocuments);
        // Load documents on page load
        loadDocuments();
      }

      if (btnOpenFolder && contractId) {
        btnOpenFolder.addEventListener('click', async () => {
          try {
            const response = await fetch('/contracts/open-document-folder?id=' + encodeURIComponent(contractId));
            const data = await response.json();
            if (!response.ok) {
              alert('Klasör bilgisi alınamadı: ' + (data.error || 'Bilinmeyen hata'));
              return;
            }

            // For Windows: use file:// protocol
            const folderPath = data.folder_path.replace(/\\/g, '/');
            if (navigator.platform.indexOf('Win') > -1) {
              // Windows: try to open explorer
              const fileUrl = 'file:///' + folderPath.replace(/\//g, '\\');
              alert('Klasör konumu: ' + data.folder_path + '\n\nKlasörü file explorerde açmak için tarayıcı kısıtlamaları var.\nLütfen aşağıdaki yolu kopyalayıp file explorerde açınız:\n\n' + data.folder_path);
            } else if (navigator.platform.indexOf('Mac') > -1) {
              // Mac: use file:// protocol
              window.location.href = 'file://' + folderPath;
            } else {
              // Linux: use file:// protocol
              window.open('file://' + folderPath, '_blank');
            }
          } catch (err) {
            alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
          }
        });
      }

    } catch (outer) {
      console.error('Genel JS hatası:', outer);
    }
  });
</script>

<!-- Create Discipline Modal -->
<div class="modal fade" id="createDisciplineModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Yeni Disiplin Oluştur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="createDisciplineForm">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="new_discipline_name_tr" class="form-label">Disiplin Adı (Türkçe) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="new_discipline_name_tr" name="name_tr"
                  placeholder="Örn: İnşaat" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="new_discipline_name_en" class="form-label">Disiplin Adı (İngilizce) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="new_discipline_name_en" name="name_en"
                  placeholder="Örn: Construction" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-success">Oluştur</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create Branch Modal -->
<div class="modal fade" id="createBranchModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Yeni Alt Disiplin Oluştur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="createBranchForm">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Disiplin</label>
            <div class="alert alert-info mb-0" id="selectedDisciplineDisplay">
              Lütfen önce disiplin seçiniz
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="new_branch_name_tr" class="form-label">Alt Disiplin Adı (Türkçe) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="new_branch_name_tr" name="name_tr"
                  placeholder="Örn: Beton" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="new_branch_name_en" class="form-label">Alt Disiplin Adı (İngilizce) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="new_branch_name_en" name="name_en"
                  placeholder="Örn: Concrete" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-success" id="createBranchSubmit">Oluştur</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const disciplineForm = document.getElementById('createDisciplineForm');
    const branchForm = document.getElementById('createBranchForm');
    const disciplineSelect = document.getElementById('discipline_id');
    const branchSelect = document.getElementById('branch_id');
    const addBranchBtn = document.getElementById('addBranchBtn');
    const selectedDisciplineDisplay = document.getElementById('selectedDisciplineDisplay');

    if (disciplineForm) {
      disciplineForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const nameTr = document.getElementById('new_discipline_name_tr').value.trim();
        const nameEn = document.getElementById('new_discipline_name_en').value.trim();

        if (!nameTr || !nameEn) {
          alert('Her iki dil için de disiplin adı zorunludur');
          return;
        }

        try {
          const response = await fetch('/api/disciplines/create', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              name_tr: nameTr,
              name_en: nameEn,
              is_active: true
            })
          });

          const data = await response.json();
          if (!response.ok) {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
            return;
          }

          // Add to dropdown and select it
          const option = document.createElement('option');
          option.value = data.id;
          option.textContent = data.name_tr || data.name_en;
          disciplineSelect.appendChild(option);
          disciplineSelect.value = data.id;

          // Trigger change event to load branches
          const event = new Event('change', {
            bubbles: true
          });
          disciplineSelect.dispatchEvent(event);

          // Close modal and reset form
          bootstrap.Modal.getInstance(document.getElementById('createDisciplineModal')).hide();
          disciplineForm.reset();

          alert('Disiplin başarıyla oluşturuldu');
        } catch (err) {
          alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
        }
      });
    }

    if (branchForm) {
      branchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const disciplineId = disciplineSelect.value;
        const nameTr = document.getElementById('new_branch_name_tr').value.trim();
        const nameEn = document.getElementById('new_branch_name_en').value.trim();

        if (!disciplineId) {
          alert('Lütfen önce disiplin seçiniz');
          return;
        }

        if (!nameTr || !nameEn) {
          alert('Her iki dil için de alt disiplin adı zorunludur');
          return;
        }

        try {
          const response = await fetch('/api/disciplines/create-branch', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              discipline_id: disciplineId,
              name_tr: nameTr,
              name_en: nameEn,
              is_active: true
            })
          });

          const data = await response.json();
          if (!response.ok) {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
            return;
          }

          // Add to dropdown and select it
          const option = document.createElement('option');
          option.value = data.id;
          option.textContent = data.name_tr || data.name_en;
          branchSelect.appendChild(option);
          branchSelect.value = data.id;

          // Close modal and reset form
          bootstrap.Modal.getInstance(document.getElementById('createBranchModal')).hide();
          branchForm.reset();
          updateSelectedDisciplineDisplay();

          alert('Alt disiplin başarıyla oluşturuldu');
        } catch (err) {
          alert('Hata: ' + (err?.message || 'Bilinmeyen hata'));
        }
      });
    }

    // Update branch modal display when discipline selection changes
    function updateSelectedDisciplineDisplay() {
      const selectedId = disciplineSelect.value;
      const selectedOption = disciplineSelect.querySelector(`option[value="${selectedId}"]`);
      if (selectedId && selectedOption) {
        selectedDisciplineDisplay.textContent = selectedOption.textContent;
        selectedDisciplineDisplay.className = 'alert alert-success mb-0';
      } else {
        selectedDisciplineDisplay.textContent = 'Lütfen disiplin seçiniz';
        selectedDisciplineDisplay.className = 'alert alert-info mb-0';
      }
    }

    if (disciplineSelect) {
      disciplineSelect.addEventListener('change', updateSelectedDisciplineDisplay);
    }

    if (addBranchBtn && disciplineSelect) {
      disciplineSelect.addEventListener('change', () => {
        addBranchBtn.disabled = !disciplineSelect.value;
      });
    }
  });
</script>