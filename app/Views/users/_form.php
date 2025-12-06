<?php

use App\Core\Helpers;

// Beklenen girişler:

// - $c: array (form değerleri; create için boş veya varsayılanlar)

// - $action: string (örn: "/users/store" veya "/users/update")

// - $submitLabel: string (örn: "Kaydet" veya "Güncelle")

// - $title: string (örn: "Kullanıcı Ekle" veya "Kullanıcıyı Düzenle")

// - $showIdHidden: bool (edit’te true, create’te false)

// - $backUrl: string (örn: "/users")

// - $companies: array|null (firma seçim listesi)

$checked = function ($v) {

if (is_bool($v)) return $v;

if ($v === null) return false;

$v = strtolower((string)$v);

return in_array($v, ['1','true','on','yes','evet'], true);

};

// Tarih alanları farklı formatlarla gelebilir, input date için Y-m-d'e normalize edelim

$fmtDate = function ($v) {

if (!$v) return '';

$s = (string)$v;

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;

try {

$dt = new DateTime($s);

return $dt->format('Y-m-d');

} catch (\Throwable $e) {

return '';

}

};

?>

<div class="page-title-bar d-flex justify-content-between align-items-center mb-3">

<h1 class="h4 m-0"><?= Helpers::e($title ?? '') ?></h1>

<div class="d-flex gap-2">

<a href="<?= Helpers::e($backUrl ?? '/users') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>

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
  <!-- Temel Bilgiler -->
  <div>
    <div class="card shadow-sm h-100 form-card">
      <div class="card-header"><strong>Temel Bilgiler</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <label class="form-label">Ad (first_name) *</label>
            <input type="text" name="first_name" required maxlength="100" value="<?= Helpers::e($c['first_name'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">İkinci Ad (middle_name)</label>
            <input type="text" name="middle_name" maxlength="100" value="<?= Helpers::e($c['middle_name'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Soyad (last_name) *</label>
            <input type="text" name="last_name" required maxlength="100" value="<?= Helpers::e($c['last_name'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">E-posta (email) *</label>
            <input type="email" name="email" required maxlength="255" value="<?= Helpers::e($c['email'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Cinsiyet (gender)</label>
            <?php
              $gender = $c['gender'] ?? '';
              $genderOpts = [
                '' => '— Seçiniz —',
                'male' => 'Erkek',
                'female' => 'Kadın',
                'nonbinary' => 'Non-binary',
                'unknown' => 'Bilinmiyor'
              ];
            ?>
            <select name="gender" class="form-select">
              <?php foreach ($genderOpts as $val => $label): ?>
                <option value="<?= Helpers::e($val) ?>" <?= ($gender === $val) ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Doğum Tarihi (birth_date)</label>
            <input type="date" name="birth_date" value="<?= Helpers::e($fmtDate($c['birth_date'] ?? null)) ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Vefat Tarihi (death_date)</label>
            <input type="date" name="death_date" value="<?= Helpers::e($fmtDate($c['death_date'] ?? null)) ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Telefon (phone)</label>
            <input type="tel" name="phone" maxlength="20" pattern="[\d+\-\s()]{6,20}" value="<?= Helpers::e($c['phone'] ?? '') ?>" class="form-control" placeholder="+90 555 555 55 55">
          </div>
          <div class="col-12">
            <label class="form-label">İkincil Telefon (secondary_phone)</label>
            <input type="tel" name="secondary_phone" maxlength="20" pattern="[\d+\-\s()]{6,20}" value="<?= Helpers::e($c['secondary_phone'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Medeni Hali (marital_status)</label>
            <?php
              $ms = $c['marital_status'] ?? '';
              $msOpts = [
                '' => '— Seçiniz —',
                'single' => 'Bekar',
                'married' => 'Evli',
                'divorced' => 'Boşanmış',
                'widowed' => 'Dul',
                'other' => 'Diğer'
              ];
            ?>
            <select name="marital_status" class="form-select">
              <?php foreach ($msOpts as $val => $label): ?>
                <option value="<?= Helpers::e($val) ?>" <?= ($ms === $val) ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Uyruk Kodu (nationality_code, ISO-2)</label>
            <input type="text" name="nationality_code" maxlength="2" value="<?= Helpers::e($c['nationality_code'] ?? '') ?>" class="form-control" placeholder="TR">
          </div>
          <div class="col-12">
            <label class="form-label">Doğum Yeri (place_of_birth)</label>
            <input type="text" name="place_of_birth" maxlength="120" value="<?= Helpers::e($c['place_of_birth'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Zaman Dilimi (timezone)</label>
            <input type="text" name="timezone" maxlength="50" value="<?= Helpers::e($c['timezone'] ?? '') ?>" class="form-control" placeholder="Europe/Istanbul">
          </div>
          <div class="col-12">
            <label class="form-label">Dil (language, IETF)</label>
            <input type="text" name="language" maxlength="5" value="<?= Helpers::e($c['language'] ?? '') ?>" class="form-control" placeholder="tr veya tr-TR">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Kimlik ve Diğer -->
  <div>
    <div class="card shadow-sm h-100 form-card">
      <div class="card-header"><strong>Kimlik ve Diğer</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <label class="form-label">T.C./Ulusal ID (national_id)</label>
            <input type="text" name="national_id" maxlength="50" value="<?= Helpers::e($c['national_id'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Pasaport No (passport_no)</label>
            <input type="text" name="passport_no" maxlength="30" value="<?= Helpers::e($c['passport_no'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Fotoğraf URL (photo_url)</label>
            <input type="url" name="photo_url" maxlength="2048" value="<?= Helpers::e($c['photo_url'] ?? '') ?>" class="form-control" placeholder="https://">
          </div>
          <div class="col-12">
            <label class="form-label">Notlar (notes)</label>
            <textarea name="notes" rows="3" class="form-control"><?= Helpers::e($c['notes'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <div class="form-check" style="margin-top:.25rem;">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $checked($c['is_active'] ?? true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_active">Aktif (is_active)</label>
            </div>
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
            <label class="form-label">Ülke Kodu (country_code, ISO-2)</label>
            <input type="text" name="country_code" maxlength="2" pattern="[A-Za-z]{2}" value="<?= Helpers::e($c['country_code'] ?? '') ?>" class="form-control" placeholder="TR">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Firma -->
  <div>
    <div class="card shadow-sm h-100 form-card">
      <div class="card-header"><strong>Firma</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <label class="form-label">Firma Seçimi (company_id)</label>
            <select name="company_id" class="form-select">
              <option value=""><?= Helpers::e('— Seçiniz —') ?></option>
              <?php
                $currentCompanyId = (string)($c['company_id'] ?? '');
                foreach (($companies ?? []) as $co):
                  $cid = (string)($co['id'] ?? '');
                  $name = $co['name'] ?? '';
              ?>
                <option value="<?= Helpers::e($cid) ?>" <?= ($currentCompanyId !== '' && $currentCompanyId === $cid) ? 'selected' : '' ?>>
                  <?= Helpers::e($name) ?>
                </option>
              <?php endforeach; ?>
            </select>
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

<a href="<?= Helpers::e($backUrl ?? '/users') ?>" class="btn btn-outline-secondary">İptal</a>

</div>

</form>