# Security Notes

SmartRH Maroc uses shared database multi-tenancy with `company_id`.

Security measures in the MVP:

- Filament Resources are scoped by company for non-Super Admin users.
- Policies enforce record-level company access.
- Employee portal users can only access their own employee profile and files.
- PDFs are stored on the private local disk and downloaded through controlled routes.
- Exports are scoped to the authenticated user's company.

Before production, add deeper policy tests, backup automation, monitoring, and a full permission review.
