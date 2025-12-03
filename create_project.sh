#!/usr/bin/env bash
set -euo pipefail

# Vars
ROOT="$(pwd)"
APP_NAME="mvc-mini"
PHP_MIN="8.2"

echo "==> Proje iskeleti oluşturuluyor: $ROOT"

# Ön kontroller
if ! command -v php >/dev/null 2>&1; then
  echo "Hata: PHP yüklü değil."; exit 1
fi
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
# Basit kontrol (min 8.2)
if [ "$(printf '%s\n' "$PHP_MIN" "$PHP_VER" | sort -V | head -n1)" != "$PHP_MIN" ]; then
  echo "Uyarı: PHP $PHP_MIN+ önerilir. Mevcut: $PHP_VER"
fi
if ! command -v composer >/dev/null 2>&1; then
  echo "Hata: Composer yüklü değil. https://getcomposer.org"; exit 1
fi

# Dizinler
mkdir -p public app/Core app/Controllers app/Models app/Views/layouts app/Views/home app/Views/users vendor database/migrations

# composer.json
cat > composer.json <<'JSON'
{
  "name": "yourname/mvc-mini",
  "type": "project",
  "require": {
    "vlucas/phpdotenv": "^5.6"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  }
}
JSON

# .env
cat > .env <<'ENV'
APP_ENV=dev
APP_DEBUG=true
APP_URL=http://localhost:8000

# PostgreSQL
DB_DSN=pgsql:host=127.0.0.1;port=5432;dbname=myapp
DB_USER=myuser
DB_PASS=secret
ENV

# README.md
cat > README.md <<'MD'
# MVC Mini (PHP 8.2 + Apache + PostgreSQL)

- DocumentRoot: project-root/public
- Geliştirme: php -S localhost:8000 -t public
- Ortam değişkenleri: .env

Kurulum:
1) .env düzenleyin (DB_DSN/DB_USER/DB_PASS)
2) PostgreSQL migration:
   psql "host=127.0.0.1 port=5432 dbname=myapp user=myuser password=secret" -f database/migrations/001_create_users.sql
3) Çalıştırma:
   php -S localhost:8000 -t public
   veya Apache VirtualHost ile public dizinini kök yapın.
MD

# app/config.php
cat > app/config.php <<'PHP'
<?php
declare(strict_types=1);

return [
    'app_env'  => $_ENV['APP_ENV'] ?? 'prod',
    'debug'    => (($_ENV['APP_DEBUG'] ?? 'false') === 'true'),
    'base_url' => rtrim($_ENV['APP_URL'] ?? '', '/'),
];
PHP

# public/.htaccess
cat > public/.htaccess <<'HTA'
RewriteEngine On

# Mevcut dosya/dizin varsa doğrudan sun
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Diğer her şeyi front controller'a yönlendir
RewriteRule ^ index.php [QSA,L]
HTA

# public/index.php
cat > public/index.php <<'PHP'
<?php
declare(strict_types=1);

use App\Core\Router;

require __DIR__ . '/../vendor/autoload.php';

// .env yükle
$root = dirname(__DIR__);
if (file_exists($root . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

// Hata gösterimi
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : 0);

// Config
$config = require __DIR__ . '/../app/config.php';

// Router
$router = new Router();

// Rotalar
$router->get('/', [App\Controllers\HomeController::class, 'index']);

$router->get('/users', [App\Controllers\UserController::class, 'index']);
$router->get('/users/create', [App\Controllers\UserController::class, 'create']);
$router->post('/users/store', [App\Controllers\UserController::class, 'store']);
$router->get('/users/edit', [App\Controllers\UserController::class, 'edit']); // ?id=...
$router->post('/users/update', [App\Controllers\UserController::class, 'update']);
$router->post('/users/delete', [App\Controllers\UserController::class, 'destroy']);

// İsteği çalıştır
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$router->dispatch($method, $path);
PHP

# app/Core/Router.php
cat > app/Core/Router.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->routes['PATCH'][$path] = $handler;
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                http_response_code(500);
                echo 'Controller not found';
                return;
            }
            $controller = new $class();
            if (!method_exists($controller, $action)) {
                http_response_code(500);
                echo 'Action not found';
                return;
            }
            $controller->$action();
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        http_response_code(500);
        echo 'Invalid route handler';
    }
}
PHP

# app/Core/Controller.php
cat > app/Core/Controller.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: $view";
            return;
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = __DIR__ . '/../Views/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                http_response_code(500);
                echo "Layout not found: $layout";
                return;
            }
            include $layoutFile;
        } else {
            echo $content;
        }
    }
}
PHP

# app/Core/Database.php
cat > app/Core/Database.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn  = $_ENV['DB_DSN']  ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';

        if ($dsn === '') {
            throw new \RuntimeException('DB_DSN is not set.');
        }

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return self::$pdo;
    }
}
PHP

# app/Core/Helpers.php
cat > app/Core/Helpers.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Core;

final class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $path = '/'): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        $path = '/' . ltrim($path, '/');
        return $base ? ($base . $path) : $path;
    }
}
PHP

# app/Controllers/HomeController.php
cat > app/Controllers/HomeController.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => 'Hoş geldin',
            'message' => 'MVC iskelet çalışıyor!'
        ]);
    }
}
PHP

# app/Controllers/UserController.php
cat > app/Controllers/UserController.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $users = User::all(Database::pdo());
        $this->view('users/index', ['title' => 'Kullanıcılar', 'users' => $users]);
    }

    public function create(): void
    {
        $this->view('users/create', ['title' => 'Kullanıcı Ekle']);
    }

    public function store(): void
    {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo 'Geçersiz veri';
            return;
        }

        User::create(Database::pdo(), $name, $email);
        header('Location: /users');
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = User::find(Database::pdo(), $id);
        if (!$user) {
            http_response_code(404);
            echo 'Kullanıcı bulunamadı';
            return;
        }
        $this->view('users/edit', ['title' => 'Kullanıcıyı Düzenle', 'user' => $user]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo 'Geçersiz veri';
            return;
        }
        User::update(Database::pdo(), $id, $name, $email);
        header('Location: /users');
    }

    public function destroy(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            User::delete(Database::pdo(), $id);
        }
        header('Location: /users');
    }
}
PHP

# app/Models/User.php
cat > app/Models/User.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public static function all(PDO $pdo): array
    {
        return $pdo->query("SELECT id, name, email FROM users ORDER BY id")->fetchAll();
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(PDO $pdo, string $name, string $email): void
    {
        $stmt = $pdo->prepare("INSERT INTO users(name, email) VALUES(:name, :email)");
        $stmt->execute([':name' => $name, ':email' => $email]);
    }

    public static function update(PDO $pdo, int $id, string $name, string $email): void
    {
        $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
        $stmt->execute([':name' => $name, ':email' => $email, ':id' => $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}
PHP

# app/Views/layouts/main.php
cat > app/Views/layouts/main.php <<'PHP'
<?php
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
PHP

# app/Views/home/index.php
cat > app/Views/home/index.php <<'PHP'
<h1><?= htmlspecialchars($title ?? 'Home', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>
<p><a href="/users">Kullanıcılar sayfasına git</a></p>
PHP

# app/Views/users/index.php
cat > app/Views/users/index.php <<'PHP'
<?php use App\Core\Helpers; ?>
<h1><?= Helpers::e($title ?? 'Kullanıcılar') ?></h1>

<p><a href="/users/create">+ Yeni Kullanıcı</a></p>

<?php if (!empty($users)): ?>
<table>
  <thead>
    <tr><th>ID</th><th>Ad</th><th>Email</th><th>İşlemler</th></tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= Helpers::e((string)$u['id']) ?></td>
      <td><?= Helpers::e($u['name']) ?></td>
      <td><?= Helpers::e($u['email']) ?></td>
      <td class="actions">
        <a href="/users/edit?id=<?= Helpers::e((string)$u['id']) ?>">Düzenle</a>
        <form method="post" action="/users/delete" onsubmit="return confirm('Silinsin mi?')">
          <input type="hidden" name="id" value="<?= Helpers::e((string)$u['id']) ?>">
          <button type="submit">Sil</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p>Hiç kullanıcı yok.</p>
<?php endif; ?>
PHP

# app/Views/users/create.php
cat > app/Views/users/create.php <<'PHP'
<?php use App\Core\Helpers; ?>
<h1><?= Helpers::e($title ?? 'Kullanıcı Ekle') ?></h1>
<form method="post" action="/users/store">
  <label>Ad
    <input type="text" name="name" required>
  </label>
  <label>Email
    <input type="email" name="email" required>
  </label>
  <div class="actions">
    <button type="submit">Kaydet</button>
    <a href="/users">İptal</a>
  </div>
</form>
PHP

# app/Views/users/edit.php
cat > app/Views/users/edit.php <<'PHP'
<?php use App\Core\Helpers; ?>
<h1><?= Helpers::e($title ?? 'Kullanıcıyı Düzenle') ?></h1>
<form method="post" action="/users/update">
  <input type="hidden" name="id" value="<?= Helpers::e((string)($user['id'] ?? '')) ?>">
  <label>Ad
    <input type="text" name="name" value="<?= Helpers::e($user['name'] ?? '') ?>" required>
  </label>
  <label>Email
    <input type="email" name="email" value="<?= Helpers::e($user['email'] ?? '') ?>" required>
  </label>
  <div class="actions">
    <button type="submit">Güncelle</button>
    <a href="/users">İptal</a>
  </div>
</form>
PHP

# database/migrations/001_create_users.sql
cat > database/migrations/001_create_users.sql <<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE
);
SQL

echo "==> Composer bağımlılıkları kuruluyor..."
composer install --no-interaction

echo "==> Autoload dump..."
composer dump-autoload -o

echo "==> Kurulum tamamlandı."
echo
echo "Çalıştırma:"
echo "  - Dev sunucu: php -S localhost:8000 -t public"
echo "  - Apache DocumentRoot: $(pwd)/public (AllowOverride All)"
echo
echo "PostgreSQL:"
echo "  - .env düzenleyin ve migration'ı uygulayın:"
echo "    psql \"host=127.0.0.1 port=5432 dbname=myapp user=myuser password=secret\" -f database/migrations/001_create_users.sql"