<?php use App\Core\Helpers;

?>
<h1><?= Helpers::e($title ?? 'Firma Ekle') ?></h1>

<form method="post" action="/firms/store" novalidate style="max-width: 1000px;">
  <fieldset>
    <legend>Kimlik Bilgileri</legend>

    <label>Firma Adı (name) *
      <input type="text" name="name" required maxlength="200" placeholder="Örn: ABC Teknoloji A.Ş.">
    </label>

    <label>Kısa Ad (short_name)
      <input type="text" name="short_name" maxlength="100" placeholder="Örn: ABC">
    </label>

    <label>Hukuki Tip (legal_type)
      <select name="legal_type">
        <option value="">— Seçiniz —</option>
        <option value="limited">Limited</option>
        <option value="anonim">Anonim</option>
        <option value="sole_prop">Şahıs</option>
        <option value="ngo">Dernek/Vakıf</option>
        <option value="other">Diğer</option>
      </select>
    </label>

    <label>Ticaret Sicil No (registration_no)
      <input type="text" name="registration_no" maxlength="100">
    </label>

    <label>MERSİS No (mersis_no)
      <input type="text" name="mersis_no" maxlength="50">
    </label>

    <label>Vergi Dairesi (tax_office)
      <input type="text" name="tax_office" maxlength="120">
    </label>

    <label>Vergi No (tax_number)
      <input type="text" name="tax_number" maxlength="50">
    </label>
  </fieldset>

  <fieldset>
    <legend>İletişim</legend>

    <label>E-posta (email)
      <input type="email" name="email" maxlength="255" placeholder="info@firma.com">
    </label>

    <label>Telefon (phone)
      <input type="tel" name="phone" maxlength="30" pattern="[\d+\-\s()]{6,30}" placeholder="+90 212 000 00 00">
    </label>

    <label>İkincil Telefon (secondary_phone)
      <input type="tel" name="secondary_phone" maxlength="30" pattern="[\d+\-\s()]{6,30}">
    </label>

    <label>Faks (fax)
      <input type="tel" name="fax" maxlength="30" pattern="[\d+\-\s()]{6,30}">
    </label>

    <label>Web Sitesi (website)
      <input type="url" name="website" maxlength="2048" placeholder="https://">
    </label>
  </fieldset>

  <fieldset>
    <legend>Adres</legend>

    <label>Adres Satırı 1 (address_line1)
      <input type="text" name="address_line1" maxlength="200">
    </label>

    <label>Adres Satırı 2 (address_line2)
      <input type="text" name="address_line2" maxlength="200">
    </label>

    <label>Şehir (city)
      <input type="text" name="city" maxlength="100">
    </label>

    <label>Eyalet/Bölge (state_region)
      <input type="text" name="state_region" maxlength="100">
    </label>

    <label>Posta Kodu (postal_code)
      <input type="text" name="postal_code" maxlength="20">
    </label>

    <label>Ülke Kodu (ISO-2) (country_code)
      <input type="text" name="country_code" maxlength="2" placeholder="TR" pattern="[A-Za-z]{2}">
    </label>

    <div style="display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:.75rem;">
      <label>Enlem (latitude)
        <input type="number" name="latitude" step="0.000001" min="-90" max="90" placeholder="41.008240">
      </label>
      <label>Boylam (longitude)
        <input type="number" name="longitude" step="0.000001" min="-180" max="180" placeholder="28.978359">
      </label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Operasyonel</legend>

    <label>Sektör (industry)
      <input type="text" name="industry" maxlength="120" placeholder="Örn: Yazılım">
    </label>

    <label>Durum (status)
      <select name="status">
        <option value="active" selected>active</option>
        <option value="prospect">prospect</option>
        <option value="lead">lead</option>
        <option value="suspended">suspended</option>
        <option value="inactive">inactive</option>
      </select>
    </label>

    <label>Para Birimi (ISO-4217) (currency_code)
      <input type="text" name="currency_code" maxlength="3" placeholder="TRY" pattern="[A-Za-z]{3}">
    </label>

    <label>Zaman Dilimi (timezone)
      <input type="text" name="timezone" maxlength="50" placeholder="Europe/Istanbul">
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="vat_exempt" value="1">
      KDV Muaf (vat_exempt)
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="e_invoice_enabled" value="1">
      e-Fatura Aktif (e_invoice_enabled)
    </label>

    <label>Logo URL (logo_url)
      <input type="url" name="logo_url" maxlength="2048" placeholder="https://.../logo.png">
    </label>

    <label>Notlar (notes)
      <textarea name="notes" rows="3"></textarea>
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="is_active" value="1" checked>
      Aktif (is_active)
    </label>
  </fieldset>

  <div class="actions" style="margin-top:1rem; display:flex; gap:.75rem;">
    <button type="submit">Kaydet</button>
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