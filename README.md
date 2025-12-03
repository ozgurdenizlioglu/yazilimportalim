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
