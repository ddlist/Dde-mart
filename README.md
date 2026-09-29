# DDE-Mart — Admin Panel, Storefront & Mobile API

A complete multi-vertical delivery and on-demand services backend, built with
**Laravel 12 + Tailwind CSS v4 + MySQL 8**. One codebase serves three
fronts: a staff **admin panel** (`/admin`), a customer **storefront**
(`/shop`), and a versioned **mobile API** (`/api/v1`, 152 routes) consumed
by five Flutter apps.

## The system

| Piece | Repo | Branch |
|---|---|---|
| Backend (this repo) — panel + shop + API | [DDE-MART-BACKEND](https://github.com/ddlist/DDE-MART-BACKEND) | `master` |
| Customer app — ordering, parcels, rentals, rides, services, wallet | [DDE-MART-Customer-app](https://github.com/ddlist/DDE-MART-Customer-app) | `main` |
| Driver app — jobs, GPS, payouts, documents, SOS | [DDE-MART-DRIVER-APP](https://github.com/ddlist/DDE-MART-DRIVER-APP) | `main` |
| Vendor app — orders, dine-in, catalog, coupons, payouts | [DDE-MART-Vendor-app](https://github.com/ddlist/DDE-MART-Vendor-app) | `main` |
| Provider app — bookings, services & workers, payouts | [DDE-MART-Provider-app](https://github.com/ddlist/DDE-MART-Provider-app) | `master` |
| Handyman app — assigned jobs, payouts, SOS | [DDE-MART-Handyman-app](https://github.com/ddlist/DDE-MART-Handyman-app) | `master` |

Verticals covered end to end: food & grocery delivery, parcels, rentals,
rides, home services, dine-in, gifts, wallet & referrals.

## Quick start

```bash
cp .env.example .env
# MySQL: CREATE DATABASE dde_mart_admin; then set DB_* in .env
php artisan key:generate
php artisan migrate --seed   # Super Admin role + admin@email.com / 12345678
php artisan storage:link
npm install && npm run build
```

Serve locally (Herd/Valet domain `http://dde-mart-admin.test`, or
`php artisan serve`), then open `/login` → `/admin` dashboard.

> **Default admin login:** `admin@email.com` / `12345678`
> (seeded by `migrate --seed`). Change it immediately after first login.

Daily run needs three processes:
web server, `php artisan queue:work`, and `php artisan schedule:work`
(dispatch engine + scheduled pushes).

## Features

- **Dashboard** — real aggregates, per-vertical scoping, charts.
- **People** — customers, staff roles with granular grants; stores,
  providers, drivers, owners with approve → detail flows (KPIs, bank,
  documents, fleet links).
- **Orders** — filters, transition machine with history, driver assignment,
  auto-dispatch with timeout/re-offer, printable views.
- **Catalog** — sections, categories, brands, products with variant
  pricing, attributes, addons, banners.
- **Promotions** — vendor-scoped coupons, flash deals, referral rewards.
- **Finance** — payment summaries, manual wallet adjustments, payout
  workflow with live drivers (Stripe, Razorpay, PayPal, Paytm,
  Flutterwave), saved withdraw methods.
- **Content & support** — pages, onboarding, push templates, broadcasts +
  scheduled pushes with logs, email templates, two-sided chat,
  complaints, SOS inbox, live ops map, maintenance mode.
- **Reports** — sales (filters + CSV + print/PDF) and earnings (vendor
  payouts after per-store commission, driver delivery earnings).
- **Mobile API** — OTP auth for every role, launch gate
  (`/app-config`: min versions, maintenance, per-app brand logos),
  honest 422s when a gateway isn't configured. Full reference:
  [`docs/api-v1.md`](docs/api-v1.md) (regen: `php artisan ddemart:api-docs`).
- **Storefront** — landing, catalog, cart, checkout (COD/wallet/gateway),
  orders with tracking, wallet top-up, rides, password reset.

## Testing

```bash
php artisan test   # 238 tests, ~1400 assertions: auth, checkout math,
                   # dispatch, payouts, wallet, safety, every role API,
                   # storefront render, every admin page render
```

The five apps add 66 Flutter tests and are `flutter analyze` clean.
Live smoke (public endpoints, 401 gates, launch config) and on-device
boot checks run before every handoff.

## Data import

```bash
php artisan ddemart:import --file=/path/to/collections.json --dry-run
php artisan ddemart:import --file=/path/to/collections.json
# Options: --only=sections,categories,brands,products,coupons --limit=25
```

Idempotent via `legacy_id` — safe to re-run. Imports catalog, stores,
drivers, owners, orders, ledgers, reviews, transport, services, support
and engagement rows.

## Going live

```bash
APP_ENV=production APP_DEBUG=false
php artisan migrate --force --seed
php artisan storage:link
npm run build
# cron: * * * * * php artisan schedule:run >> /dev/null 2>&1
# queue: supervise a worker if QUEUE_CONNECTION leaves sync
```

Then: change the admin password, set staff grants, add production keys
(`FIREBASE_CREDENTIALS`, SMS gateway, `STRIPE_SECRET` / `RAZORPAY_*` /
`PAYPAL_*` / `PAYTM_*` / `FLW_*`, `MAPS_KEY`, SMTP), upload brand logos
in Settings → Branding, set min app versions, and test one payout per
gateway in sandbox before going live. Apps release with
`--dart-define=API_BASE_URL=https://api.your-domain.com/api/v1`.

## Docs & support

- Setup guide: [`dde-mart/documentation.html`](../documentation.html)
- Project overview: [`dde-mart/about.html`](../about.html)
- Installation, support & customization: [`dde-mart/contact.html`](../contact.html)

Never commit `.env`, service-account keys, `google-services.json`,
`GoogleService-Info.plist`, or `*.sql` dumps.

## Credits

Built by [DDLIST](https://ddlist.github.io).

## License

DDLIST Commercial Source License v1.0 — see [LICENSE](LICENSE). You may
use, run, edit, and modify the software for personal or business use,
but you may not resell, redistribute, or republish it.
