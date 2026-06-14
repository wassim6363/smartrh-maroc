# SmartRH Maroc — Guide de déploiement Laravel

## Prérequis

- PHP 8.2+
- Composer 2.x
- MySQL 8+ ou MariaDB 10+
- Node.js 18+ (optionnel, pour les assets)
- Serveur web : Apache / Nginx / Laragon
- Extension PHP requises : BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, DOM, Zip

---

## 1. Déploiement sur VPS (Nginx / Apache)

### 1.1 Cloner le projet

```bash
cd /var/www
git clone <url-du-repo> smartrh
cd smartrh
```

### 1.2 Installer les dépendances

```bash
composer install --no-dev --optimize-autoloader
```

### 1.3 Configuration

```bash
cp .env.example .env
nano .env   # Configurer DB, APP_URL, mail, etc.
php artisan key:generate
```

### 1.4 Assets (si personnalisés)

```bash
npm install
npm run build
```

Le projet utilise Vite. Les assets sont compilés dans `public/build/`.

### 1.5 Migrations

```bash
php artisan migrate --force
```

Ne pas exécuter `migrate:fresh` en production.

### 1.6 Stockage

```bash
php artisan storage:link   # si liens symboliques publics nécessaires
```

Vérifier que `storage/app/private/` existe et est accessible en écriture.

### 1.7 Cache de production

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 1.8 Permissions

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 1.9 Configuration Nginx (exemple)

```nginx
server {
    listen 443 ssl;
    server_name smartrh.ma;

    root /var/www/smartrh/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/smartrh.ma/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/smartrh.ma/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.env { deny all; }
    location ~ /storage/app/private/ { deny all; }
}
```

### 1.10 Worker queue (Supervisor)

```bash
sudo apt install supervisor
sudo nano /etc/supervisor/conf.d/smartrh-queue.conf
```

```ini
[program:smartrh-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/smartrh/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/smartrh/storage/logs/queue.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start smartrh-queue:*
```

### 1.11 Scheduler Cron

```bash
crontab -e
```

Ajouter :

```cron
* * * * * cd /var/www/smartrh && php artisan schedule:run >> /dev/null 2>&1
```

---

## 2. Déploiement sur Laragon (Windows / Dev avancé)

### 2.1 Cloner le projet

Placer le projet dans `C:\laragon\www\smartrh-maroc`

### 2.2 Installer les dépendances

```bash
composer install
```

### 2.3 Configuration

Copier `.env.example` vers `.env` et ajuster.

```bash
php artisan key:generate
```

### 2.4 Base de données

Via Laragon : créer une base MySQL "smartrh" ou utiliser SQLite.

### 2.5 Migrations + Seed

```bash
php artisan migrate:fresh --seed
```

### 2.6 Lancer le serveur

```bash
php artisan serve
```

Ou utiliser l'interface Laragon > Démarrer.

### 2.7 Queue (optionnel en dev)

```bash
php artisan queue:listen
```

---

## 3. Script de déploiement automatisé (VPS)

Créer `deploy.sh` :

```bash
#!/bin/bash
set -e

echo "=== Déploiement SmartRH Maroc ==="

cd /var/www/smartrh

git pull origin main

composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force

php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

sudo supervisorctl restart smartrh-queue:*

php artisan smartrh:health-check

echo "=== Déploiement terminé ==="
```

```bash
chmod +x deploy.sh
```

---

## 4. Commandes utiles après déploiement

```bash
php artisan optimize              # Cache tout (config, route, view, event)
php artisan optimize:clear        # Efface tous les caches (dév. uniquement)
php artisan config:cache          # Cache la config
php artisan route:cache           # Cache les routes
php artisan view:cache            # Cache les vues Blade
php artisan event:cache           # Cache les events
php artisan migrate --force       # Migrations
php artisan queue:work            # Worker (via Supervisor de préférence)
php artisan schedule:run          # Planificateur (via cron)
php artisan smartrh:health-check  # Vérification SmartRH
php artisan smartrh:create-demo-tenant email@example.com  # Créer un client demo
```

---

**Rappel :** Ne jamais exécuter `php artisan optimize:clear` ou `php artisan migrate:fresh` en production sans temps d'arrêt.
