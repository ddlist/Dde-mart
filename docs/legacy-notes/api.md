# API v1 — design spec (original)

Base: `dde-mart/admin-panel` (same Laravel app, same MySQL). No separate deployable:
`routes/api.php` under `/api/v1`, Sanctum personal tokens, per-audience abilities.

## Conventions

- Versioned prefix `/api/v1`; JSON only (`Accept: application/json` enforced).
- Envelope: `{ "data": …, "meta": {…} }` for collections (paginated),
  `{ "data": … }` for singles, errors as `{ "message", "errors": {…} }` (422 native shape).
- Auth: `POST /api/v1/auth/register|login|otp/request|otp/verify|logout`, `GET /me`.
  Tokens carry abilities: `customer:*`, `driver:*`, `vendor:*` (+ `staff:*` reserved).
  OTP: 6-digit, 10-min TTL, 5/hr throttle per phone; log driver locally, SMS hook later.
- Pagination: `?page&per_page` (max 50). Filtering via query params, never POST bodies.
- Rate limits: `throttle:api` default; auth endpoints stricter (`throttle:10,1`).
- Images: absolute URLs via `Images::url()` (already remote-capable).

## Audiences & guards

- `auth:sanctum` + `ability:customer` style middleware (`abilities` middleware ships
  with Sanctum). Staff panel session auth stays cookie-based, untouched.
- App users live in audience tables (`customers` …) — NOT staff `users`.
  v1 ships customers; driver/vendor/owner accounts arrive with their surfaces.

## Endpoint plan (this track)

1. Foundation (this batch): auth + me + catalog browse (sections, categories,
   products incl. addons/attributes, stores incl. zones, search).
2. Commerce: cart quote (price/tax/coupon math server-side), checkout → orders,
   coupon validate, my-orders + timeline, reviews submit, wallet read.
3. Engagement: push-token register, notifications feed, pages/languages/banners/ads,
   settings subset (non-secret), onboarding slides.
4. Driver/vendor surfaces + uploads + documents submit (with their milestones).

## Deliberately NOT in v1

- Passwords for imported Firebase users (unexportable) → OTP-first login.
- Live order dispatch/assignment (staff panel owns it), Braintree cards (storefront later),
  chat send (read endpoints first; send arrives with apps messaging milestone).
