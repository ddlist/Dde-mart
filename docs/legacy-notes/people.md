# People & verification — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): Driver/Owner/Deliveryman/Document controllers (shells),
`users` (618, roles customer|driver|provider|vendor), `documents_verify` (129:
type driver|restaurant, documents[] with front/backImage + per-doc status),
`documents` master (5).

## Legacy behavior

- Drivers/deliverymen/fleet/owners: separate Firestore-shell view sets, all browser-driven.
  `driverChat()` exists with no route (dead). Fleet drivers = company-owned subset.
- Verification: per-user `documents_verify` docs holding per-document images + status strings;
  29 ids match `users`, rest match vendors (type=restaurant).
- App users (618) + providers/vendors roles live in `users`; owner accounts are app-side.

## Fresh design (this milestone)

- `drivers` (kind ride|delivery|fleet; store link for deliverymen; status machine
  pending→active⇄suspended + rejected), `document_types` master (driver|store|owner),
  polymorphic `verifications` (front/back paths, status, reviewer note).
- Driver detail page: profile + document review (approve/reject per doc) + transitions.
- Importer: role=driver users → drivers; `documents` → types; `documents_verify` → verifications
  (owner resolved driver→store; doc type by legacy id).
- Owners directory + wallets + referrals: next batch (same pattern).

## Batch 2 — owners, wallets, referrals

- `users` roles: driver 244 / vendor 236 / customer 114 / provider 20. 53/56 vendor.author ids
  match `users` keys → owners ARE role=vendor accounts; `stores.owner_id` links them.
- Wallet rows (1983): signed amount, isTopUp flag, method, payment_status, note, order link,
  `transactionUser` audience + legacy `user_id`. Imported as read-only ledger
  (`wallet_entries`, owner unresolved → legacy ref kept); adjustments come later.
- Referrals (2464): bare `{referralBy, referralCode}` pairs → read-only list.