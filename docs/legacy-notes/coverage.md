# Coverage matrix — legacy Admin Panel vs dde-mart (exhaustive audit, 2026-09-25)

Source: all 299 routes in legacy `routes/web.php` (+ stub `api.php`), 55 controllers,
~70 view dirs, 70 Firestore collections, SQL dumps, support zips.
Method: behavior reimplemented, never copied. Status per item: DONE / PARTIAL / PENDING / DROPPED.

## A. DONE in dde-mart/admin-panel (with better verbs, validation, tests)

Auth (login-only, throttled) · Roles + ability matrix · Staff users + profile · Sections,
Categories, Brands, Attributes+values, Products (+addons, +store link), Banners · Food orders
(status machine, timeline, events) · Coupons (all scopes), Advertisements (status machine),
Gift cards · Taxes, Currencies (single default), Subscription plans, Payout requests
(workflow) · Zones (+haversine), Settings (allowlisted keys), Broadcasts + log, Push + email
templates, CMS pages, Languages · Stores (workflow) + vendor/zone import · Dashboard
aggregates · Sales report + CSV · Importer (sections/categories/brands/stores/products/
coupons/zones; 658 docs live) · UI kit + icon layout + mobile drawer.

## B. PENDING — people & verification

1. **Drivers** (routes: index/approved/pending/edit/create/view; 63 rides, driver_payouts 20):
   driver directory + approval workflow + documents + fleet-drivers subset. Includes `fleetDrivers*`
   views and dead `driverChat()` method (no route — confirm drop).
2. **Deliverymen / store riders** (deliveryman views+controller): distinct from ride drivers;
   per-store rider assignment.
3. **Owners** (index/approved/pending/edit/create/view + document upload + driverList): store-owner
   accounts; blocked on customer-auth milestone (like app users).
4. **Documents & verification** (`documents` 5 types; `documents_verify` 129 queue rows):
   verification-type master + approve/reject queue for drivers/owners/vendors.
5. **Wallets & ledger** (`wallet` 1983, `wallet_transaction`): per-user ledger UI
   (`walletstransaction`, owner + per-user views), top-up history.
6. **Referrals** (`referral` 2464 rows, no legacy admin UI): program overview table (new value-add).

## C. PENDING — transport verticals (each: entities + orders + settings + import)

7. **Parcel**: `parcel_categories` (11), `parcel_weight` slabs (5, with delivery_charge),
   parcel coupons (covered by code via scope, needs UI filter only), `parcel_orders` (33) +
   owner/driver scoped lists, `map/parcel`.
8. **Rental**: `rental_packages` (12), rental vehicle types + `rentalvehicle` fleet,
   `rentalDiscount`, `rental_orders` (34) + owner/driver lists, `map/rental`.
9. **Rides/cab**: `rides` (63) + view/edit, `vehicle_type` (11), `carMake` (6) + `carModel` (11)
   masters, `popular_destinations` (6), `map/cab`, SOS ride view (`index2`).
10. **On-demand services** (largest): `provider_categories` (14, nested via parentCategoryId),
    `providers_services` (26), `providers_workers` (9), `provider_orders` (77 bookings + print),
    providers CRUD + view, providers_coupons (5; code-covered, needs scope), workers CRUD.
11. **Dine-in / book-a-table** (`booked_table` 35): reservation list/edit + notify action,
    vendor dine-in settings (openDineTime/closeDineTime already on stores, UI pending).

## D. PENDING — commerce follow-ups

12. **Reviews & ratings** (`items_review` 192, `rating`, order_reviews views): moderation inbox +
    `reviewattributes` master (distinct from catalog attributes) + aggregates replace stored counts.
13. **Order extras**: print view (`vendors.orderprint`, ondemand bookings print) — tiny; per-owner
    order lists (owner/orders|parcelorders|rides|rentalorders) — owner-portal scope.
14. **Subscriptions**: `subscription_history` (456) + current-subscriber list per plan.
15. **Gift purchases** (45): gift-code ledger (codes, pins, redeem state).
16. **Disbursement batches**: grouped vendor/driver/owner/provider disbursement views (our payout
    workflow covers single requests; batches aggregate them).
17. **Order transactions split** (`order_transactions`): vendor vs driver amount view per order.
18. **Vendor filters**: structured filter definitions for storefront search (legacy: free-form map).
19. **Stories** (66 vendor story videos): moderation list (tiny).
20. **Live payment gateways (D8c)**: Paytm checksum/initiate/callback, Stripe intents, Braintree/
    PayPal, Razorpay order API + 8 more settings pages (payfast/paystack/flutterwave/mercadopago/
    xendit/midtrans/orangepay/cod/wallet toggles). Secrets stay in env.

## E. PENDING — engagement & safety (tiny-to-small)

21. **Complaints** (6): list/edit + `complaint_notification` action.
22. **SOS** (39): safety alert inbox (+ ride link).
23. **Support chat**: `chat_admin` (14 threads) + driver/store/provider/worker threads — inbox UI.
24. **Onboarding slides** (`on_boarding` 15): app content CRUD.
25. **Homepage/footer templates + app banners settings page**: storefront CMS blocks
    (our banners module covers data; template assembly pending).
26. **Manual email composer** (`send-email` route + view): one-off admin emails.
27. **Scheduled-order notifications** (`scheduleOrderNotification`): settings + worker.
28. **Live maps** (`map/multivendor|parcel|rental|cab`): needs Maps key + app data.
29. **Admin locale switcher** (`lang/change`): panel i18n (en-only now).
30. **Maintenance mode UI**: map to Laravel `down` command (tiny).
31. **Business-model / special-offer / adminCommission / deliveryCharge / wallet settings keys**:
    extend `config/settings.php` groups as modules land.
32. **Advertisement extras**: public request queue (`advertisements.request`), detail view,
    per-ad chat, send-notification action.

## F. DROPPED on purpose (documented, do not rebuild)

- `store-firebase-service` (any authed admin overwrites Firebase creds — security hole).
- GET deletes, JSON-in-URL bulk deletes, role-delete-cascades-users.
- Public registration/password-reset on the panel; `Auth::routes()` surface.
- FCM raw-curl senders (TLS disabled, `die()`); Spatie-exception dead import.
- Stored review aggregates; parallel add-on arrays; triple location fields; device fcmTokens.
- `None` statuses; money-as-string; hardcoded super-admin `id==1`.
- Dead `driverChat()` (no route) unless chat milestone wants it.

## G. OUTSIDE admin-panel (separate rebuild tracks)

- **Store Panel, Website Panel, Landing Panel** (Laravel apps under `web/`), **apps/** (Flutter),
  **apps.zip / web-admin.zip** (archives — verify duplicates before deleting).
- **Documentation.zip** (18 MB — read before starting apps track), **Order Tracking Firebase
  Function.zip** (cloud function to port as queued job + FCM), **Firebase Indexing.zip**
  (Firestore indexes — obsolete post-MySQL, keep for reference), **Firestore Demo Auth import.zip**.
- **api.php**: only a sanctum stub — the whole customer/store/rider API is a future track that
  consumes this panel's MySQL (auth, catalog, orders, payouts, wallet, chat, reviews).
- Remaining Firestore collections with no admin UI at all (app-side data, import with owners):
  users (618), favorite_*, heartbeat, documents_verify (see B4), withdraw_method (56 — needed by
  payouts D8c), providers_workers (see C10), cms extras, vehicle/location pings.
