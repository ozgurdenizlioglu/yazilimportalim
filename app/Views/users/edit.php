<?php

use App\Core\Helpers;

ob_start();

// Kaynaktan gelen kullanıcı verisini normalize et

$raw = $user ?? [];

$c = is_array($raw) ? $raw : (array)$raw;

// Partial’a parametreler

$action = '/users/update';

$submitLabel = 'Güncelle';

$title = $title ?? 'Kullanıcıyı Düzenle';

$showIdHidden = true;

$backUrl = '/users';

// Firma listesi (controller’dan gelebilir)

$companies = $companies ?? [];

include __DIR__ . '/_form.php';

// QR Kodu bölümü (edit sayfasına özgü)

?>

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

<?php

$baseUrl = $_ENV['BASE_URL'] ?? getenv('BASE_URL') ?? (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

$baseUrl = rtrim($baseUrl, '/');

?>

<script src="/js/qrcode.min.js"></script>

<script>
    (function() {

        const userId = <?= isset($c['id']) ? (int)$c['id'] : 0 ?>;

        if (!userId) {

            const el = document.getElementById('qrcode');

            if (el) el.innerHTML = '<span style="color:#b00020;font-size:12px;">Önce kullanıcıyı kaydedin (ID yok).</span>';

            return;

        }

        const url = "<?= $baseUrl ?>" + '/scan?uid=' + userId;

        const urlEl = document.getElementById('qr-url');

        if (urlEl) urlEl.textContent = url;

        const copyBtn = document.getElementById('btn-copy');

        if (copyBtn) {

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

        }

        if (typeof QRCode === 'undefined') {

            const el = document.getElementById('qrcode');

            if (el) el.innerHTML = '<span style="color:#b00020;font-size:12px;">qrcode.min.js yüklü değil.</span>';

            return;

        }

        const qrel = document.getElementById('qrcode');

        if (!qrel) return;

        qrel.innerHTML = '';

        const qr = new QRCode(qrel, {

            text: url,

            width: 220,

            height: 220,

            correctLevel: QRCode.CorrectLevel.M

        });

        const dlBtn = document.getElementById('btn-download');

        if (dlBtn) {

            dlBtn.addEventListener('click', () => {

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

        }

    })();
</script>

<?php

$content = ob_get_clean();

include __DIR__ . '/../layouts/base.php';
