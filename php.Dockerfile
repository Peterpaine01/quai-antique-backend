FROM php:8.3.11-fpm-alpine

WORKDIR /var/www/html

# On installe les extensions pdo et pdo_mysql
RUN docker-php-ext-install pdo pdo_mysql

RUN mkdir -p /var/www/html

# On expose le port 9000 pour PHP-FPM
EXPOSE 9000

# On lance PHP-FPM
CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]