# SmartRH Maroc — Stratégie de sauvegarde

## 1. Ce qui doit être sauvegardé

### 1.1 Base de données (SQL)

Contient toutes les données métier :
- Sociétés, employés, contrats
- Bulletins de paie, éléments de paie
- Abonnements, factures, paiements
- Demandes de démo, leads
- Utilisateurs, rôles, permissions
- Audit logs

### 1.2 Fichiers (storage/app/private)

- **Bulletins de paie PDF** : `storage/app/private/payslips/`
- **Contrats PDF** : `storage/app/private/contracts/`
- **Documents générés** : `storage/app/private/documents/`
- **Factures PDF** : `storage/app/private/invoices/`
- **Autres fichiers uploadés** (CIN, certificats, etc.) : `storage/app/private/`

---

## 2. Script de sauvegarde

Créer `/usr/local/bin/backup-smartrh.sh` :

```bash
#!/bin/bash
set -e

BACKUP_DIR="/backups/smartrh"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
RETENTION_DAYS=7
RETENTION_WEEKS=4
RETENTION_MONTHS=6

mkdir -p "$BACKUP_DIR/daily"
mkdir -p "$BACKUP_DIR/weekly"
mkdir -p "$BACKUP_DIR/monthly"

DB_NAME="smartrh"
DB_USER="smartrh_user"
DB_PASS="your_db_password"

# === 1. Base de données ===
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    --single-transaction --routines --triggers --events \
    | gzip > "$BACKUP_DIR/daily/smartrh_db_$DATE.sql.gz"

# === 2. Fichiers (storage) ===
tar czf "$BACKUP_DIR/daily/smartrh_storage_$DATE.tar.gz" \
    -C /var/www/smartrh storage/app/private/

# === 3. Fichier .env ===
cp /var/www/smartrh/.env "$BACKUP_DIR/daily/env_$DATE.txt"

# === 4. Nettoyage rétention ===
find "$BACKUP_DIR/daily" -name "*.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR/weekly" -name "*.gz" -mtime +$((RETENTION_WEEKS*7)) -delete
find "$BACKUP_DIR/monthly" -name "*.gz" -mtime +$((RETENTION_MONTHS*30)) -delete

# === 5. Rotation hebdo / mensuel ===
if [ "$(date +%u)" = "1" ]; then  # Lundi
    cp "$BACKUP_DIR/daily/smartrh_db_$DATE.sql.gz" "$BACKUP_DIR/weekly/"
    cp "$BACKUP_DIR/daily/smartrh_storage_$DATE.tar.gz" "$BACKUP_DIR/weekly/"
fi

if [ "$(date +%d)" = "01" ]; then  # 1er du mois
    cp "$BACKUP_DIR/daily/smartrh_db_$DATE.sql.gz" "$BACKUP_DIR/monthly/"
    cp "$BACKUP_DIR/daily/smartrh_storage_$DATE.tar.gz" "$BACKUP_DIR/monthly/"
fi

echo "[OK] Backup terminé : $DATE"
```

### 2.1 Planification cron

```cron
# Quotidien à 3h du matin
0 3 * * * /usr/local/bin/backup-smartrh.sh >> /var/log/smartrh-backup.log 2>&1
```

---

## 3. Politique de rétention

| Fréquence  | Rétention | Stockage      |
|------------|-----------|---------------|
| Quotidien  | 7 jours   | `daily/`      |
| Hebdomadaire | 4 semaines | `weekly/`   |
| Mensuel    | 6 mois    | `monthly/`    |
| Annuel     | 5 ans     | Archivage froid (S3, Glacier) |

---

## 4. Procédure de restauration

### 4.1 Restauration base de données

```bash
gunzip < /backups/smartrh/daily/smartrh_db_2026-06-11_030000.sql.gz | \
    mysql -u smartrh_user -p smartrh
```

### 4.2 Restauration des fichiers

```bash
tar xzf /backups/smartrh/daily/smartrh_storage_2026-06-11_030000.tar.gz \
    -C /var/www/smartrh/
```

### 4.3 Restauration complète (après crash)

```bash
# 1. Restaurer le code depuis Git
git clone <url> /var/www/smartrh

# 2. Restaurer .env
cp /backups/smartrh/daily/env_*.txt /var/www/smartrh/.env

# 3. Restaurer la base
gunzip < /backups/smartrh/daily/smartrh_db_latest.sql.gz | mysql -u smartrh_user -p smartrh

# 4. Restaurer les fichiers
tar xzf /backups/smartrh/daily/smartrh_storage_latest.tar.gz -C /var/www/smartrh/

# 5. Réinstaller les dépendances
cd /var/www/smartrh
composer install --no-dev --optimize-autoloader

# 6. Cache
php artisan optimize

# 7. Vérification
php artisan smartrh:health-check
```

---

## 5. Vérification des sauvegardes

Exécuter mensuellement :

```bash
# Vérifier que les fichiers ne sont pas corrompus
gzip -t /backups/smartrh/daily/smartrh_db_*.sql.gz
# Vérifier que les archives tar sont valides
tar tzf /backups/smartrh/daily/smartrh_storage_*.tar.gz > /dev/null && echo "OK"
```

Ajouter une alerte si la taille du backup est anormalement basse.

---

## 6. Sauvegarde distante (recommandé)

Copier les backups vers un stockage distant :

```bash
# Exemple avec S3 (AWS CLI)
aws s3 sync /backups/smartrh/ s3://smartrh-backups/ --delete

# Exemple avec rsync (serveur distant)
rsync -avz /backups/smartrh/ user@backup-server:/backups/smartrh/
```

---

## 7. Ce qui n'est PAS sauvegardé

- Les dépendances Composer (restaurées via `composer install`)
- Les dépendances Node (restaurées via `npm install`)
- Les fichiers temporaires (`storage/framework/`)
- Les logs (`storage/logs/`)
- Le cache (`bootstrap/cache/`)
