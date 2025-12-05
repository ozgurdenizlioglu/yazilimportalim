<?php
//\myapp\app\Views\layouts\main.php
use App\Core\Helpers;
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : 'App' ?></title>
  <style>
    :root { color-scheme: light dark; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; }
    header, footer { padding: 12px 16px; background: #f5f5f5; }
    main { padding: 16px; max-width: 900px; margin: 0 auto; }
    a { color: #0b5; text-decoration: none; }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 8px 10px; border-bottom: 1px solid #ccc; text-align: left; }
    form { display: grid; gap: 8px; max-width: 420px; }
    input { padding: 8px; }
    .container { max-width: 900px; margin: 0 auto; }
    .actions { display: flex; gap: 8px; align-items: center; }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <strong><a href="/">My MVC</a></strong>
      <span style="margin-left:12px"><a href="/users">Kullanıcılar</a></span>
    </div>
  </header>
  <main class="container">
    <?= $content ?? '' ?>
  </main>
  <footer>
    <div class="container">
      <small>&copy; <?= date('Y') ?> My MVC</small>
    </div>
  </footer>
</body>
</html>
