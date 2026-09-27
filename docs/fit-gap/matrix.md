# DDE-Mart Admin — Legacy Fit-Gap Matrix

> Read-only inventories of `emart-66/web/Admin Panel` vs the rebuilt panel,
> consolidated 2026-09-27. Status: ✅ present · ⚠️ partial · ❌ missing.
> Priority proposals are proposals only — owner gives the final
> rebuild / defer / drop verdict per row (Phase 3).

## 1. Settings

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Global: app name, logos (app/menu/provider/worker), panel+app colors | ❌ missing | Propose: rebuild (branding core) |
| Contact us (address/email/phone/default country), map keys, map redirection + driver interval + single-order flag | ⚠️ partial | Keys live in env; UI missing. Propose: rebuild contact + map prefs UI |
| Wallet minima (deposit, owner deposit, withdrawal), digital max file size | ❌ missing | Propose: rebuild (finance guards) |
| Store toggles (story upload + time, auto-approve vendor), provider auto-approve, ads enable, self-delivery enable, ringtone upload | ❌ missing | Propose: rebuild (feature flags drive apps) |
| Email SMTP (from/host/port/user/pass), FCM sender ID + service JSON | ⚠️ partial | Secrets in env by design; UI missing. Propose: rebuild status/display-only UI, never secret values |
| Version strings (app/web/store URLs) | ⚠️ partial | Mobile-versions group exists; store/provider URLs missing. Propose: rebuild |
| Mobile globals (maps key) | ❌ missing | Propose: fold into map prefs UI |
| Banners upload (multi-image + delete) | ✅ present | Catalog banners |
| Radius/geo/OTP (distance unit, nearby radii, accept timeout, auto-cancel, cab/parcel/rental radii + OTP toggles) | ⚠️ partial | `dispatch_*` settings exist; radii/OTP UI missing. Propose: rebuild |
| Schedule-order notification lead time | ❌ missing | Propose: rebuild if scheduled orders stay |
| Delivery charge (vendor-modify flag, per-km, minima + note) | ❌ missing | Propose: rebuild |
| Document verification toggles (store/driver/owner) | ⚠️ partial | Doc types + queue exist; global toggles missing. Propose: rebuild |
| Maintenance per audience (customer/driver/provider/vendor/worker) | ⚠️ partial | Maintenance UI exists; per-audience granularity uncertain. Propose: verify, rebuild if single-switch |
| Special offer master toggle | ❌ missing | Propose: rebuild only if slot-discounts return |
| Business model + bulk commission update | ❌ missing | Propose: rebuild (commission ops) |
| Admin commission (enable/type/value) | ⚠️ partial | Plan-level commissions exist; global switch missing. Propose: rebuild |
| Payment gateways (12 tabs × keys + withdraw enables) | ⚠️ partial | 5 live drivers + execute exist; per-gateway admin key pages missing by design (env). Propose: keep env, add status dashboard |
| Cab promos, rental vehicle types/vehicles/discounts | ⚠️ partial | Unified coupons exist; vehicle-type masters + rental discounts missing. Propose: rebuild masters |
| Complaints + SOS inboxes (status workflow, order breakdown, SOS map) | ✅ present | Inboxes + reporter column exist |
| Email send helper (test/compose) | ✅ present | Manual email exists |
| Homepage/footer templates | ✅ present | Homepage/footer CMS + content blocks exist |

## 2. System

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Languages CRUD (+RTL, flag upload) | ✅ present | — |
| Currencies CRUD (symbol-at-right, digits, single-default) | ⚠️ partial | Core present; symbol-at-right/digits uncertain. Propose: verify, rebuild if missing |
| Taxes CRUD (country, fix/percent, section scope) | ✅ present | — |
| Email templates CRUD | ✅ present | — |
| CMS pages CRUD | ✅ present | — |
| Dynamic (transactional) notifications CRUD | ✅ present | Push/email templates exist |
| Zones (polygon map draw, publish) | ⚠️ partial | Zones + coverage exist; polygon draw UI uncertain. Propose: verify, rebuild if list-only |
| Document types (vendor/driver/owner, front/back toggles) | ✅ present | — |
| Database/backup, system logs, cron UI, cache tools | ❌ missing | Absent in legacy too. Propose: defer unless ops demands |
| Vehicle types/makes/models admin | ⚠️ partial | Fleet masters exist (D5). Propose: verify coverage |

## 3. Auth / RBAC

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Admin login (+remember-me, validation) | ✅ present | Throttled login; remember-me minor. Propose: defer |
| Password reset / verify flows (staff) | ⚠️ partial | Customer reset exists; staff reset uncertain. Propose: verify, rebuild if missing |
| Public vendor self-onboarding (long form: profile, store, hours, amenities, dine-in, delivery, bank) | ❌ missing | Propose: rebuild (growth-critical) — phased |
| Roles CRUD + permission matrix | ✅ present | Ability matrix exists |
| Staff admin users CRUD | ✅ present | — |
| Own profile + password change | ✅ present | — |

## 4. Users (customers)

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Customer list (status filter, avatar/contact/date, toggle, export) | ⚠️ partial | Staff users exist; customer list uncertain. Propose: verify, rebuild if missing |
| Customer create/edit (names, email, phone, image, active, reset-password mail) | ❌ missing | Propose: rebuild |
| Customer detail (wallet top-up modal, tabs, stats, addresses, map) | ❌ missing | Propose: rebuild |
| Wallet transactions ledger (+manual adjustment form) | ✅ present | Wallet ledger + splits exist; manual adjust uncertain. Propose: verify |
| Referrals inbox | ✅ present | Ledger exists; code mechanics TBD (matches legacy gap) |
| Addresses | ❌ missing | Legacy also had no CRUD. Propose: defer |

## 5. Dashboard

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Per-vertical dashboards (delivery/ecommerce/cab/parcel/rental/ondemand) | ⚠️ partial | Real aggregates exist; per-vertical split uncertain. Propose: verify, rebuild splits |
| Earnings + commission cards, orders/bookings, clients, drivers/stores/vendors counts | ✅ present | Aggregates exist |
| Charts (status doughnut, sales, commissions) | ⚠️ partial | Uncertain. Propose: verify, rebuild if tables-only |
| Top stores / recent orders, top users/drivers, top services/providers | ⚠️ partial | Partially present. Propose: verify, rebuild missing tables |

## 6. Stores / Vendors

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Stores list (category filter, stat cards, search, export, clone, wallet/view/edit/delete) | ⚠️ partial | CRUD + status machine exist; stats/filters/export/clone missing. Propose: rebuild |
| Store create (vendor details, gallery, working hours, services, dine-in, self-delivery, delivery charge, special-offer slots, story) | ❌ missing | Current create is minimal. Propose: rebuild in phases |
| Store edit + commission details | ❌ missing | Propose: rebuild with create |
| Store detail (12 tabs, KPIs, subscription mgmt, gallery, services, limits modal, wallet modal) | ❌ missing | Propose: rebuild (ops hub) |
| Vendors approved/pending lists (status + date filters, toggles, export) | ⚠️ partial | Owners + queue exist; approved/pending split uncertain. Propose: verify, rebuild if single list |
| Vendor create/edit (profile, subscription, bank, reset-password mail) | ❌ missing | Propose: rebuild |
| Vendor documents queue + upload (approve/reject workflow) | ✅ present | Queue exists |
| Vendor payouts list/create + payout requests + bank modal + disbursements | ✅ present | Requests + batches + cascade exist |
| Subscription assign/change/history/limits | ⚠️ partial | Subs ledger exists; assign/change UI uncertain. Propose: verify, rebuild if missing |
| Deliverymen (store-attached CRUD) | ❌ missing | No backend surface. Propose: defer (needs backend track) |
| QR code, story upload, dine-in settings on store | ❌ missing | Propose: defer (needs backend track) |

## 7. Providers / Workers

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Providers list/detail/create/edit (bank, commission, subscription, tabs, KPIs) | ⚠️ partial | Owners/wallets/referrals imported; full CRUD uncertain. Propose: verify, rebuild gaps |
| Workers list/create/edit (salary, address, provider link, toggles) | ❌ missing | Backend has worker surfaces (API). Propose: rebuild admin UI |
| Provider payouts/requests/disbursements + bank modal | ✅ present | Same engine as vendor |
| Provider documents | ✅ present | Queue covers owner type; verify provider scoping |

## 8. Drivers / Fleet

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Drivers approved/pending lists (status/date filters, online display, export) | ⚠️ partial | Drivers + queue exist; splits/filters uncertain. Propose: verify |
| Driver create/edit (personal, vehicle per service, bank, zone, reset mail) | ❌ missing | Current driver admin is list-level. Propose: rebuild |
| Driver detail (KPIs, tabs, vehicle/bank cards, wallet modal) | ❌ missing | Propose: rebuild |
| Driver documents queue + upload | ✅ present | Exists |
| Driver payouts/requests/disbursements + bank modal | ✅ present | Exists |
| Fleet drivers (owner-linked CRUD + view) | ❌ missing | No backend surface. Propose: defer (needs backend track) |
| Deliverymen (store couriers) | ❌ missing | Same as §6. Propose: defer |
| Vehicle types/makes/models | ⚠️ partial | Fleet masters exist. Propose: verify |
| Cash-in-hand ledger, incentives/bonuses | ❌ missing | Absent in legacy too. Propose: drop unless ops demands |
| SOS handling view | ✅ present | SOS inbox exists |

## 9. Owners

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Owners approved/pending lists + detail (tabs, KPIs, drivers, orders) | ⚠️ partial | Owners exist; splits/detail uncertain. Propose: verify |
| Owner create/edit (bank, subscription, reset mail) | ❌ missing | Propose: rebuild with vendor edit |
| Owner documents/payouts/wallets | ✅ present | Queues + ledger cover this |

## 10. Orders

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Food orders list (KPI cards, status/type/date filters, scoped tabs, export, bulk) | ⚠️ partial | Core pipeline exists; KPIs/filters/export uncertain. Propose: verify, rebuild gaps |
| Order detail/edit (general card, items, totals, billing/driver/vendor cards, reviews, courier/prep-time/driver-assign modals, print) | ⚠️ partial | Timeline + print exist; modals/cards uncertain. Propose: verify, rebuild gaps |
| Status machine + notifications + wallet refund on reject | ✅ present | Machine + events + FCM exist; refund path to verify |
| Parcel/rental/ride/dine-in/service order lists + details + prints | ⚠️ partial | Vertical modules exist; per-type detail/print uncertain. Propose: verify each |
| Live maps (god-eye), SOS map | ⚠️ partial | Graceful map exists. Propose: verify live coverage |

## 11. Catalog

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Sections (service-type mapping, colors, commission, homepage theme) | ⚠️ partial | CRUD exists; rich mapping uncertain. Propose: verify |
| Categories (homepage flag, review attributes, section scope) | ⚠️ partial | CRUD exists; flags uncertain. Propose: verify |
| Brands, attributes/values, veg filter | ✅ present | — |
| Products full form (vendor, digital, variants + prices/images, specs, nutrition, addons repeater, takeaway, SEO-ish) | ⚠️ partial | Core + addons/attrs exist; variants-pricing/specs/digital uncertain. Propose: verify, rebuild gaps |
| Product read-only detail view | ❌ missing | Propose: rebuild (cheap) |
| Parcel categories/weights/coupons, rental packages/fleet, ondemand cats/services/coupons, review attributes | ✅ present | Transport + catalog cover these |
| Banners (redirect types, positions, app+web images, ordering) | ⚠️ partial | Banners exist; redirect-type depth uncertain. Propose: verify |
| Bulk import | ❌ missing | Legacy had export-only too. Propose: defer (importer covers migration) |

## 12. Promotions

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Store/vendor/ondemand coupons (privacy flag, vendor scope) | ⚠️ partial | Unified coupons exist; privacy/vendor-scope uncertain. Propose: verify |
| Usage-limit engine (min basket, caps, per-user/total, stacking, windows) | ❌ missing | Absent in legacy too. Propose: new-module decision, else drop |
| Parcel/cab/rental coupon variants | ⚠️ partial | Unified engine; variant parity uncertain. Propose: fold into unified, drop variants |
| Advertisements (paid ads, approve/pause/chat, requests, view) | ✅ present | Ads + extras exist; approve/chat flow to verify |
| Special/flash discounts (vendor slots + master toggle) | ❌ missing | Propose: rebuild only with slot-discount scope |
| Gift cards | ✅ present | Buy/redeem/ledger exist |
| Referral amount per section | ❌ missing | Referral ledger exists, no amount setting. Propose: rebuild small setting |
| Stories moderation/queue | ✅ present | Stories mod exists |
| Cashback | ❌ missing | Absent in legacy too. Propose: drop unless business demands |

## 13. Geo

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Zones polygon draw + publish | ⚠️ partial | See §2. Propose: verify |
| Delivery-charge settings | ❌ missing | See §1. Propose: rebuild |
| Dispatch settings (radii, timeouts, OTP toggles) | ⚠️ partial | `dispatch_*` group exists. Propose: verify completeness |
| Map keys + redirection prefs | ❌ missing | See §1. Propose: rebuild prefs UI |

## 14. Finance

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Wallet ledger + manual adjustments | ✅ present | Verify manual-adjust UI |
| Store/driver/provider/owner payment summaries | ⚠️ partial | Payout flows exist; summary pages uncertain. Propose: verify |
| Order transactions ledger | ✅ present | Splits exist |
| Payout requests (all roles) + bank modal + disbursements | ✅ present | — |
| Taxes, currencies, subscription plans | ✅ present | Verify currency options (§2) |
| Live gateway drivers + execute | ✅ present | 5 drivers |
| Withdraw method setup (bank/forms per role) | ❌ missing | No backend surface. Propose: defer (needs backend track) |
| Earnings/commission reports | ⚠️ partial | Sales report exists. Propose: verify scope |

## 15. Content & support

| Legacy component | New status | Notes / proposal |
|---|---|---|
| CMS/terms/privacy, languages, onboarding slides, homepage/footer | ✅ present | — |
| Push history/send, dynamic notifications, email templates/manual email | ✅ present | Verify scheduled + logs |
| Support chat (two-sided), complaints, SOS | ✅ present | Chat reply added this track |
| Dine-in inbox | ✅ present | — |

## 16. Reports

| Legacy component | New status | Notes / proposal |
|---|---|---|
| Sales report (multi-actor filters + CSV/PDF export) | ⚠️ partial | Sales + CSV exist; PDF + full filter set uncertain. Propose: verify |
| Earnings/commission/vendor/driver reports | ❌ missing | Met only via ledgers. Propose: rebuild priority reports after verdict |

## Next step (Phase 3)

Owner verdict per ⚠️/❌ row: **rebuild / defer / drop**. Suggested first wave (ops-critical): settings feature flags (§1), vendor/store create + detail (§6), driver create + detail (§8), order list filters + detail modals (§10). Backend-dependent items (fleet, deliverymen, withdraw methods, QR, stories publish) need a backend track first and are marked defer-by-default.

