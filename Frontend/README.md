# MiniPOS Frontend

A React and JavaScript point-of-sale frontend built with Vite, React Router, Redux Toolkit, Tailwind CSS, and shadcn/ui conventions.

Both authentication and cart state use Redux Toolkit. `authSlice` owns the user, token, loading, and error state; Laravel Sanctum supplies the API token; and `ProtectedRoute` guards private screens. React Context is not used for authentication.

## Start

```bash
npm install
npm run dev
```

Copy `.env.example` to `.env` when connecting an API.

## Structure

- `src/api` — API client and configuration
- `src/assets` — static local assets
- `src/components/layout` — application layouts
- `src/components/ui` — shadcn/ui components
- `src/data` — mock/static data
- `src/hooks` — reusable typed hooks
- `src/pages` — route-level screens
- `src/redux` — Redux Toolkit store and slices
- `src/router` — React Router configuration
- `src/services` — domain service functions
- `src/utils` — shared helpers
