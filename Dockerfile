# Utiliser une image de base PHP avec Composer intégré
FROM php:latest

# Définir les variables d'environnement
ENV DATABASE_URL=${DATABASE_URL}
ENV CLOUDINARY_URL=${CLOUDINARY_URL}

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    libicu-dev \
    git \
    unzip \
    && docker-php-ext-install intl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && symfony -v  # Vérifie que Symfony est bien installé

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Créer les répertoires nécessaires, y compris 'var'
RUN mkdir -p /var/www/html/var && chown -R www-data:www-data /var/www/html/var

# Définir le répertoire de travail
WORKDIR /var/www/html

# Exécuter Composer pour installer les dépendances
RUN composer install --no-dev --optimize-autoloader

# Démarrer le serveur Symfony
CMD ["symfony", "server:start"]
