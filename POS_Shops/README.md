# SimplePOS - Laravel 12 POS System

A clean, scalable starting point for a Point of Sale (POS) system built with Laravel 12, PHP 8.2+, and Blade templating.

## Features

- **Authentication** (via Laravel Breeze): Login, Signup, Password Reset
- **Role-based access control**: Owner and Cashier roles with middleware protection
- **Dashboard**: Overview with product count, low-stock alerts, daily income/expense summary
- **User Management** (Owner only): View, activate/deactivate cashiers, assign granular permissions
- **Inventory Management**: Full CRUD for products and categories with search and filtering
- **Daily Ledger**: Record income/expense entries with date filtering and net total calculation
- **Reusable Blade Components**: Buttons, cards, data tables, status badges, sidebar links
- **Responsive Design**: Sidebar navigation with mobile drawer, works on all screen sizes

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Database**: SQLite (default for local dev) / MySQL (for production)
- **Auth**: Laravel Breeze (Blade stack)

## Quick Start

### Prerequisites
- PHP 8.2+ with extensions: mbstring, xml, dom, curl, zip, pdo, pdo_mysql, intl, tokenizer, fileinfo
- Composer
- Node.js & npm

### Installation

```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Build frontend assets
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed
php artisan migrate --seed

# Start the server
php artisan serve
```

Visit `http://localhost:8000` and register a new account (first registrant becomes the owner).

### Seeded Test Accounts

| Role    | Email                  | Password   |
|---------|------------------------|------------|
| Owner   | owner@simplepos.com    | password   |
| Cashier | cashier@simplepos.com  | password   |

## Switching to MySQL

Edit `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simplepos
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run `php artisan migrate --seed` to create tables and seed data.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/           # Breeze auth controllers
│   │   ├── CategoryController.php
│   │   ├── DashboardController.php
│   │   ├── LedgerController.php
│   │   ├── ProductController.php
│   │   └── UserController.php
│   └── Middleware/
│       ├── CheckPermission.php
│       └── CheckRole.php
├── Models/
│   ├── Category.php
│   ├── LedgerEntry.php
│   ├── Product.php
│   └── User.php

database/
├── migrations/
│   ├── add_role_and_status_to_users_table.php
│   ├── create_categories_table.php
│   ├── create_products_table.php
│   └── create_ledger_entries_table.php
└── seeders/
    └── DatabaseSeeder.php

resources/views/
├── layouts/
│   ├── app.blade.php      # Main layout with sidebar
│   ├── sidebar.blade.php  # Navigation sidebar
│   └── topbar.blade.php   # Top header bar
├── components/
│   ├── button.blade.php
│   ├── card.blade.php
│   ├── data-table.blade.php
│   ├── page-header.blade.php
│   ├── sidebar-link.blade.php
│   └── status-badge.blade.php
├── dashboard.blade.php
├── users/                 # User management views
├── categories/            # Category CRUD views
├── products/              # Product CRUD views
└── ledger/                # Daily ledger views
```

## Permissions System

Cashiers can be granted individual permissions by the owner:

- `view_inventory` - View products and categories
- `manage_inventory` - Add/edit products and categories
- `view_ledger` - View the daily ledger
- `manage_ledger` - Add/delete ledger entries
- `process_sales` - Process sales (future feature)

Owners automatically have all permissions.

## Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Configure MySQL database credentials
3. Run `php artisan migrate --seed`
4. Run `npm run build` for production assets
5. Point your web server (Nginx/Apache) to the `public/` directory
6. Ensure `storage/` and `bootstrap/cache/` are writable

## License

This project is open-sourced software licensed under the MIT license.
