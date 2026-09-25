# Storefront — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): Website Panel `routes/web.php` (~200 routes),
35 controllers (Product 887 lines, Parcel 767, Checkout 698, Rental 694, payment
flows 600+), ~30 view dirs; static Bootstrap landing page.

## Legacy behavior

- Full server-rendered storefront: cookie-driven vertical homepages (section_id +
  service_type pick 1 of 6 home shells; forced set-location gate), session carts,
  Firebase-auth-via-Ajax, CMS/FAQ/contact, favorites, offers, gift cards, dine-in,
  per-vertical order lists.
- Checkout/payment logic DUPLICATED per vertical × per gateway (food/parcel/rental/
  on-demand/giftcard/wallet/extra-charges × stripe/paypal/razorpay/paystack/mercadopago).
- Same credential-upload hole (`store-firebase-service`) and CSRF-exempt payment posts.

## Fresh design (dde-mart website layer, same app)

- `routes/shop.php` + `Shop/` controllers + `shop/` views + customer session auth (next).
  Thin over existing models/services: `CartQuote` prices everything, gateway drivers
  charge, status machines advance. ONE checkout parameterized by vertical.
- Session (not cookie) location + section context. Landing reskin deferred (static page).
