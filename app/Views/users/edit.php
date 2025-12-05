<?php use App\Core\Helpers;

?>
<h1><?= Helpers::e($title ?? 'Kullanıcıyı Düzenle') ?></h1>

<?php
// Yardımcı: tarih değerini Y-m-d formatına çevir (timestamptz/DateTime string'lerinden gelebilir)
$fmtDate = function ($v) {
    if (!$v) {
        return '';
    }
    // Eğer sadece tarih ise
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v)) {
        return $v;
    }
    // Aksi halde DateTime ile dener
    try {
        $dt = new DateTime((string)$v);
        return $dt->format('Y-m-d');
    } catch (\Throwable $e) {
        return '';
    }
};

// Yardımcı: bool/truey flag için
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

$u = $user ?? [];

$baseUrl = $_ENV['BASE_URL'] ?? getenv('BASE_URL') ?? (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$baseUrl = rtrim($baseUrl, '/');

?>

<form method="post" action="/users/update" novalidate style="max-width: 900px;">
  <input type="hidden" name="id" value="<?= Helpers::e((string)($u['id'] ?? '')) ?>">

  <fieldset>
    <legend>Temel Bilgiler</legend>

    <label>Ad (first_name) *
      <input type="text" name="first_name" required maxlength="100" value="<?= Helpers::e($u['first_name'] ?? '') ?>">
    </label>

    <label>İkinci Ad (middle_name)
      <input type="text" name="middle_name" maxlength="100" value="<?= Helpers::e($u['middle_name'] ?? '') ?>">
    </label>

    <label>Soyad (last_name) *
      <input type="text" name="last_name" required maxlength="100" value="<?= Helpers::e($u['last_name'] ?? '') ?>">
    </label>

    <label>E-posta *
      <input type="email" name="email" required maxlength="255" value="<?= Helpers::e($u['email'] ?? '') ?>">
    </label>

    <label>Cinsiyet
      <select name="gender">
        <?php
          $gender = $u['gender'] ?? '';
          $opts = ['' => '— Seçiniz —', 'male' => 'Erkek', 'female' => 'Kadın', 'nonbinary' => 'Non-binary', 'unknown' => 'Bilinmiyor'];
        ?>
        <?php foreach ($opts as $val => $label): ?>
          <option value="<?= Helpers::e($val) ?>" <?= ($gender === $val) ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Doğum Tarihi
      <input type="date" name="birth_date" value="<?= Helpers::e($fmtDate($u['birth_date'] ?? null)) ?>">
    </label>

    <label>Vefat Tarihi
      <input type="date" name="death_date" value="<?= Helpers::e($fmtDate($u['death_date'] ?? null)) ?>">
    </label>

    <label>Telefon
      <input type="tel" name="phone" maxlength="20" pattern="[\d+\-\s()]{6,20}" value="<?= Helpers::e($u['phone'] ?? '') ?>">
    </label>

    <label>İkincil Telefon
      <input type="tel" name="secondary_phone" maxlength="20" pattern="[\d+\-\s()]{6,20}" value="<?= Helpers::e($u['secondary_phone'] ?? '') ?>">
    </label>

    <label>Medeni Hali
      <select name="marital_status">
        <?php
          $ms = $u['marital_status'] ?? '';
          $msOpts = ['' => '— Seçiniz —', 'single' => 'Bekar', 'married' => 'Evli', 'divorced' => 'Boşanmış', 'widowed' => 'Dul', 'other' => 'Diğer'];
        ?>
        <?php foreach ($msOpts as $val => $label): ?>
          <option value="<?= Helpers::e($val) ?>" <?= ($ms === $val) ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Uyruk Kodu (ISO-2)
      <input type="text" name="nationality_code" maxlength="2" value="<?= Helpers::e($u['nationality_code'] ?? '') ?>" placeholder="TR">
    </label>

    <label>Doğum Yeri
      <input type="text" name="place_of_birth" maxlength="120" value="<?= Helpers::e($u['place_of_birth'] ?? '') ?>">
    </label>

    <label>Zaman Dilimi
      <input type="text" name="timezone" maxlength="50" value="<?= Helpers::e($u['timezone'] ?? '') ?>" placeholder="Europe/Istanbul">
    </label>

    <label>Dil (locale)
      <input type="text" name="language" maxlength="5" value="<?= Helpers::e($u['language'] ?? '') ?>" placeholder="tr veya tr-TR">
    </label>
  </fieldset>

  <fieldset>
    <legend>Kimlik ve Diğer</legend>

    <label>T.C./Ulusal ID (benzersiz)
      <input type="text" name="national_id" maxlength="50" value="<?= Helpers::e($u['national_id'] ?? '') ?>">
    </label>

    <label>Pasaport No (benzersiz)
      <input type="text" name="passport_no" maxlength="30" value="<?= Helpers::e($u['passport_no'] ?? '') ?>">
    </label>

    <label>Fotoğraf URL
      <input type="url" name="photo_url" maxlength="2048" placeholder="https://" value="<?= Helpers::e($u['photo_url'] ?? '') ?>">
    </label>

    <label>Notlar
      <textarea name="notes" rows="3"><?= Helpers::e($u['notes'] ?? '') ?></textarea>
    </label>

    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
      <input type="checkbox" name="is_active" value="1" <?= $checked($u['is_active'] ?? true) ? 'checked' : '' ?>>
      Aktif
    </label>
  </fieldset>

  <fieldset>
    <legend>Adres</legend>

    <label>Adres Satırı 1
      <input type="text" name="address_line1" maxlength="200" value="<?= Helpers::e($u['address_line1'] ?? '') ?>">
    </label>

    <label>Adres Satırı 2
      <input type="text" name="address_line2" maxlength="200" value="<?= Helpers::e($u['address_line2'] ?? '') ?>">
    </label>

    <label>Şehir
      <input type="text" name="city" maxlength="100" value="<?= Helpers::e($u['city'] ?? '') ?>">
    </label>

    <label>Eyalet/Bölge
      <input type="text" name="state_region" maxlength="100" value="<?= Helpers::e($u['state_region'] ?? '') ?>">
    </label>

    <label>Posta Kodu
      <input type="text" name="postal_code" maxlength="20" value="<?= Helpers::e($u['postal_code'] ?? '') ?>">
    </label>

    <label>Ülke Kodu (ISO-2)
      <input type="text" name="country_code" maxlength="2" value="<?= Helpers::e($u['country_code'] ?? '') ?>" placeholder="TR">
    </label>
  </fieldset>

  <fieldset>
    <legend>Firma</legend>
    <label>Firma Seçimi
      <select name="company_id">
        <option value="">— Seçiniz —</option>
        <?php
          $currentCompanyId = (string)($u['company_id'] ?? '');
          foreach (($companies ?? []) as $c):
            $cid = (string)$c['id'];
        ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($currentCompanyId !== '' && $currentCompanyId === $cid) ? 'selected' : '' ?>>
            <?= Helpers::e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </fieldset>

  <div class="actions" style="margin-top:1rem; display:flex; gap:.75rem;">
    <button type="submit">Güncelle</button>
    <a href="/users">İptal</a>
  </div>

  <div style="margin-top:1.5rem;">
    <div style="font-weight:600; margin-bottom:.5rem;">Kullanıcı QR</div>
    <div id="qrcode" style="width:220px;height:220px;border:1px dashed #ddd;display:grid;place-items:center;">
      <span style="color:#888;font-size:12px;">QR yükleniyor...</span>
    </div>
    <div style="margin-top:8px; font-size:13px; color:#666">
      <span id="qr-url"></span>
      <button id="btn-copy" type="button" style="margin-left:8px;">Kopyala</button>
    </div>
    <button id="btn-download" type="button" style="margin-top:10px;">PNG indir</button>
  </div>

  <script src="/js/qrcode.min.js"></script>
  <script>
  (function() {
    const userId = <?= isset($user['id']) ? (int)$user['id'] : 0 ?>;
    if (!userId) {
      document.getElementById('qrcode').innerHTML = '<span style="color:#b00020;font-size:12px;">Önce kullanıcıyı kaydedin (ID yok).</span>';
      return;
    }
    const url = "<?= $baseUrl ?>" + '/scan?uid=' + userId;

    const urlEl = document.getElementById('qr-url');
    urlEl.textContent = url;

    const copyBtn = document.getElementById('btn-copy');
    copyBtn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(url);
        copyBtn.textContent = 'Kopyalandı ✓';
        setTimeout(() => copyBtn.textContent = 'Kopyala', 1500);
      } catch {
        copyBtn.textContent = 'Kopyalanamadı';
        setTimeout(() => copyBtn.textContent = 'Kopyala', 1500);
      }
    });

    if (typeof QRCode === 'undefined') {
      document.getElementById('qrcode').innerHTML = '<span style="color:#b00020;font-size:12px;">qrcode.min.js yüklü değil.</span>';
      return;
    }

    const qrel = document.getElementById('qrcode');
    // Önceki içeriği temizle
    qrel.innerHTML = '';
    const qr = new QRCode(qrel, {
      text: url,
      width: 220,
      height: 220,
      correctLevel: QRCode.CorrectLevel.M
    });

    document.getElementById('btn-download').addEventListener('click', () => {
      const img = qrel.querySelector('img') || qrel.querySelector('canvas');
      if (!img) return;
      let dataUrl;
      if (img.tagName.toLowerCase() === 'img') {
        dataUrl = img.src;
      } else {
        dataUrl = img.toDataURL('image/png');
      }
      const a = document.createElement('a');
      a.href = dataUrl;
      a.download = 'user-' + userId + '-qr.png';
      a.click();
    });
  })();
  </script>
</form>

<style>
  form { display: grid; gap: 1rem; }
  fieldset { border: 1px solid #ddd; padding: 1rem; border-radius: .5rem; }
  fieldset > label { display: grid; gap: .25rem; margin-bottom: .5rem; }
  input, select, textarea { padding: .5rem; border: 1px solid #ccc; border-radius: .375rem; }
  .actions button { padding: .5rem 1rem; }
</style>