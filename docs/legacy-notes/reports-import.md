# Reports + Import — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): `HomeController` (45 lines), `ReportController` (17 lines),
`resources/views/{dashboard,reports}/*` (Firestore-driven shells).

## Legacy behavior

- **Dashboard:** picks one of 6 per-vertical Blade shells from `service_type` cookie
  (cab/delivery/ecommerce/parcel/rental/ondemand, default delivery). All numbers are
  Firestore JS queries.
- **Reports:** single `reports.sales-reports` shell (JS + Firestore).
- **`storeFirebaseService`:** any authenticated admin can POST base64 `serviceJson` and the app
  WRITES it to `storage/app/firebase/credentials.json`, silently switching the Firebase
  project the whole panel talks to. No validation, no audit, no confirmation.

## Fixed in the rebuild

1. Cookie-driven vertical shells → ONE dashboard backed by MySQL aggregates (this DB).
2. No credential upload endpoint exists, ever — `FIREBASE_CREDENTIALS` comes from env/volume.
3. Sales report is server-computed with date/status filters + CSV export.
4. Import is an auditable artisan command (`ddemart:import`, dry-run first), not a browser action.

## Fresh design (D10)

- **Dashboard:** users, active products, orders today, revenue MTD (completed), pending payouts
  (count + amount), status breakdown, 5 recent orders, low-stock (qty ≤ 5) list.
- **Sales report** (`reports.view` gate): from/to + status filters, KPI cards
  (orders, gross, discount, delivery, tax, net), daily rows, CSV export of the same query.
- **Importer:** `--file` (collections.json export), `--only` (sections|categories|brands|
  products|coupons), `--limit`, `--dry-run`. Idempotent via `legacy_id` columns.
  Field maps: Firestore `publish/is_publish/isEnabled/isEnable → is_active`,
  `title → name`, `photo/sectionImage/image → image_path` (remote URLs kept as-is and
  rendered via `Images::url()`), money strings → decimals, Firestore timestamps → datetimes,
  `Percentage|Fix Price → percentage|fixed`, add-on parallel arrays → `product_addons` rows,
  status vocabulary → canonical map from `orders.md`.
- **Explicitly NOT imported yet:** orders (need vendor/customer owners), payouts/wallets
  (need requester owners), users (app accounts), parcel/rental entities (modules don't exist).
  They resolve in owner-module milestones using the same `legacy_id` pattern.
