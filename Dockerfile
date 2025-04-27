FROM php:8.2-cli

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    zlib1g-dev \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install intl zip pdo pdo_mysql

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Vérifier que Symfony CLI est bien installé
RUN symfony -v

# Définir les variables d'environnement
ENV DATABASE_URL=${DATABASE_URL}
ENV CLOUDINARY_URL=${CLOUDINARY_URL}

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . .

# Créer les dossiers nécessaires
RUN mkdir -p var && chown -R www-data:www-data var

# Passer à l'utilisateur www-data
USER www-data

# Installer les dépendances PHP
RUN composer install --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader

# Exposer le port utilisé par le serveur Symfony
EXPOSE 8080

# Commande pour démarrer le serveur Symfony
CMD ["symfony", "server:start", "--no-tls", "--port=8080", "--allow-http", "--ansi"]
