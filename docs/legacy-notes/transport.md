# Transport verticals — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): Parcel/Rental/Vehicle/Ride controllers (shells),
`parcel_categories` (11), `parcel_weight` (5 slabs), `parcel_orders` (33),
`rental_packages` (12), `rental_vehicle_type` (7+), `rental_orders` (34).

## Legacy behavior

- All shells over Firestore. Parcel: categories + weight slabs (title + delivery_charge) +
  per-type coupons (unified already via coupon scope) + orders with sender/receiver snapshots,
  distance, `paymentCollectByReceiver` flag. Rental: packages (base fare + included hrs/km +
  extra rates) + vehicle types + orders with odometer readings, OTP (`otpCode`), start/end times.
- Statuses reuse the food vocabulary (Placed/Accepted/Shipped/In Transit/Completed/Cancelled).

## Fresh design (this milestone)

- `parcel_categories`, `parcel_weights` (title/max_kg/charge), `parcel_orders` (+history).
- `rental_vehicle_types`, `rental_packages`, `rental_orders` (+history). Fleet units: no legacy
  collection → skipped until fleet milestone (noted).
- Canonical machines: parcel placed→accepted→shipped→completed (+cancelled/rejected);
  rental placed→accepted→ongoing→completed (+cancelled/rejected). Numbers `P-DDE-…`/`R-DDE-…`.
- Shared `transport.*` gates (also used by rides + on-demand next).
- Rental discounts: covered by coupon scope=rental (no separate table — fixes a legacy duplicate).
