# Legacy notes — behavior specs (study-only, no copied code)

Each file here distills ONE legacy module's *behavior* (routes, flows, validation,
edge cases, bugs found) so the rebuild can reimplement it cleanly. Never paste
legacy code into these notes or into the app.

## Modules to spec (from previous panel inventory)

- `dashboard.md` — HomeController summary cards + charts (TODO D4)
- `auth-rbac.md` — login, roles, permissions (TODO D2)
- `users.md`, `stores-vendors.md`, `drivers.md` (TODO D5–D6)
- `orders.md` — food/parcel/rental/ondemand pipelines + status flows (TODO D7)
- `catalog.md` — categories/brands/attributes/items/banners/sections (TODO D6)
- `finance.md` — transactions/payouts/coupons/tax/plans + payment SDKs (TODO D8)
- `geo-content.md` — zones/maps/CMS/notifications/emails/languages (TODO D9)
- `data-import.md` — legacy SQL dump → fresh schema mapping plan

## Template

```md
# <Module>
- Legacy source: <controller + views + routes>
- Purpose / flows:
- Validation & edge cases:
- Bugs fixed in rebuild:
- Fresh schema (dde_mart_*):
- Rebuild checklist:
```
