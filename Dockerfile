FROM php:8.2-apache

# PostgreSQL sürücüleri, GD ve Zip
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libgd-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# rewrite + AllowOverride
RUN a2enmod rewrite && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Increase PHP upload limits (100MB for upload_max_filesize and post_max_size)
RUN printf "upload_max_filesize = 100M\npost_max_size = 100M\nmax_execution_time = 300\n" \
    > /usr/local/etc/php/conf.d/uploads.ini
# DocumentRoot'u public'e ayarla
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf