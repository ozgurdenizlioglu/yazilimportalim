<?php use App\Core\Helpers;

?>

<h1><?= Helpers::e($title ?? 'Firmayı Düzenle') ?></h1>

<?php
// Yardımcı: bool checkbox
$checked = function ($v) {
    if (is_bool($v)) {
        return $v;
    }
    if ($v === null) {
        return false;
    }
    $v = strtolower((string)$v);
    return in_array($v, ['1','true','on','yes'], true);
};

$c = $company ?? [];
?>

<form method="post" action="/firms/update" novalidate style="max-width: 1000px;">
  <input type="hidden" name="id" value="<?= Helpers::e((string)($c['id'] ?? '')) ?>">

  <fieldset>
    <legend>Kimlik Bilgileri</legend>

    <label>Firma Adı (name) *
      <input type="text" name="name" required maxlength="200" value="<?= Helpers::e($c['name'] ?? '') ?>" placeholder="Örn: ABC Teknoloji A.Ş.">
    </label>

    <label>Kısa Ad (short_name)
      <input type="text" name="short_name" maxlength="100" value="<?= Helpers::e($c['short_name'] ?? '') ?>" placeholder="Örn: ABC">
    </label>

    <label>Hukuki Tip (legal_type)
      <?php $lt = $c['legal_type'] ?? ''; ?>
      <select name="legal_type">
        <?php
          $ltOpts = [
            '' => '— Seçiniz —',
            'limited' => 'Limited',
            'anonim' => 'Anonim',
            'sole_prop' => 'Şahıs',
            'ngo' => 'Dernek/Vakıf',
            'other' => 'Diğer',
          ];
?>
        <?php foreach ($ltOpts as $val => $label): ?>
          <option value="<?= Helpers::e($val) ?>" <?= ($lt === $val) ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Ticaret Sicil No (registration_no)
      <input type="text" name="registration_no" maxlength="100" value="<?= Helpers::e($c['registration_no'] ?? '') ?>">
    </label>

    <label>MERSİS No (mersis_no)
      <input type="text" name="mersis_no" maxlength="50" value="<?= Helpers::e($c['mersis_no'] ?? '') ?>">
    </label>

    <label>Vergi Dairesi (tax_office)
      <input type="text" name="tax_office" maxlength="120" value="<?= Helpers::e($c['tax_office'] ?? '') ?>">
    </label>

    <label>Vergi No (tax_number)
      <input type="text" name="tax_number" maxlength="50" value="<?= Helpers::e($c['tax_number'] ?? '') ?>">
    </label>
  </fieldset>

  <fieldset>
    <legend>İletişim</legend>

    <label>E-posta (email)
      <input type="email" name="email" maxlength="255" value="<?= Helpers::e($c['email'] ?? '') ?>" placeholder="info@firma.com">
    </label>

    <label>Telefon (phone)
      <input type="tel" name="phone" maxlength="30" pattern="[\d+\-\s()]{6,30}" value="<?= Helpers::e($c['phone'] ?? '') ?>" placeholder="+90 212 000 00 00">
    </label>

    <label>İkincil Telefon (secondary_phone)
      <input type="tel" name="secondary_phone" maxlength="30" pattern="[\d+\-\s()]{6,30}" value="<?= Helpers::e($c['secondary_phone'] ?? '') ?>">
    </label>

    <label>Faks (fax)
      <input type="tel" name="fax" maxlength="30" pattern="[\d+\-\s()]{6,30}" value="<?= Helpers::e($c['fax'] ?? '') ?>">
    </label>

    <label>Web Sitesi (website)
      <input type="url" name="website" maxlength="2048" value="<?= Helpers::e($c['website'] ?? '') ?>" placeholder="https://">
    </label>
  </fieldset>

  <fieldset>
    <legend>Adres</legend>

    <label>Adres Satırı 1 (address_line1)
      <input type="text" name="address_line1" maxlength="200" value="<?= Helpers::e($c['address_line1'] ?? '') ?>">
    </label>

    <label>Adres Satırı 2 (address_line2)
      <input type="text" name="address_line2" maxlength="200" value="<?= Helpers::e($c['address_line2'] ?? '') ?>">
    </label>

    <label>Şehir (city)
      <input type="text" name="city" maxlength="100" value="<?= Helpers::e($c['city'] ?? '') ?>">
    </label>

    <label>Eyalet/Bölge (state_region)
      <input type="text" name="state_region" maxlength="100" value="<?= Helpers::e($c['state_region'] ?? '') ?>">
    </label>

    <label>Posta Kodu (postal_code)
      <input type="text" name="postal_code" maxlength="20" value="<?= Helpers::e($c['postal_code'] ?? '') ?>">
    </label>

    <label>Ülke Kodu (ISO-2) (country_code)
      <input type="text" name="country_code" maxlength="2" pattern="[A-Za-z]{2}" value="<?= Helpers::e($c['country_code'] ?? '') ?>" placeholder="TR">
    </label>

    <div style="display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:.75rem;">
      <label>Enlem (latitude)
        <input type="number" name="latitude" step="0.000001" min="-90" max="90" value="<?= Helpers::e((string)($c['latitude'] ?? '')) ?>" placeholder="41.008240">
      </label>
      <label>Boylam (longitude)
        <input type="number" name="longitude" step="0.000001" min="-180" max="180" value="<?= Helpers::e((string)($c['longitude'] ?? '')) ?>" placeholder="28.978359">
      </label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Operasyonel</legend>

    <label>Sektör (industry)
      <input type="text" name="industry" maxlength="120" value="<?= Helpers::e($c['industry'] ?? '') ?>" placeholder="Örn: Yazılım">
    </label>

    <label>Durum (status)
      <?php $status = $c['status'] ?? 'active'; ?>
      <?php $statusOpts = ['active','prospect','lead','suspended','inactive']; ?>
      <select name="status">
        <?php foreach ($statusOpts as $s): ?>
          <option value="<?= Helpers::e($s) ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= Helpers::e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Para Birimi (ISO-4217) (currency_code)
      <input type="text" name="currency_code" maxlength="3" pattern="[A-Za-z]{3}" value="<?= Helpers::e($c['currency_code'] ?? '') ?>" placeholder="TRY">
    </label>

    <label>Zaman Dilimi (timezone)
      <input type="text" name="timezone" maxlength="50" value="<?= Helpers::e($c['timezone'] ?? '') ?>" placeholder="Europe/Istanbul">
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="vat_exempt" value="1" <?= $checked($c['vat_exempt'] ?? false) ? 'checked' : '' ?>>
      KDV Muaf (vat_exempt)
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="e_invoice_enabled" value="1" <?= $checked($c['e_invoice_enabled'] ?? false) ? 'checked' : '' ?>>
      e-Fatura Aktif (e_invoice_enabled)
    </label>

    <label>Logo URL (logo_url)
      <input type="url" name="logo_url" maxlength="2048" value="<?= Helpers::e($c['logo_url'] ?? '') ?>" placeholder="https://.../logo.png">
    </label>

    <label>Notlar (notes)
      <textarea name="notes" rows="3"><?= Helpers::e($c['notes'] ?? '') ?></textarea>
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="is_active" value="1" <?= $checked($c['is_active'] ?? true) ? 'checked' : '' ?>>
      Aktif (is_active)
    </label>
  </fieldset>

  <div class="actions" style="margin-top:1rem; display:flex; gap:.75rem;">
    <button type="submit">Güncelle</button>
    <a href="/firms">İptal</a>
  </div>
</form>

<style>
  form { display: grid; gap: 1rem; }
  fieldset { border: 1px solid #ddd; padding: 1rem; border-radius: .5rem; }
  fieldset > label { display: grid; gap: .25rem; margin-bottom: .5rem; }
  input, select, textarea { padding: .5rem; border: 1px solid #ccc; border-radius: .375rem; }
  .actions button { padding: .5rem 1rem; }
  @media (max-width: 640px) {
    .grid-2 { grid-template-columns: 1fr !important; }
  }
</style>