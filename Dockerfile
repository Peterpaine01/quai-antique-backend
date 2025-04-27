# Utiliser une image de base PHP avec Composer intégré
FROM php:8.4-fpm

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Exécuter composer install pendant le build
RUN composer install --no-dev --optimize-autoloader

ENV DATABASE_URL=${DATABASE_URL}
ENV CLOUDINARY_URL=${CLOUDINARY_URL}

# Exposer le port
EXPOSE 9000

# Démarrer le serveur PHP-FPM
CMD ["php-fpm"]
