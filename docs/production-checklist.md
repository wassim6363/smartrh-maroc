# SmartRH Maroc — Checklist de mise en production

## 1. Configuration de l'environnement

- [ ] `APP_ENV=production` dans `.env`
- [ ] `APP_DEBUG=false` — ne jamais laisser `true` en production
- [ ] `APP_URL` défini avec le domaine réel (ex: `https://smartrh.ma`)
- [ ] `APP_KEY` généré via `php artisan key:generate`
- [ ] Clé unique, jamais partagée ni commitée

## 2. Base de données

- [ ] MySQL 8+ ou MariaDB 10+ en production (SQLite déconseillé)
- [ ] Configurer `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- [ ] Utiliser un utilisateur dédié (pas `root`)
- [ ] Activer les sauvegardes automatiques
- [ ] `php artisan migrate --force` après chaque déploiement

## 3. Mail (SMTP)

- [ ] Configurer un service SMTP réel (SendGrid, Mailgun, Brevo, Postmark, etc.)
- [ ] Variables : `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
- [ ] `MAIL_FROM_ADDRESS` = adresse d'expédition (ex: `contact@smartrh.ma`)
- [ ] `MAIL_FROM_NAME` = "SmartRH Maroc"
- [ ] Tester avec `php artisan tinker` : `Mail::raw('test', fn($m) => $m->to('admin@smartrh.ma')->subject('SMTP test'));`
- [ ] Configurer `SMARTRH_SUPPORT_EMAIL` dans `.env` pour les notifications admin

## 4. File d'attente (Queue)

- [ ] `QUEUE_CONNECTION=database` recommandé (déjà configuré)
- [ ] Créer les jobs table : déjà fait via migration `create_jobs_table`
- [ ] Démarrer le worker : `php artisan queue:work --daemon` (voir superviseur)
- [ ] En production, utiliser Supervisor pour le worker :
    ```ini
    [program:smartrh-queue]
    process_name=%(program_name)s_%(process_num)02d
    command=php /chemin/vers/smartrh/artisan queue:work --sleep=3 --tries=3
    autostart=true
    autorestart=true
    numprocs=2
    user=www-data
    ```

## 5. Planificateur (Scheduler Cron)

Ajouter dans crontab (une seule ligne) :

```cron
* * * * * cd /chemin/vers/smartrh && php artisan schedule:run >> /dev/null 2>&1
```

Vérifier avec : `php artisan schedule:list`

## 6. Stockage privé

- [ ] `storage/app/private/` doit exister et être accessible en écriture
- [ ] Chemins importants :
    - `storage/app/private/payslips/` — Bulletins de paie PDF
    - `storage/app/private/contracts/` — Contrats PDF
    - `storage/app/private/documents/` — Documents générés
    - `storage/app/private/invoices/` — Factures PDF
- [ ] Ne jamais exposer `storage/app/private/` via le web

## 7. Permissions fichiers

- [ ] `storage/` : écriture pour l'utilisateur du serveur web (www-data)
- [ ] `bootstrap/cache/` : écriture pour le serveur web
- [ ] `public/` : lecture seule pour le web
- [ ] `.env` : 600 (lecture seule pour le propriétaire)
- [ ] Pas de fichiers inutiles dans `public/`

```bash
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

## 8. SSL / HTTPS

- [ ] Certificat SSL valide (Let's Encrypt, Cloudflare, etc.)
- [ ] Forcer HTTPS dans `AppServiceProvider` ou via le serveur web
- [ ] Ajouter dans `AppServiceProvider::boot()` :
    ```php
    if (! app()->environment('local')) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
    ```
- [ ] Activer HSTS, redirection HTTP → HTTPS

## 9. Cache de production

Exécuter après chaque déploiement :

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Ne jamais exécuter `optimize:clear` en production sans prévoir un temps d'arrêt.

## 10. Migration et seed

- [ ] `php artisan migrate --force` pour appliquer les migrations
- [ ] Ne pas exécuter `migrate:fresh` en production (perte de données)
- [ ] Les seeds sont réservés au développement (PlanSeeder, etc.)
- [ ] Prévoir un script de déploiement automatisé

## 11. Sauvegardes (Backup)

- [ ] Base de données : dump SQL quotidien
- [ ] Fichiers : `storage/app/private/` inclus
- [ ] Rétention : 7 jours (journalier), 4 semaines (hebdomadaire), 6 mois (mensuel)
- [ ] Script exemple dans `docs/backup-strategy.md`
- [ ] Tester la restauration au moins une fois par mois

## 12. Sécurité

- [ ] `APP_DEBUG=false`
- [ ] Password par défaut changé pour tous les comptes admin
- [ ] Désactiver l'inscription publique si non utilisée
- [ ] Rate limiting sur les routes sensibles (login, API)
- [ ] Vérifier que les rôles Spatie sont corrects :
    - Super Admin, Company Owner, RH Manager, Payroll Manager, Employee, etc.
- [ ] Vérifier que `User::canAccessPanel()` refuse les employés
- [ ] Audit logs activés et vérifiés
- [ ] Headers de sécurité (CSP, X-Frame-Options, X-Content-Type-Options)

## 13. Vérification finale

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan smartrh:health-check
```

Le health-check doit afficher uniquement des `[OK]` ou des `[WARN]` acceptables.

## 14. Contacts

- Support email : configuré dans `SMARTRH_SUPPORT_EMAIL`
- WhatsApp business : configuré dans `SMARTRH_WHATSAPP_NUMBER`
- Site web : défini dans `APP_URL`

---

**Rappel légal :** Les paramètres de paie, modèles de contrats et documents générés doivent être vérifiés par un expert-comptable marocain, juriste ou professionnel compétent avant utilisation officielle.
