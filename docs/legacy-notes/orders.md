# Orders — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): `OrderController` (118 lines), `Parcel/Rental/OnDemand/OrderReview/
TransactionController` (shells), Firestore `collections.json` (`vendor_orders` 133, `parcel_orders`
33, `rental_orders` 34, `provider_orders` 77, `rides` 63, `order_transactions` 1).

## Legacy behavior

- **Controllers are shells** returning Firestore-driven Blade views (id passed to JS). Real state
  lives in Firestore; status flips happen browser-side with no transition validation.
- **Food order doc:** customer snapshot (`author`), vendor snapshot (`vendor`+`vendorID`), address
  snapshot, `products[]` (name/price/qty/photo snapshot + `variant_info` + `extras[]`), money
  (`discount`, `specialDiscount`, `deliveryCharge`, `tip_amount`, `taxSetting`, `payment_method`),
  `couponCode/Id`, `status`, `section_id`, `scheduleTime`, `estimatedTimeToPrepare`, `takeAway`.
- **Status vocabulary (all collections):** Order Placed, Order Accepted, Order Shipped, In Transit,
  Driver Pending, Driver Rejected, Order Completed, Order Cancelled, Order Rejected, Order Ongoing, None.
- **Notifications:** `OrderController@sendNotification` posts FCM v1 via raw curl with
  `CURLOPT_SSL_VERIFYHOST=0` + `CURLOPT_SSL_VERIFYPEER=false` (MITM-able), `die()` on curl error,
  hardcoded `data.status=done`. Credentials from `storage/app/firebase/credentials.json`.
- **Print:** `orders.print` view (browser print of Firestore doc).

## Bugs / smells fixed in the DDE-Mart rebuild

1. No transition validation (any status → any status, incl. resurrecting cancelled orders).
2. FCM curl disables TLS verification + `die()` in controller → D9 uses verified HTTP client.
3. `triggerDelivery` / `trigger_delevery` duplicate flags; `None` status; money as strings.
4. Parcel/rental/ride orders crammed around the same views with per-type `if`s → separate
   domain modules later; `orders.type` column reserved (`food` now).
5. Order financial records deletable conceptually → rebuild has **no delete**; cancel instead.
6. Review aggregates stored on products → computed when reviews land.

## Fresh design (D7, food orders)

- Tables: `orders` (number `DDE-YYYYMM-XXXXX`, type, customer snapshot, vendor_id reserved,
  section FK, address JSON, money decimals, coupon, notes, status, scheduled/ETA, timestamps),
  `order_items` (snapshot rows + extras JSON), `order_status_history` (from→to, changed_by, note).
- Canonical statuses: `placed → accepted → preparing → shipped → completed`, plus
  `cancelled` (from placed/accepted) and `rejected` (from placed/accepted). Completed/cancelled/
  rejected are terminal. D10 maps legacy names onto these (In Transit→shipped, Driver *→accepted
  + note, None→placed, Ongoing→accepted).
- `App\Services\OrderStatus::transition()` enforces the map, writes history, fires
  `OrderStatusChanged` (listener logs for now — FCM seam for D9).
- Admin UI: filterable list + detail with timeline + transition buttons. No destroy route.
- Gates reuse the pre-declared `orders.view / orders.edit` abilities.
