<div class="container">
  <h1>QR Okuma</h1>

  <?php if (!empty($error)): ?>
    <div style="color:#b00020; border:1px solid #b00020; padding:10px; border-radius:6px; margin:10px 0;">
      Hata: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php else: ?>
    <?php if (!empty($user)): ?>
      <p>Kullanıcı: <strong><?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> (ID: <?= (int)$user['id'] ?>)</p>
      <div id="result" style="margin-top:12px; color:#666">İşlem yapılıyor, lütfen bekleyin...</div>

      <?php if (!empty($token)): ?>
        <script>
        (async () => {
          const el = document.getElementById('result');
          try {
            const res = await fetch('/api/attendance/scan', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ token: <?= json_encode($token) ?> })
            });
            const data = await res.json().catch(()=>null);
            if (res.ok && data && data.ok) {
              el.style.color = '#107e3e';
              el.innerHTML = 'Kaydedildi. ID: ' + (data.attendance_id ?? '-') +
                (data.type ? ' (' + data.type + ')' : '');
            } else {
              el.style.color = '#b00020';
              el.innerHTML = 'Hata: ' + ((data && data.error) || (res.status + ' ' + res.statusText));
            }
          } catch (e) {
            el.style.color = '#b00020';
            el.innerHTML = 'Ağ hatası: ' + (e?.message || e);
          }
        })();
        </script>
      <?php else: ?>
        <div style="color:#b00020; margin-top:8px;">Token üretilemedi.</div>
      <?php endif; ?>

    <?php else: ?>
      <p>Kullanıcı bulunamadı.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>