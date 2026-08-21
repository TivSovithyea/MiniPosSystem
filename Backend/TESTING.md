# Backend testing

The API routes are tested with Laravel feature tests because endpoint tests cover the router, Sanctum middleware, validation, database, controllers, and JSON responses together. PHPUnit unit tests are reserved for isolated PHP classes.

Tests use an in-memory SQLite database configured in `phpunit.xml`. They do not modify the development or production database.

## Run locally

Install dependencies once:

```bash
cd Backend
composer install
```

Run the complete suite:

```bash
php artisan test
```

Run only endpoint tests:

```bash
php artisan test tests/Feature
```

Run one file or one test:

```bash
php artisan test tests/Feature/ApiEndpointTest.php
php artisan test --filter=test_product_crud_endpoints_and_filters
```

Use PHPUnit's readable test names and stop at the first failure:

```bash
php artisan test --testdox --stop-on-failure
```

## Run with Docker

From the `Backend` directory, build and run the dedicated test stage:

```bash
docker build --target test -t minipos-backend-test .
docker run --rm minipos-backend-test
```

The normal final Docker stage remains the production image and excludes development dependencies.

## Test layout

- `tests/Feature/AuthApiTest.php`: login, profile, logout, invalid credentials, and guest access.
- `tests/Feature/ApiEndpointTest.php`: health, category/customer/product CRUD, filters, validation, orders, cancellation, dashboard, reports, and PayWay endpoint guards.
- `tests/Feature/PosApiTest.php`: checkout calculations and stock, image storage, PayWay integration responses, callbacks, and sales reports.
- `tests/Unit`: isolated class tests that do not make HTTP requests.
