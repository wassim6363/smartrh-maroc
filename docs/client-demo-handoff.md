# SmartRH Maroc - Client Demo Handoff

## Demo access

Admin URL: `http://127.0.0.1:8000/admin`

Admin account: `admin@smartrh.test` / `password`

Employee portal: `http://127.0.0.1:8000/employee/login`

Employee account: `amina.employee@smartrh.test` / `password`

## Reset the local demo

Use this before a client presentation when you want clean seeded data:

```bash
php artisan smartrh:reset-demo --force
```

For a faster developer reset without health checks:

```bash
php artisan smartrh:reset-demo --force --skip-checks
```

## Manual QA checklist

- Public landing page loads at `/`.
- Pricing page loads at `/pricing`.
- Demo request form loads and validates friendly errors at `/request-demo`.
- Admin login loads at `/admin/login`.
- Admin dashboard opens after login.
- Employee list opens at `/admin/employees`.
- Employee quick actions generate documents without Livewire 500 errors.
- Payroll preview/generation works for seeded employees.
- Employee portal login works at `/employee/login`.
- Employee can view only their own payslips, contracts, documents, and support tickets.
- PDF files show SmartRH Maroc branding, employer/client details, and legal disclaimer.

## Required checks

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
php artisan smartrh:health-check
php artisan smartrh:local-ready-check
```

## Production caution

Payroll rules, legal settings, generated contracts, invoices, and HR documents must be reviewed by a qualified Moroccan accountant, legal expert, or other competent professional before production use.
