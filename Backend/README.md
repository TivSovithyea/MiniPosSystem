# MiniPOS Backend — Laravel + MySQL Tutorial

This REST API uses Laravel 13, PHP 8.3+, and MySQL 8. It manages categories, products and stock, customers, checkout, orders, cancellation/restocking, and dashboard totals.

## Project structure and request flow

```text
MiniPos/
├── Frontend/                    React application
└── Backend/                     Laravel API
    ├── app/Models/              Models and relationships
    ├── app/Http/Controllers/Api Request validation and business logic
    ├── database/migrations/     Table definitions
    ├── database/seeders/        Sample POS records
    ├── routes/api.php           REST endpoints
    └── tests/Feature/           Automated API tests
```

```text
React → API route → controller → validation → model → MySQL → JSON
```

## 1. Requirements

- PHP 8.3 or newer
- Composer 2
- MySQL 8
- Node.js 20+ for the frontend

```bash
php -v
composer --version
mysql --version
```

## 2. Create the MySQL database

Start MySQL, then run `mysql -u root -p` and enter:

```sql
CREATE DATABASE minipos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'minipos_user'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON minipos.* TO 'minipos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

A dedicated user is safer than giving the application MySQL root access.

## 3. Configure Laravel

```bash
cd Backend
cp .env.example .env
composer install
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minipos
DB_USERNAME=minipos_user
DB_PASSWORD=change_this_password
```

Never commit `.env`; it contains secrets. `.env.example` documents variables without real passwords.

## 4. Create tables and demo data

```bash
php artisan migrate --seed
```

During development, `php artisan migrate:fresh --seed` erases every table and rebuilds it. Do not use it when you need to preserve data.

## 5. Start both applications

Backend terminal:

```bash
cd Backend
php artisan serve
```

Frontend terminal:

```bash
cd Frontend
cp .env.example .env
npm install
npm run dev
```

The frontend `.env` must contain:

```dotenv
VITE_API_URL=http://localhost:8000/api
```

## 6. API endpoints

All responses are JSON. Invalid input returns HTTP `422` and an `errors` object. Every endpoint except health and login requires `Authorization: Bearer YOUR_TOKEN`.

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/health` | API health check |
| POST | `/api/auth/login` | Exchange credentials for a token |
| GET | `/api/auth/me` | Get the signed-in user |
| POST | `/api/auth/logout` | Revoke the current token |
| GET | `/api/dashboard` | Sales totals, stock, popular products |
| GET/POST | `/api/categories` | List/create categories |
| GET/PUT/DELETE | `/api/categories/{id}` | Manage one category |
| GET/POST | `/api/products` | List/create products |
| GET/PUT/DELETE | `/api/products/{id}` | Manage one product |
| GET/POST | `/api/customers` | List/create customers |
| GET/PUT/DELETE | `/api/customers/{id}` | Manage one customer |
| GET/POST | `/api/orders` | List orders or checkout |
| GET | `/api/orders/{id}` | Get a receipt/order |
| PATCH | `/api/orders/{id}/cancel` | Cancel and restore stock |

Filter products with `search`, `category_id`, `active_only`, and `per_page`:

```text
GET /api/products?search=coffee&active_only=1&per_page=20
```

Paginated records are inside the `data` property; metadata includes `current_page`, `last_page`, and `total`.

## 7. Try it with curl

```bash
curl http://localhost:8000/api/health
curl 'http://localhost:8000/api/products?active_only=1'
```

Checkout example:

```bash
curl -X POST http://localhost:8000/api/orders \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "payment_method": "cash",
    "discount": 0,
    "items": [
      {"product_id": 1, "quantity": 2},
      {"product_id": 2, "quantity": 1}
    ]
  }'
```

The API reads prices from MySQL and calculates tax itself. It never trusts totals from the browser. Checkout uses a transaction and row locks: if any item lacks stock, the entire operation rolls back.

## 8. Understand the relationships

- One category has many products.
- One customer has many orders; walk-in orders may have no customer.
- One order has many order items.
- An order item references a product and saves a name/price snapshot, preserving old receipts after product changes.
- Cancelling an order marks it cancelled/refunded and restores its stock.

## 9. Tests, formatting, and route inspection

Tests use in-memory SQLite, so they never erase your MySQL data:

```bash
php artisan test
./vendor/bin/pint
php artisan route:list --path=api
```

## 10. Common problems

- **Unknown database:** create `minipos` first.
- **Access denied:** verify DB credentials, then run `php artisan config:clear`.
- **CORS error:** `FRONTEND_URL` must exactly match the React origin and port; clear config afterward.
- **Frontend 404:** `VITE_API_URL` must include `/api`; restart Vite after editing `.env`.
- **Schema changed:** run `php artisan migrate`.

## 11. Before production

Add role/permission checks, configure store-specific tax settings, verify payments with a provider, add inventory audit records, and configure HTTPS, backups, monitoring, and rate limiting. Sanctum token authentication is already installed.
