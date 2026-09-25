# Stores — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): `Store/Vendor/Owner/VendorFilters/*PayoutController` (shells),
Firestore `collections.json` (`vendors` 56 docs; 52/55 product vendorIDs resolve).

## Legacy behavior

- **Controllers are shells** (`index/edit/create` + id to JS). All state in Firestore `vendors`
  docs, written browser-side.
- **Vendor doc:** title, description, authorName/phone (owner), location + lat/lng (+`coordinates`
  geopoint + `g` geo-hash duplicates), photo + photos[], section_id, categoryID[] (served
  categories), zoneId, `reststatus` (open bool as string), `adminCommission{type,commission,enable}`,
  isSelfDelivery, deliveryCharge JSON, workingHours[] + specialDiscount[] (schedule matrices),
  subscriptionPlanId + snapshot, walletAmount, reviewsCount/reviewsSum aggregates, filters{} map,
  dine-in fields, fcmToken (device token stored on a public doc).
- **Vendor filters:** a free-form `filters{label: Yes/No}` map per vendor (UI toggles like
  "Free Wi-Fi"); no global definitions collection.
- **Owners:** `owners/*` views = store-owner accounts (app users), separate from staff.

## Bugs / smells fixed in the DDE-Mart rebuild

1. Browser-direct writes → server CRUD with `stores.*` gates.
2. Triple location storage (lat/lng + geopoint + geohash) → single lat/lng pair (+zone link).
3. Money/percents/bools as strings (`"True"`, `"10"`) → typed columns.
4. `reviewsCount/reviewsSum` stored → computed when reviews land.
5. Device `fcmToken` on vendor docs → never stored (topics only, D9 pattern).
6. No status workflow (`reststatus` bool only) → pending→active⇄suspended + rejected machine.
7. `vendor_filters` as free text → deferred: structured filter definitions land with storefront
   search (later); raw map ignored on import.
8. Owner accounts → deferred with customer auth (later); `owner_name/phone` snapshot kept.

## Fresh design (Stores milestone)

- Table `stores` (legacy_id, name/slug, description, owner snapshot, phone, address, lat/lng,
  image, section FK, zone FK, status, is_open, commission type/value, min_order, delivery
  fees, subscription_plan FK). Delete blocked while products attach; products.vendor_id gains
  a real FK (nullOnDelete).
- Importer: `zones` (adds zones.legacy_id) + `vendors` + backfill of products.vendor_id via
  the legacy map. Commission/schedule matrices simplified to scalar settings; workingHours
  preserved for a later scheduling milestone (noted, not imported).
- Gates: new `stores` group (view/create/edit/delete) in the permission catalog.
