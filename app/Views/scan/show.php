<div class="container">

<h1>QR Okuma</h1>

<?php if (!empty($error)): ?>
  <div style="color:#b00020; border:1px solid #b00020; padding:10px; border-radius:6px; margin:10px 0;">
  Hata: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php else: ?>
  <?php if (!empty($user)): ?>
  <p>Kullanıcı: <strong><?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> (ID: <?= (int)$user['id'] ?>)</p>

  <?php if (!empty($token)): ?>
    <div id="cameraSection" style="margin:16px 0; border:1px solid #ddd; padding:12px; border-radius:8px;">
      <div style="margin-bottom:8px; color:#555;">
        Lütfen kamerayı açıp yüzünüzün yeni bir fotoğrafını çekin. Fotoğraf çekmeden giriş/çıkış tamamlanmayacaktır.
      </div>

      <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
        <div style="flex:1 1 260px; max-width:420px;">
          <video id="video" autoplay playsinline style="width:100%; background:#000; border-radius:8px;"></video>
          <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
            <button id="btnStartCam" class="btn btn-sm btn-outline-primary" type="button">Kamerayı Aç</button>
            <button id="btnFlipCam" class="btn btn-sm btn-outline-secondary" type="button" disabled>Ön/Arka Kamera</button>
            <button id="btnCapture" class="btn btn-sm btn-primary" type="button" disabled>Fotoğraf Çek</button>
            <button id="btnRetake" class="btn btn-sm btn-outline-secondary" type="button" disabled>Yeniden Çek</button>
          </div>
          <div id="camHint" style="font-size:12px; color:#777; margin-top:6px;">
            İpucu: Aydınlık bir ortamda olun. Yüzünüz kadrajda ve net görünsün.
          </div>
        </div>

        <div style="flex:1 1 220px; max-width:320px;">
          <div style="font-size:12px; color:#666; margin-bottom:6px;">Önizleme</div>
          <canvas id="canvas" width="640" height="480" style="width:100%; background:#f7f7f7; border:1px dashed #bbb; border-radius:8px;"></canvas>
          <div id="photoInfo" style="font-size:12px; color:#888; margin-top:6px;">Henüz fotoğraf çekilmedi.</div>
        </div>
      </div>
    </div>

    <div style="display:flex; gap:10px; align-items:center; margin-top:10px; flex-wrap:wrap;">
      <button id="btnSubmit" class="btn btn-success" type="button" disabled>Fotoğrafı Yükle ve Kaydet</button>
      <div id="result" style="color:#666;">Fotoğraf çekildikten sonra kaydet butonu aktif olacaktır.</div>
    </div>

    <div id="metaInfo" style="margin-top:12px; font-size:12px; color:#555;">
      <div>Konum: <span id="locStatus">Alınmaya çalışılıyor...</span></div>
      <div>Cihaz: <span id="devInfo">Toplanıyor...</span></div>
    </div>

    <script>
      (function(){
        const token = <?= json_encode($token) ?>;

        const elVideo     = document.getElementById('video');
        const elCanvas    = document.getElementById('canvas');
        const elStartCam  = document.getElementById('btnStartCam');
        const elFlipCam   = document.getElementById('btnFlipCam');
        const elCapture   = document.getElementById('btnCapture');
        const elRetake    = document.getElementById('btnRetake');
        const elSubmit    = document.getElementById('btnSubmit');
        const elResult    = document.getElementById('result');
        const elPhotoInfo = document.getElementById('photoInfo');
        const elLocStatus = document.getElementById('locStatus');
        const elDevInfo   = document.getElementById('devInfo');

        let stream = null;
        let hasSnapshot = false;
        let facingMode = 'user'; // ön kamera
        let currentDeviceId = null;

        // Cihaz bilgisi
        const deviceInfo = {
          userAgent: navigator.userAgent || '',
          platform: navigator.platform || '',
          language: navigator.language || '',
          languages: navigator.languages || [],
          vendor: navigator.vendor || ''
        };
        elDevInfo.textContent = `${deviceInfo.platform} | ${deviceInfo.userAgent}`;

        // Konum bilgisi
        const locationData = { status: 'pending', lat: null, lng: null, accuracy: null };
        function getLocation(){
          if (!navigator.geolocation) {
            locationData.status = 'unsupported';
            elLocStatus.textContent = 'Tarayıcı konum desteği yok.';
            return;
          }
          navigator.geolocation.getCurrentPosition(pos=>{
            locationData.status = 'ok';
            locationData.lat = pos.coords.latitude;
            locationData.lng = pos.coords.longitude;
            locationData.accuracy = pos.coords.accuracy;
            elLocStatus.textContent = `Alındı (±${Math.round(locationData.accuracy)} m)`;
          }, err=>{
            locationData.status = 'denied_or_error';
            elLocStatus.textContent = 'Konum alınamadı: ' + (err?.message || err);
          }, {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 0
          });
        }
        getLocation();

        async function startCamera(){
          try {
            const constraints = currentDeviceId
              ? { video: { deviceId: { exact: currentDeviceId } }, audio: false }
              : { video: { facingMode }, audio: false };

            stream = await navigator.mediaDevices.getUserMedia(constraints);
            elVideo.srcObject = stream;

            elCapture.disabled = false;
            elStartCam.disabled = true;
            elFlipCam.disabled = false;

            elResult.style.color = '#666';
            elResult.textContent = 'Kamera açık. Fotoğraf çekebilirsiniz.';
          } catch (err){
            elResult.style.color = '#b00020';
            elResult.textContent = 'Kamera açılamadı: ' + (err?.message || err);
          }
        }

        function stopCamera(){
          if (stream){
            stream.getTracks().forEach(t => t.stop());
            stream = null;
          }
          elStartCam.disabled = false;
          elCapture.disabled = true;
        }

        async function flipCamera(){
          try {
            // mevcut stream’i kapat
            stopCamera();

            // kullanılabilir cihazları al
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoInputs = devices.filter(d => d.kind === 'videoinput');

            if (videoInputs.length < 2) {
              // facingMode toggle dene
              facingMode = (facingMode === 'user') ? 'environment' : 'user';
              currentDeviceId = null;
            } else {
              // Cihazlar arasında sıradaki cihaza geç
              let index = videoInputs.findIndex(d => d.deviceId === currentDeviceId);
              index = (index + 1) % videoInputs.length;
              currentDeviceId = videoInputs[index].deviceId;
            }
            await startCamera();
          } catch (e){
            elResult.style.color = '#b00020';
            elResult.textContent = 'Kamera değiştirilemedi: ' + (e?.message || e);
          }
        }

        function capture(){
          try {
            const w = elVideo.videoWidth || 640;
            const h = elVideo.videoHeight || 480;
            elCanvas.width = w;
            elCanvas.height = h;
            const ctx = elCanvas.getContext('2d');
            ctx.drawImage(elVideo, 0, 0, w, h);

            hasSnapshot = true;
            elSubmit.disabled = false;
            elRetake.disabled = false;
            elPhotoInfo.textContent = `Çekildi: ${w}x${h}`;
            elResult.style.color = '#666';
            elResult.textContent = 'Fotoğraf çekildi. Kaydetmek için butona basın.';
          } catch (e){
            elResult.style.color = '#b00020';
            elResult.textContent = 'Fotoğraf çekilemedi: ' + (e?.message || e);
          }
        }

        function resetSnapshot(){
          const ctx = elCanvas.getContext('2d');
          ctx.clearRect(0, 0, elCanvas.width, elCanvas.height);
          hasSnapshot = false;
          elSubmit.disabled = true;
          elRetake.disabled = true;
          elPhotoInfo.textContent = 'Henüz fotoğraf çekilmedi.';
          elResult.style.color = '#666';
          elResult.textContent = 'Fotoğraf çekildikten sonra kaydet butonu aktif olacaktır.';
        }

        function collectMeta(){
          return {
            device: deviceInfo,
            location: {
              status: locationData.status,
              lat: locationData.lat,
              lng: locationData.lng,
              accuracy: locationData.accuracy
            },
            // Basit saat/mühür
            capturedAt: new Date().toISOString(),
          };
        }

        async function submitWithPhoto(){
          if (!hasSnapshot){
            elResult.style.color = '#b00020';
            elResult.textContent = 'Lütfen önce fotoğraf çekin.';
            return;
          }

          elSubmit.disabled = true;
          elResult.style.color = '#666';
          elResult.textContent = 'Yükleniyor, lütfen bekleyin...';

          try {
            const blob = await new Promise(resolve => elCanvas.toBlob(resolve, 'image/jpeg', 0.9));
            if (!blob) throw new Error('Fotoğraf oluşturulamadı.');

            const meta = collectMeta();

            const form = new FormData();
            form.append('token', token);                // zorunlu
            form.append('image', blob, 'snapshot.jpg'); // zorunlu: yeni çekilen
            form.append('meta', JSON.stringify(meta));  // opsiyonel: cihaz/konum

            const res = await fetch('/api/attendance/scan', {
              method: 'POST',
              body: form
            });

            const data = await res.json().catch(()=>null);

            if (res.ok && data && data.ok) {
              elResult.style.color = '#107e3e';
              elResult.innerHTML = 'Kaydedildi. ID: ' + (data.attendance_id ?? '-') +
                (data.type ? ' (' + data.type + ')' : '');

              // İşlem bitti; kamerayı kapatın
              stopCamera();
            } else {
              elResult.style.color = '#b00020';
              elResult.innerHTML = 'Hata: ' + ((data && data.error) || (res.status + ' ' + res.statusText));
              elSubmit.disabled = false;
            }
          } catch (e){
            elResult.style.color = '#b00020';
            elResult.textContent = 'Ağ/işlem hatası: ' + (e?.message || e);
            elSubmit.disabled = false;
          }
        }

        // Eventler
        elStartCam.addEventListener('click', startCamera);
        elFlipCam.addEventListener('click', flipCamera);
        elCapture.addEventListener('click', capture);
        elRetake.addEventListener('click', resetSnapshot);
        elSubmit.addEventListener('click', submitWithPhoto);

        // Otomatik: destek varsa kamerayı açmayı dene
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia){
          startCamera();
        } else {
          elResult.style.color = '#b00020';
          elResult.textContent = 'Tarayıcı kamera erişimini desteklemiyor.';
        }

        // Sayfadan çıkarken kamerayı kapat
        window.addEventListener('beforeunload', stopCamera);
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