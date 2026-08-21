# MiniPOS Keycloak SSO

## Start Keycloak

From the project root:

```bash
docker compose -f docker-compose.keycloak.yml up -d
```

Keycloak runs at <http://localhost:8080>. The administration console credentials are `admin` / `admin`. These credentials and the included client secret are for local development only.

The imported `minipos` realm contains ten users:

- Usernames: `user01` through `user10`
- Emails: `user01@minipos.test` through `user10@minipos.test`
- Password for every dummy user: `password`

## Configure Laravel

Copy these settings into `Backend/.env`:

```dotenv
KEYCLOAK_ENABLED=true
KEYCLOAK_BASE_URL=http://localhost:8080
KEYCLOAK_PUBLIC_URL=http://localhost:8080
KEYCLOAK_REALM=minipos
KEYCLOAK_CLIENT_ID=minipos-web
KEYCLOAK_CLIENT_SECRET=minipos-local-secret
KEYCLOAK_REDIRECT_URI=http://localhost:8000/api/auth/keycloak/callback
KEYCLOAK_FRONTEND_CALLBACK=http://localhost:5173/auth/sso/callback
```

Apply the user migration and seed the ten matching Laravel users:

```bash
cd Backend
php artisan migrate
php artisan db:seed
php artisan config:clear
php artisan serve
```

## Configure the frontend

Set these values in `Frontend/.env`:

```dotenv
VITE_API_URL=http://localhost:8000/api
VITE_KEYCLOAK_ENABLED=true
```

Then start it:

```bash
cd Frontend
npm run dev
```

Open <http://localhost:5173/login>, select **Sign in with Keycloak SSO**, and use any dummy account.

## Production notes

- Replace the development client secret and Keycloak administrator password.
- Use HTTPS for Keycloak, Laravel, the callback, and the frontend.
- Update the client's valid redirect URI and web origin in Keycloak.
- Set `KEYCLOAK_BASE_URL` to the URL Laravel can reach and `KEYCLOAK_PUBLIC_URL` to the URL users' browsers can reach.
- Do not import the local realm file into production without changing its dummy credentials.
