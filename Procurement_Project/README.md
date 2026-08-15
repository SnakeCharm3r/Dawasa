# Procurement Management System

A role-controlled procurement platform covering the full request-to-payment lifecycle. Laravel owns the domain, persistence, policies, approvals, audit records, and JSON endpoints. The user workspace is a separate Next.js application in `frontend/`.

The detailed business rules and workflow definitions live in [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md).

## Application Areas

- Purchase requisitions and multi-stage approval
- Supplier proformas and approval recommendations
- Local purchase orders (LPOs), accountant confirmation, issue, and acknowledgement
- Delivery notes, warehouse receipt, inspection, and store signature
- Supplier invoice matching and variance decisions
- Payment vouchers, approvals, and payment recording
- Procurement closure and requester confirmation
- Entity budgets, suppliers, users, departments, reports, and dashboards

Navigation and workflow actions are filtered by the authenticated user's roles: `super_admin`, `gm`, `accountant`, `procurement_officer`, `department_head`, `requester`, `auditor`, `line_manager`, `storekeeper`, and `receiving_officer`.

## Document Workflow

Every purchasing document retains its database ID and explicit foreign-key chain: requisition -> approved supplier proforma -> LPO -> accepted delivery note/GRN -> supplier invoice. Requisitions, proformas, and LPOs can be rejected at their approval stage. An invoice can only be created after store acceptance and does not have a rejected state; finance either matches and pays it or cancels a draft through the controlled cancellation workflow. Once all supplier invoices are paid and the obligations are satisfied, procurement can close the requisition.

## Requirements

- PHP 8.2 or newer with DOM and XML enabled
- Composer 2
- Node.js 20.9 or newer
- MySQL 8 or compatible MariaDB

On this workstation, `/opt/lampp/bin/php` is PHP 8.2.12 and includes DOM/XML. Use it to avoid the system PHP installation that is missing those extensions:

```bash
/opt/lampp/bin/php /usr/bin/composer install
```

## Setup

The default environment connects to MySQL at `127.0.0.1:3306` and uses the `Procurement` database.

```bash
cp .env.example .env
/opt/lampp/bin/php artisan key:generate
/opt/lampp/bin/php artisan migrate --seed
npm --prefix frontend install
```

The organisational seeder is idempotent. On a new database it creates this local account:

```text
Email: super_admin@example.com
Password: password
```

Change that password before using the application outside local development.

## Development

Run Laravel and Next.js in separate terminals:

```bash
/opt/lampp/bin/php artisan serve
```

```bash
npm --prefix frontend run dev
```

The workspace is available at `http://localhost:3000`. Next proxies `/backend/*` to Laravel at `http://127.0.0.1:8000`. To use another backend address, create `frontend/.env.local`:

```bash
LARAVEL_API_URL=http://127.0.0.1:8001
```

## Verification

```bash
/opt/lampp/bin/php artisan test
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run build
```
