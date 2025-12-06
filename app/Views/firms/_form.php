<?php

use App\Core\Helpers;

// Beklenen girişler:

// - $c: array (form değerleri; create için boş veya varsayılanlar)

// - $action: string (örn: "/firms/store" veya "/firms/update")

// - $submitLabel: string (örn: "Kaydet" veya "Güncelle")

// - $title: string (örn: "Firma Ekle" veya "Firmayı Düzenle")

// - $showIdHidden: bool (edit’te true, create’te false)

// - $backUrl: string (örn: "/firms")

$checked = function ($v) {

if (is_bool($v)) return $v;

if ($v === null) return false;

$v = strtolower((string)$v);

return in_array($v, ['1','true','on','yes','evet'], true);

};

?>

<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">

<h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>

<div class="d-flex gap-2">

<a href="<?= Helpers::e($backUrl ?? '/firms') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>

</div>

</div>

<form method="post" action="<?= Helpers::e($action) ?>" novalidate>

<?php if (!empty($showIdHidden)): ?>

<input type="hidden" name="id" value="<?= Helpers::e((string)($c['id'] ?? '')) ?>">

<?php endif; ?>

<style>

.form-label { display:block; margin-bottom:.5rem; line-height:1.3; font-weight:600; font-size:1rem; }

.card .form-control, .card .form-select, .card textarea { margin-bottom:1rem; padding:.75rem 1rem; font-size:1rem; }

.card { border-radius:.6rem; }

.card-header { font-weight:700; background:#f8f9fb; }

/* Grid: soldan başlat, asla ortalama */

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

.form-card { width: 100%; }

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
            <label class="form-label">Firma Adı (name) *</label>
            <input type="text" name="name" required maxlength="200" value="<?= Helpers::e($c['name'] ?? '') ?>" class="form-control" placeholder="Örn: ABC Teknoloji A.Ş.">
          </div>
          <div class="col-12">
            <label class="form-label">Kısa Ad (short_name)</label>
            <input type="text" name="short_name" maxlength="100" value="<?= Helpers::e($c['short_name'] ?? '') ?>" class="form-control" placeholder="Örn: ABC">
          </div>
          <div class="col-12">
            <label class="form-label">Hukuki Tip (legal_type)</label>
            <?php
              $lt = $c['legal_type'] ?? '';
              $ltOpts = [
                '' => '— Seçiniz —',
                'limited' => 'Limited',
                'anonim' => 'Anonim',
                'sole_prop' => 'Şahıs',
                'ngo' => 'Dernek/Vakıf',
                'other' => 'Diğer'
              ];
            ?>
            <select name="legal_type" class="form-select">
              <?php foreach ($ltOpts as $val => $label): ?>
                <option value="<?= Helpers::e($val) ?>" <?= ($lt === $val) ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Ticaret Sicil No (registration_no)</label>
            <input type="text" name="registration_no" maxlength="100" value="<?= Helpers::e($c['registration_no'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">MERSİS No (mersis_no)</label>
            <input type="text" name="mersis_no" maxlength="50" value="<?= Helpers::e($c['mersis_no'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Vergi Dairesi (tax_office)</label>
            <input type="text" name="tax_office" maxlength="120" value="<?= Helpers::e($c['tax_office'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Vergi No (tax_number)</label>
            <input type="text" name="tax_number" maxlength="50" value="<?= Helpers::e($c['tax_number'] ?? '') ?>" class="form-control">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- İletişim -->
  <div>
    <div class="card shadow-sm h-100 form-card">
      <div class="card-header"><strong>İletişim</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <label class="form-label">E-posta (email)</label>
            <input type="email" name="email" maxlength="255" value="<?= Helpers::e($c['email'] ?? '') ?>" class="form-control" placeholder="info@firma.com">
          </div>
          <div class="col-12">
            <label class="form-label">Telefon (phone)</label>
            <input type="tel" name="phone" maxlength="30" pattern="[\d+\-\s()]{6,30}" value="<?= Helpers::e($c['phone'] ?? '') ?>" class="form-control" placeholder="+90 212 000 00 00">
          </div>
          <div class="col-12">
            <label class="form-label">İkincil Telefon (secondary_phone)</label>
            <input type="tel" name="secondary_phone" maxlength="30" pattern="[\d+\-\s()]{6,30}" value="<?= Helpers::e($c['secondary_phone'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Faks (fax)</label>
            <input type="tel" name="fax" maxlength="30" pattern="[\d+\-\s()]{6,30}" value="<?= Helpers::e($c['fax'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Web Sitesi (website)</label>
            <input type="url" name="website" maxlength="2048" value="<?= Helpers::e($c['website'] ?? '') ?>" class="form-control" placeholder="https://">
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
            <input type="text" name="address_line1" maxlength="200" value="<?= Helpers::e($c['address_line1'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Adres Satırı 2 (address_line2)</label>
            <input type="text" name="address_line2" maxlength="200" value="<?= Helpers::e($c['address_line2'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Şehir (city)</label>
            <input type="text" name="city" maxlength="100" value="<?= Helpers::e($c['city'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Eyalet/Bölge (state_region)</label>
            <input type="text" name="state_region" maxlength="100" value="<?= Helpers::e($c['state_region'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Posta Kodu (postal_code)</label>
            <input type="text" name="postal_code" maxlength="20" value="<?= Helpers::e($c['postal_code'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Ülke Kodu (ISO-2)</label>
            <input type="text" name="country_code" maxlength="2" pattern="[A-Za-z]{2}" value="<?= Helpers::e($c['country_code'] ?? '') ?>" class="form-control" placeholder="TR">
          </div>
          <div class="col-12">
            <label class="form-label">Enlem (latitude)</label>
            <input type="number" name="latitude" step="0.000001" min="-90" max="90" value="<?= Helpers::e((string)($c['latitude'] ?? '')) ?>" class="form-control" placeholder="41.008240">
          </div>
          <div class="col-12">
            <label class="form-label">Boylam (longitude)</label>
            <input type="number" name="longitude" step="0.000001" min="-180" max="180" value="<?= Helpers::e((string)($c['longitude'] ?? '')) ?>" class="form-control" placeholder="28.978359">
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
            <label class="form-label">Sektör (industry)</label>
            <input type="text" name="industry" maxlength="120" value="<?= Helpers::e($c['industry'] ?? '') ?>" class="form-control" placeholder="Örn: Yazılım">
          </div>
          <div class="col-12">
            <label class="form-label">Durum (status)</label>
            <?php $status = $c['status'] ?? 'active'; $statusOpts = ['active','prospect','lead','suspended','inactive']; ?>
            <select name="status" class="form-select">
              <?php foreach ($statusOpts as $s): ?>
                <option value="<?= Helpers::e($s) ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= Helpers::e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Para Birimi (currency_code)</label>
            <input type="text" name="currency_code" maxlength="3" pattern="[A-Za-z]{3}" value="<?= Helpers::e($c['currency_code'] ?? '') ?>" class="form-control" placeholder="TRY">
          </div>
          <div class="col-12">
            <label class="form-label">Zaman Dilimi (timezone)</label>
            <input type="text" name="timezone" maxlength="50" value="<?= Helpers::e($c['timezone'] ?? '') ?>" class="form-control" placeholder="Europe/Istanbul">
          </div>

          <div class="col-12">
            <div class="d-flex flex-column gap-2 pt-1">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="vat_exempt" name="vat_exempt" value="1" <?= $checked($c['vat_exempt'] ?? false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="vat_exempt">KDV Muaf (vat_exempt)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="e_invoice_enabled" name="e_invoice_enabled" value="1" <?= $checked($c['e_invoice_enabled'] ?? false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="e_invoice_enabled">e-Fatura Aktif (e_invoice_enabled)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $checked($c['is_active'] ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktif (is_active)</label>
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Logo URL (logo_url)</label>
            <input type="url" name="logo_url" maxlength="2048" value="<?= Helpers::e($c['logo_url'] ?? '') ?>" class="form-control" placeholder="https://.../logo.png">
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

<a href="<?= Helpers::e($backUrl ?? '/firms') ?>" class="btn btn-outline-secondary">İptal</a>

</div>

</form>