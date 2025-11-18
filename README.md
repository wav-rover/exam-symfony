# TP Note Deveney

API Symfony pour la gestion de hamsters avec authentification JWT.

## Prérequis

- PHP >= 8.2
- Composer
- MySQL (ou autre base de données compatible)
- OpenSSL (pour générer les clés JWT)

## Installation

### 1. Cloner le projet

```bash
git clone <https://github.com/wav-rover/exam-symfony.git>
cd tp-note-deveney
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer les variables d'environnement

Créer un fichier `.env.local` à la racine du projet :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/dbname?charset=utf8"

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

**Remplacez :**
- `user`, `password`, `dbname` par vos identifiants de base de données
- `your_passphrase_here` par une phrase secrète de votre choix

### 4. Générer les clés JWT

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Lors de la génération de la clé privée, vous devrez entrer une passphrase. Utilisez la même que celle définie dans `JWT_PASSPHRASE`.

### 5. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Charger les fixtures

```bash
php bin/console doctrine:fixtures:load
```

## Lancer le serveur

```bash
symfony server:start
```

Ou avec PHP intégré :

L'API sera accessible sur `http://localhost:8000`

## Endpoints

- `POST /api/register` - Inscription d'un utilisateur
- `POST /api/login` - Connexion (retourne un token JWT)
- `GET /api/hamsters` - Liste des hamsters (authentification requise)
- `POST /api/hamsters` - Créer un hamster (authentification requise)

## Utilisation

Après inscription/connexion, inclure le token JWT dans les en-têtes


