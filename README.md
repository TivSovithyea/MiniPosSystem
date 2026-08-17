# MiniPOS

MiniPOS is a responsive, open-source point-of-sale and store-management application for small marts, cafes, and retail shops. It combines a Laravel REST API with a React frontend and includes inventory management, checkout, sales reporting, token authentication, and printable 80 mm receipts.

> This project is suitable for learning, prototyping, and small-business customization. Review the [production checklist](#production-checklist) before using it with real payments or customer data.

## Features

- Secure login and logout with Laravel Sanctum API tokens
- Responsive dashboard for desktop, tablet, and mobile
- Category, product, and customer CRUD with search and pagination
- Product filtering, stock tracking, and transactional checkout
- Cash, card, and QR payment methods
- Charge or charge-and-print checkout actions
- Order history, cancellation, refunds, and stock restoration
- Order, product-sales, and customer-sales reports with date filters
- 80 mm mini-mart receipt printing and Save as PDF support
- Toast notifications and authenticated route protection
- Large, rerunnable demonstration dataset
- Laravel feature tests and frontend lint/build checks

## Technology

| Area | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13, Eloquent ORM |
| Authentication | Laravel Sanctum |
| Database | MySQL 8+ |
| Frontend | React, Vite, React Router |
| State | Redux Toolkit |
| Styling | Tailwind CSS, class-variance-authority |
| Icons | Lucide React |
| Testing | PHPUnit, Laravel HTTP tests, ESLint |

## Architecture

```text
Browser
  └── React UI
      ├── React Router protected pages
      ├── Redux authentication/cart state
      └── API services + Bearer token
              │
              ▼
      Laravel REST API
      ├── Sanctum authentication
      ├── Validation and controllers
      ├── Transactional checkout
      └── Eloquent models
              │
              ▼
           MySQL
```

```text
MiniPos/
├── Backend/                 Laravel API
│   ├── app/Http/Controllers/Api/
│   ├── app/Models/
│   ├── database/migrations/
│   ├── database/seeders/
│   ├── routes/api.php
│   └── tests/Feature/
├── Frontend/                React application
│   └── src/
│       ├── api/
│       ├── components/
│       ├── pages/
│       ├── redux/
│       ├── router/
│       └── services/
└── Laravel_Week_13_Category_CRUD.pptx  Supporting course material
```

## Requirements

- PHP 8.3 or newer
- Composer 2
- MySQL 8 or newer
- Node.js 20 or newer
- npm

```bash
php -v
composer --version
mysql --version
node --version
npm --version
```

## Installation

### 1. Clone the repository

```bash
git clone YOUR_REPOSITORY_URL.git
cd MiniPos
```

Replace `YOUR_REPOSITORY_URL` with the URL of your fork or repository.

### 2. Create the database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE minipos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'minipos_user'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON minipos.* TO 'minipos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Configure the backend

```bash
cd Backend
composer install
cp .env.example .env
php artisan key:generate
```

Update `Backend/.env`:

```dotenv
APP_NAME=MiniPOS
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minipos
DB_USERNAME=minipos_user
DB_PASSWORD=change_this_password
```

Create the tables and demo records:

```bash
php artisan migrate --seed
```

The seeder creates categories, products, customers, and historical orders for dashboards and reports. It can be rerun with `php artisan db:seed`; only generated `DEMO-*` orders are replaced.

### 4. Configure the frontend

From the repository root:

```bash
cd Frontend
npm install
cp .env.example .env
```

The default `Frontend/.env` value is:

```dotenv
VITE_API_URL=http://localhost:8000/api
```

## Running locally

Start the backend:

```bash
cd Backend
php artisan serve
```

Start the frontend in another terminal:

```bash
cd Frontend
npm run dev
```

Open [http://localhost:5173](http://localhost:5173).

### Demo login

```text
Email:    admin@minipos.test
Password: password
```

The demo credentials are for local development only. Change or remove them before deploying.

## Commands

### Backend

```bash
php artisan serve                 # Start the API server
php artisan migrate --seed        # Run migrations and seed data
php artisan db:seed               # Refresh demonstration data
php artisan test                  # Run backend tests
./vendor/bin/pint                 # Format PHP code
php artisan route:list --path=api # Inspect API routes
```

`php artisan migrate:fresh --seed` deletes all database tables before recreating them. Use it only when existing data can be discarded.

### Frontend

```bash
npm run dev      # Start Vite
npm run build    # Create a production build
npm run preview  # Preview the production build
npm run lint     # Run ESLint
```

## API overview

Business endpoints require these headers after login:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/health` | Check API availability |
| POST | `/api/auth/login` | Create an API token |
| GET | `/api/auth/me` | Get the authenticated user |
| POST | `/api/auth/logout` | Revoke the current token |
| GET | `/api/dashboard` | Dashboard totals and recent activity |
| GET, POST | `/api/categories` | List or create categories |
| GET, PUT, DELETE | `/api/categories/{id}` | Manage a category |
| GET, POST | `/api/products` | List or create products |
| GET, PUT, DELETE | `/api/products/{id}` | Manage a product |
| GET, POST | `/api/customers` | List or create customers |
| GET, PUT, DELETE | `/api/customers/{id}` | Manage a customer |
| GET, POST | `/api/orders` | List orders or perform checkout |
| GET | `/api/orders/{id}` | Get order and receipt data |
| PATCH | `/api/orders/{id}/cancel` | Cancel an order and restore stock |
| GET | `/api/reports/summary` | Sales totals and daily summaries |
| GET | `/api/reports/products` | Product-sales totals |
| GET | `/api/reports/customers` | Customer-sales totals |

List endpoints return Laravel pagination metadata and accept `page` and `per_page`. Relevant endpoints also support `search`, `category_id`, `status`, `date_from`, and `date_to`.

### Login example

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@minipos.test","password":"password"}'
```

### Checkout example

```bash
curl -X POST http://localhost:8000/api/orders \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -d '{
    "customer_id": null,
    "payment_method": "cash",
    "items": [
      {"product_id": 1, "quantity": 2},
      {"product_id": 2, "quantity": 1}
    ]
  }'
```

The backend reads prices from MySQL, calculates totals and tax, locks inventory rows, and rolls back the transaction when stock is insufficient.

## Receipt printing

The POS provides two checkout actions:

- **Charge** completes an order without opening a receipt.
- **Charge & Print** completes an order and opens an 80 mm receipt preview in a new tab.

Use the browser print dialog to send the receipt to a thermal printer or choose **Save as PDF**. Allow pop-ups for the frontend origin so the preview can open.

## Testing

```bash
cd Backend
php artisan test

cd ../Frontend
npm run lint
npm run build
```

Backend tests use an isolated test database and cover authentication, protected APIs, checkout totals, inventory changes, insufficient stock, and reports.

## Common problems

### CORS error

Make sure `FRONTEND_URL` in `Backend/.env` exactly matches the frontend origin, then run `php artisan config:clear`.

### Database connection failed

Verify MySQL is running and confirm the `DB_*` values in `Backend/.env`.

### API requests return 401

Sign in again. Invalid Sanctum tokens are removed automatically.

### Receipt preview does not open

Allow pop-ups for the frontend URL. Charge & Print stops when the browser blocks the preview window.

### Frontend routes return 404 after deployment

Configure the web server to send unknown frontend routes to `Frontend/dist/index.html`, allowing React Router to handle them.

## Production checklist

- Replace the seeded administrator credentials.
- Set `APP_ENV=production`, `APP_DEBUG=false`, and a strong `APP_KEY`.
- Serve both applications over HTTPS.
- Restrict CORS to the real frontend origin.
- Add roles and permissions for administrators and cashiers.
- Configure tax, currency, store name, address, and receipt details.
- Integrate and verify real card or QR payment providers.
- Add inventory adjustments, purchase orders, and audit logs as required.
- Configure database backups, monitoring, logging, and rate limiting.
- Pin frontend dependency versions before a production release.

## Additional documentation

- [Backend API guide](./Backend/README.md)
- [Frontend guide](./Frontend/README.md)
- `Laravel_Week_13_Category_CRUD.pptx` for supporting category CRUD course material

## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a branch: `git checkout -b feature/your-feature`.
3. Make focused changes and add tests when relevant.
4. Run the backend and frontend checks.
5. Commit with a clear message.
6. Open a pull request describing the problem and solution.

Do not commit `.env` files, database credentials, generated tokens, customer data, `vendor/`, or `node_modules/`.

## Security

Do not report security vulnerabilities in a public issue. Contact the repository maintainer privately with reproduction steps, affected versions, and potential impact.

## License

MiniPOS is available under the [MIT License](./LICENSE).
