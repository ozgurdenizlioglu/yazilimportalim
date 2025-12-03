<?php use App\Core\Helpers;

?>
<h1><?= Helpers::e($title ?? 'Kullanıcı Ekle') ?></h1>

<form method="post" action="/users/store" novalidate style="max-width: 900px;">
  <fieldset>
    <legend>Temel Bilgiler</legend>

    <label>Ad (first_name) *
      <input type="text" name="first_name" required maxlength="100">
    </label>

    <label>İkinci Ad (middle_name)
      <input type="text" name="middle_name" maxlength="100">
    </label>

    <label>Soyad (last_name) *
      <input type="text" name="last_name" required maxlength="100">
    </label>

    <label>E-posta *
      <input type="email" name="email" required maxlength="255">
    </label>

    <label>Cinsiyet
      <select name="gender">
        <option value="">— Seçiniz —</option>
        <option value="male">Erkek</option>
        <option value="female">Kadın</option>
        <option value="nonbinary">Non-binary</option>
        <option value="unknown">Bilinmiyor</option>
      </select>
    </label>

    <label>Doğum Tarihi
      <input type="date" name="birth_date">
    </label>

    <label>Vefat Tarihi
      <input type="date" name="death_date">
    </label>

    <label>Telefon
      <input type="tel" name="phone" maxlength="20" pattern="[\d+\-\s()]{6,20}" placeholder="+90 555 555 55 55">
    </label>

    <label>İkincil Telefon
      <input type="tel" name="secondary_phone" maxlength="20" pattern="[\d+\-\s()]{6,20}">
    </label>

    <label>Medeni Hali
      <select name="marital_status">
        <option value="">— Seçiniz —</option>
        <option value="single">Bekar</option>
        <option value="married">Evli</option>
        <option value="divorced">Boşanmış</option>
        <option value="widowed">Dul</option>
        <option value="other">Diğer</option>
      </select>
    </label>

    <label>Uyruk Kodu (ISO-2)
      <input type="text" name="nationality_code" maxlength="2" placeholder="TR">
    </label>

    <label>Doğum Yeri
      <input type="text" name="place_of_birth" maxlength="120">
    </label>

    <label>Zaman Dilimi
      <input type="text" name="timezone" maxlength="50" placeholder="Europe/Istanbul">
    </label>

    <label>Dil (locale)
      <input type="text" name="language" maxlength="5" placeholder="tr veya tr-TR">
    </label>
  </fieldset>

  <fieldset>
    <legend>Kimlik ve Diğer</legend>
    <label>T.C./Ulusal ID (benzersiz)
      <input type="text" name="national_id" maxlength="50">
    </label>

    <label>Pasaport No (benzersiz)
      <input type="text" name="passport_no" maxlength="30">
    </label>

    <label>Fotoğraf URL
      <input type="url" name="photo_url" maxlength="2048" placeholder="https://">
    </label>

    <label>Notlar
      <textarea name="notes" rows="3"></textarea>
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="is_active" value="1" checked>
      Aktif
    </label>
  </fieldset>

  <fieldset>
    <legend>Adres</legend>

    <label>Adres Satırı 1
      <input type="text" name="address_line1" maxlength="200">
    </label>

    <label>Adres Satırı 2
      <input type="text" name="address_line2" maxlength="200">
    </label>

    <label>Şehir
      <input type="text" name="city" maxlength="100">
    </label>

    <label>Eyalet/Bölge
      <input type="text" name="state_region" maxlength="100">
    </label>

    <label>Posta Kodu
      <input type="text" name="postal_code" maxlength="20">
    </label>

    <label>Ülke Kodu (ISO-2)
      <input type="text" name="country_code" maxlength="2" placeholder="TR">
    </label>
  </fieldset>

  <div class="actions" style="margin-top:1rem; display:flex; gap:.75rem;">
    <button type="submit">Kaydet</button>
    <a href="/users">İptal</a>
  </div>
</form>

<style>
  form { display: grid; gap: 1rem; }
  fieldset { border: 1px solid #ddd; padding: 1rem; border-radius: .5rem; }
  fieldset > label { display: grid; gap: .25rem; margin-bottom: .5rem; }
  input, select, textarea { padding: .5rem; border: 1px solid #ccc; border-radius: .375rem; }
  .actions button { padding: .5rem 1rem; }
</style>