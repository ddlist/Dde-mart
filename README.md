# DDE-Mart — Admin Panel

Fresh, clean-room rebuild of a legacy food-delivery admin panel (Laravel 10 + Bootstrap)
as a modern **Laravel 12 + Tailwind CSS v4 + Vite + Blade** admin app. No legacy code was
copied — behavior is reimplemented from studied flows. See `../../AGENT.md` for rules and
`docs/legacy-notes/` for per-module behavior specs.

## Quick start

```bash
cp .env.example .env
# MySQL: CREATE DATABASE dde_mart_admin; set DB_* (root/123456 local)
php artisan key:generate
php artisan migrate --seed   # seeds Super Admin role + admin@dde-mart.local / password
npm install
npm run dev   # or: npm run build
php artisan serve
```

Open http://127.0.0.1:8000 → `/login` → `/admin` dashboard.
Default login: `admin@dde-mart.local` / `password` (change immediately).

## Tests

```bash
php artisan test
```

## Structure

- `app/Http/Controllers/Admin/` — dashboard (real aggregates), users, roles, catalog (6),
  orders, promotions (3), finance (4), content (7), reports
- `app/Services/` — `OrderStatus` (transition machine), `FcmSender` (push)
- `app/Payments/` — `PaymentGateway` seam (live drivers = D8c)
- `app/Console/Commands/ImportLegacy.php` — Firestore → MySQL importer (see below)
- `resources/views/` — Tailwind Blade throughout (`x-admin-layout`, `x-stat-card`, …)
- `routes/web.php` — `admin.*` routes behind `auth` + `admin.can:<group>,<ability>`
- `docs/legacy-notes/` — behavior specs distilled from legacy (no copied code)

## Legacy data import

```bash
# 1. Export the Firestore `collections.json` from the previous project's console
# 2. Preview, then import (idempotent via legacy_id — safe to re-run):
php artisan ddemart:import --file=/path/to/collections.json --dry-run
php artisan ddemart:import --file=/path/to/collections.json
# Options: --only=sections,categories,brands,products,coupons --limit=25
```

Imports everything mappable (catalog, stores, drivers, owners, orders, payouts-adjacent
ledgers, reviews, transport, services, support, engagement). Owner-less rows (app users,
live orders) resolve with the customer API track — see `docs/legacy-notes/coverage.md`.

## Launch checklist (production)

```bash
APP_ENV=production APP_DEBUG=false
php artisan key:generate --show   # set APP_KEY
php artisan migrate --force --seed
php artisan storage:link
npm run build
# cron: * * * * * php artisan schedule:run >> /dev/null 2>&1
# (runs dispatch:orders + schedule:send every minute)
# queue: run a worker if QUEUE_CONNECTION leaves sync
# env: DB_*, MAIL_*, FIREBASE_CREDENTIALS, MAPS_KEY,
#      STRIPE_SECRET, RAZORPAY_KEY/SECRET, PAYPAL_* (MODE=live),
#      PAYTM_*, FLW_SECRET_KEY, PAYOUT_CURRENCY
```

Then: change `admin@dde-mart.local` password, grant staff roles (new `transport`,
`drivers`, `owners` groups need explicit grants — super-admin bypasses), test one
payout per gateway in sandbox before `PAYPAL_MODE=live`.

## Roadmap status

Admin panel: COMPLETE — auth+RBAC, users, catalog, food/parcel/rental/ride orders,
on-demand bookings, dine-in, promotions, finance + live gateways, geo+content,
people + verification, wallets, reviews, reports, importer, UI kit.
API v1 (119 routes, `docs/api-v1.md`): customer auth + OTP password reset +
account deletion, catalog, checkout, wallet top-up, bookings (parcel/rental/
ride/service/dine-in/gifts/favorites), chat, reviews, complaints/SOS,
driver + vendor + provider surfaces, workforce push, app-config launch gate.
Storefront: COMPLETE — landing, catalog, cart, checkout (COD/wallet/gateways),
orders + tracking, wallet top-up, rides, password reset, render-guarded views.
Flutter `apps/`: customer (auth/home/launch-gate), driver, vendor — clean-room
rebuilds against API v1.
Still external (ops): Firebase credentials + FCM topics, SMS gateway for OTP
(log driver by default), gateway live keys, Play/App Store listings
(account deletion + app-config gate satisfy the usual review checks).
