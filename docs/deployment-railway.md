# SmartRH Maroc Railway Deployment

This guide prepares a safe online demo deployment on Railway. It uses Docker, PostgreSQL, database-backed sessions/cache/queues, and a Railway Volume for private generated files.

## Deployment Strategy

- Build strategy: Dockerfile
- Runtime: PHP 8.3 Apache serving `public/`
- Database: Railway PostgreSQL
- Private files: Railway Volume mounted at `/var/www/html/storage/app/private`
- Health check: `/up`
- Queue: database worker service, or temporary `QUEUE_CONNECTION=sync` for a simple demo

Never run `php artisan migrate:fresh` in production.

## 1. Create and Push the Repository

1. Create a GitHub repository.
2. Add this Laravel project to Git.
3. Do not commit `.env`, `database/*.sqlite`, `vendor/`, `node_modules/`, logs, or generated private files.
4. Push to GitHub.

## 2. Create Railway Services

1. Create a new Railway project.
2. Add a PostgreSQL service.
3. Add an application service from the GitHub repository.
4. Railway should detect `railway.json` and build with the Dockerfile.

## 3. Add a Railway Volume

Create a Railway Volume for persistent private files.

Mount path:

```text
/var/www/html/storage/app/private
```

Set:

```text
PRIVATE_STORAGE_PATH=/var/www/html/storage/app/private
PRIVATE_FILESYSTEM_DISK=private
FILESYSTEM_DISK=private
```

Generated payslips, contracts, HR documents, invoices, uploads, and signed PDFs stay private and are downloaded only through authorized Laravel routes.

## 4. Required Environment Variables

Use Railway variable references where possible.

```text
APP_NAME="SmartRH Maroc"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://YOUR-RAILWAY-DOMAIN.up.railway.app
FORCE_HTTPS=true
TRUSTED_PROXIES=*

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=private
PRIVATE_FILESYSTEM_DISK=private
PRIVATE_STORAGE_PATH=/var/www/html/storage/app/private

MAIL_MAILER=log
MAIL_FROM_ADDRESS=demo@smartrh.example
MAIL_FROM_NAME="${APP_NAME}"

SMARTRH_PRODUCT_NAME="SmartRH Maroc"
SMARTRH_SUPPORT_EMAIL=demo@smartrh.example
SMARTRH_TIMEZONE=Africa/Casablanca
SMARTRH_DEMO_MODE_ENABLED=true

SEED_DEMO_DATA=false
```

For a disposable demo only, set `SEED_DEMO_DATA=true` for the first deploy. The startup script runs `smartrh:seed-demo`, which skips if company data already exists. Set it back to `false` after the first successful demo seed.

## 5. Web Service

Railway uses the Docker image entrypoint. No custom command is required for the web service.

The entrypoint:

1. Waits for PostgreSQL.
2. Runs `php artisan migrate --force`.
3. Optionally seeds demo data only when `SEED_DEMO_DATA=true` and company data is empty.
4. Clears and rebuilds Laravel caches.
5. Starts Apache on `0.0.0.0:$PORT`.

Health check path:

```text
/up
```

## 6. Queue Worker Service

Create a second Railway service from the same repository/image.

Command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Use the same environment variables as the web service.

Temporary demo fallback:

```text
QUEUE_CONNECTION=sync
```

Use the fallback only if you do not configure a worker service.

## 7. Scheduler Service

Create a third Railway service from the same repository/image if scheduled tasks are needed.

Command:

```bash
php artisan schedule:work
```

Use the same environment variables as the web service.

## 8. Generate the APP_KEY

Locally:

```bash
php artisan key:generate --show
```

Copy the displayed key into Railway as `APP_KEY`. Do not commit it.

## 9. Demo Data

Demo credentials are only for explicitly seeded demos:

```text
Admin: admin@smartrh.test / password
Employee: amina.employee@smartrh.test / password
```

For a one-time seed after migrations:

```bash
php artisan smartrh:seed-demo --force
```

Do not run:

```bash
php artisan smartrh:reset-demo --force
php artisan migrate:fresh --seed
```

Those are destructive and intended only for disposable local/demo databases.

## 10. Optional Brevo SMTP

Initial Railway demo can use:

```text
MAIL_MAILER=log
```

For Brevo SMTP, configure:

```text
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=YOUR_BREVO_LOGIN
MAIL_PASSWORD=YOUR_BREVO_SMTP_KEY
MAIL_FROM_ADDRESS=verified-sender@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Do not commit SMTP credentials.

## 11. Deployment Validation

After deploy, run:

```bash
php artisan smartrh:deployment-check
```

Then test:

- `/`
- `/pricing`
- `/request-demo`
- `/up`
- `/admin/login`
- `/employee/login`
- Payroll generation
- Payslip PDF download
- Invoice PDF download
- HR document PDF download
- Employee XLSX/CSV exports
- Support tickets

## 12. Before Real Production

- Disable demo credentials and demo mode.
- Set `SEED_DEMO_DATA=false`.
- Configure a real support email and mail provider.
- Validate Moroccan payroll/legal settings with a qualified accountant or legal expert.
- Confirm private downloads remain authorized.
- Confirm `APP_DEBUG=false`.
- Confirm backups for PostgreSQL and the Railway Volume.
