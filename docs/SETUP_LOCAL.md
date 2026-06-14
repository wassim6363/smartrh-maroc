# Local Setup

Requirements: PHP 8.2+, Composer, Laravel 12, and a configured database.

Run:

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan serve
```

Demo accounts:

- admin@smartrh.test / password
- owner@smartrh.test / password
- payroll@smartrh.test / password
- rh@smartrh.test / password
- amina.employee@smartrh.test / password

If PHP is not in PATH, use Laragon's PHP executable.

Useful verification commands:

```bash
php artisan route:list
php artisan test
php artisan smartrh:health-check
```

Production notes are in `docs/PRODUCTION_CHECKLIST.md`.
