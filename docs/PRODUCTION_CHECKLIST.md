# SmartRH Maroc - Checklist production

SmartRH Maroc est pret pour demo et ventes initiales. Avant production, valider les regles de paie avec un expert-comptable marocain.

## Hebergement

- VPS recommande pour production.
- PHP compatible Laravel 12.
- Composer install avec `--no-dev`.
- Build front si assets customises: `npm install` puis `npm run build`.
- Base de donnees MySQL/MariaDB ou PostgreSQL configuree dans `.env`.
- Domaine, SSL et redirections HTTPS.
- Cloudflare Tunnel uniquement pour demo temporaire, pas pour production.

## Configuration

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` fort et unique.
- SMTP configure.
- `QUEUE_CONNECTION` adapte et worker actif.
- Scheduler Laravel actif.
- Permissions d'ecriture sur `storage` et `bootstrap/cache`.
- Stockage prive verifie pour les PDFs.
- Sauvegardes base de donnees et fichiers.
- Mot de passe admin fort.

## Verification SmartRH

- `php artisan optimize:clear`
- `php artisan migrate --force`
- `php artisan smartrh:health-check`
- Vérifier les paramètres légaux actifs.
- Verifier les baremes IR actifs.
- Tester generation et telechargement de PDF.
- Tester isolation entre societes.
- Tester portail salarie avec un compte Employee.
