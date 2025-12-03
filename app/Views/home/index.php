<h1><?= htmlspecialchars($title ?? 'Home', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>
<p><a href="/users">Kullanıcılar sayfasına git</a></p>
<p><a href="/firms">Firmalar sayfasına git</a></p>
