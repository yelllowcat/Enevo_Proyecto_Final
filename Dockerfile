FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
    && docker-php-ext-install mysqli pdo_mysql mbstring \
    && a2enmod rewrite headers setenvif expires deflate \
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
    '<IfModule mod_expires.c>' \
    '    ExpiresActive On' \
    '    ExpiresByType image/webp "access plus 30 days"' \
    '    ExpiresByType image/jpeg "access plus 30 days"' \
    '    ExpiresByType image/png "access plus 30 days"' \
    '    ExpiresByType image/gif "access plus 30 days"' \
    '    ExpiresByType image/svg+xml "access plus 30 days"' \
    '    ExpiresByType image/x-icon "access plus 30 days"' \
    '    ExpiresByType text/css "access plus 1 hour"' \
    '    ExpiresByType application/javascript "access plus 1 hour"' \
    '</IfModule>' \
    '<IfModule mod_headers.c>' \
    '    <FilesMatch "\.(webp|jpg|jpeg|png|gif|svg|ico)$">' \
    '        Header set Cache-Control "public, max-age=2592000"' \
    '    </FilesMatch>' \
    '    <FilesMatch "\.(css|js)$">' \
    '        Header set Cache-Control "public, max-age=3600, must-revalidate"' \
    '    </FilesMatch>' \
    '</IfModule>' \
    '<IfModule mod_deflate.c>' \
    '    AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript application/json image/svg+xml' \
    '</IfModule>' \
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
