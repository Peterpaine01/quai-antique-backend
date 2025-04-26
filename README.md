# 🚀 Projet Symfony - Quai Antique

[![Symfony](https://img.shields.io/badge/Symfony-6.x-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## 📦 Prérequis

- PHP >= 8.1
- Composer
- Symfony CLI
- MySQL ou MariaDB
- Un serveur local (Symfony Server, MAMP, Docker, etc.)

---

## 🔥 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/Peterpaine01/quai-antique-backend
cd quai-antique-backend
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

Copier le fichier .env en .env.local :

```bash
cp .env .env.local
```

Modifier la variable DATABASE_URL dans .env.local :

```bash
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 5. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 6. Charger les fixtures (optionnel)

```bash
php bin/console doctrine:fixtures:load
```

### 7. Lancer le serveur local

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec PHP directement :

```bash
php -S 127.0.0.1:8000 -t public
```

Accéder ensuite au projet ici 👉 http://127.0.0.1:8000
