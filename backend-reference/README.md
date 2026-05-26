# Lifter’s Touch Empowerment Foundation — Final Deployable Laravel Backend

## Status
This is the consolidated final deployable backend package.

It merges the runnable foundation and all generated milestone layers into one Laravel-style repository:

- v1 Auth + Users + Branches + Members
- v2 Disbursement + Approval + Treasury Mark-Paid
- v3 Period Close/Lock/Reopen + Reconciliation blocker workflow
- v4 Workbook Assimilation Layer
- v5 Idempotency + API Error Standardization + Production Observability
- v6 API Documentation + Postman + Frontend Contract
- v7 Deployment Pack

## Install Locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
```

## Production Deployment

See:

```text
deployment/DEPLOYMENT_GUIDE_CPANEL.md
deployment/GO_LIVE_CHECKLIST.md
deployment/BACKUP_CHECKLIST.md
deployment/ROLLBACK_PLAN.md
deployment/HYPERCARE_RUNBOOK.md
```

## Critical Production Rule
Do not deploy until this exact package passes:

```bash
composer install
php artisan migrate --seed
php artisan test
php artisan serve
```

## Default Seeded Login

```text
email: admin@example.com
password: password
```

Change this immediately after first login.
