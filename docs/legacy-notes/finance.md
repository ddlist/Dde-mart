# Finance + Promotions — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): `Coupon/Payment/GiftCard/SubscriptionPlan/Tax/Currency/
*Payout*/AdminPaymentsController` (mostly shells), `UserController@pay*` gateway code,
Firestore `collections.json` field shapes (values only, no code).

## Legacy behavior

- **Coupons** (`coupons` 44, `parcel_coupons` 13, `rental_coupons` 6, `providers_coupons` 5,
  `promos` 18): code, Percentage|Fix Price discount, expiry, vendor/section scoping, isPublic.
  Four near-identical collections per vertical + a parallel `promos` twin.
- **Advertisements** (31): vendor-paid promos — cover/profile images, video, schedule window,
  priority, showRating/showReview, `status` + `paymentStatus` + `isPaused` (three overlapping flags).
- **Gift cards** (3): title/message/image/expiryDay/isEnable (no amount on the card itself).
- **Tax** (21): country/title/type/tax/enable/sectionId. **Currencies** (2): full money format info.
- **Plans** (20): name/price/type(free)/expiryDay/itemLimit/orderLimit/commission flag/features{}.
- **Payouts** (`payouts` 167, `driver_payouts` 20): amount/vendorID/note/adminNote/paidDate +
  `paymentStatus` ∈ None|Pending|Reject|Success. Execution lives in `UserController@payToUser`
  (PayPal/Stripe/Razorpay/Flutterwave, secrets passed per-request from settings docs).
- **Wallets** (`wallet` 1983 rows): belong to app users/vendors — no admin owners module yet.

## Bugs / smells fixed in the DDE-Mart rebuild

1. Four coupon collections + promos twin → ONE `coupons` table with `scope` (food|parcel|rental|all).
2. Ads' three overlapping flags → single `status` (pending|approved|active|paused|rejected|expired)
   + `payment_status` (pending|paid). Expiry derived from dates, not stored.
3. Money/percentages as strings, `None` statuses → typed decimals + canonical statuses.
4. Gateway secrets flowing per-request from settings docs + raw curl in controllers → `PaymentGateway`
   seam (interface + config-driven manager); live execution deferred to D8c (needs real credentials).
5. Wallet ledger without owner modules → deferred; payouts modeled as **requests workflow**
   (pending→approved→paid / rejected) that the ledger will reference later.

## Fresh design (D8)

- Tables: `coupons` (+`calculateDiscount()` helper + usage counters), `advertisements`,
  `gift_cards`, `taxes`, `currencies` (single default), `subscription_plans` (features JSON),
  `payout_requests` (polymorphic-by-string requester, method_details JSON, handled_by FK).
- Gates: `promotions.*` for coupons/ads/gifts; `finance.*` for tax/currency/plans/payouts
  (config extended with finance.create/delete — additive, safe for existing roles).
- No deletes for payout_requests (financial trail); cancel via reject.
