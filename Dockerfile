FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
    && docker-php-ext-install mysqli pdo_mysql mbstring \
    && a2enmod rewrite headers setenvif \
    && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
    'ServerTokens Prod' \
    'ServerSignature Off' \
    'SetEnvIf X-Forwarded-Proto "https" HTTPS=on' \
    '<Directory /var/www/html>' \
    '    DirectoryIndex index.html index.php' \
    '    Options -Indexes +FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    '<Directory /var/www/html/assets/app/config>' \
    '    Require all denied' \
    '</Directory>' \
    > /etc/apache2/conf-available/enevo.conf \
    && a2enconf enevo

RUN printf '%s\n' \
    'display_errors = Off' \
    'log_errors = On' \
    'error_log = /proc/self/fd/2' \
    'date.timezone = America/Mexico_City' \
    > /usr/local/etc/php/conf.d/enevo.ini

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
