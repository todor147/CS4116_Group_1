# EduCoach — PHP 8.2 + Apache
FROM php:8.2-apache

# PHP extensions required by the app
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

# Apache virtual host (DocumentRoot + AllowOverride for .htaccess)
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Port-aware entrypoint (honours $PORT on hosts like Render)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Application code
COPY . /var/www/html

# Writable runtime directories
RUN mkdir -p /var/www/html/logs /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads

# Sensible production PHP defaults
RUN { \
    echo 'expose_php = Off'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'upload_max_filesize = 8M'; \
    echo 'post_max_size = 10M'; \
    } > /usr/local/etc/php/conf.d/educoach.ini

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
