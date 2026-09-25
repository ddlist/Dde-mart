# Geo + Content — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): `SettingsController` (304 lines of view-shell methods),
`NotificationController` (FCM broadcast), other geo/content shells, Firestore shapes
(`zone` 1, `settings` 46 keys, `notifications` 2, `dynamic_notification` 40,
`email_templates` 9, `cms_pages` 1, `on_boarding` 15, `documents` 5).

## Legacy behavior

- **Settings:** ~20 view methods (social, globals, cod, stripe, paypal, razorpay, wallet,
  deliveryCharge, languages…) each rendering a Firestore-backed JS form. **Gateway secrets live
  in Firestore settings docs** readable by client apps.
- **Zone:** single doc `{name, latitude, longitude, area, publish}` — service circle.
- **Broadcast:** `broadcastnotification` posts one FCM topic message (vendor|customer|driver|
  provider|worker) via raw curl, TLS verification disabled, `die()` on error, `topic` placed
  inside `message` object (arguably misplaced — FCM expects it at top level of message… actually
  v1 API does accept `message.topic`; the payload shape is roughly right, transport is not).
- **Dynamic notifications:** 40 `{service_type, type, subject, message}` template docs consumed
  by apps directly.
- **Email templates:** 9 `{type, subject, message, isSendToAdmin}` docs; sending via
  `SendEmailController` shell + `DynamicEmail` mailable.
- **CMS:** single `cms_pages` doc + terms/privacy shells. **Languages:** Firestore locale docs
  edited through settings views.

## Bugs / smells fixed in the DDE-Mart rebuild

1. Secrets in Firestore settings → gateway credentials live ONLY in `.env`; settings UI holds
   non-secret ops knobs.
2. FCM raw-curl + disabled TLS + `die()` → `kreait/laravel-firebase` (verified TLS) behind
   `FcmSender`; graceful "not configured" without credentials; every send logged in `notifications`.
3. 40 loose template docs → `notification_templates` keyed (`order.placed`…) with `:placeholders`,
   rendered server-side on `OrderStatusChanged`.
4. Zone as single doc → `zones` table (circles now, polygons later).
5. Triple email/CMS/terms shells → `email_templates` + `pages` tables.
6. Deferred with owners: onboarding slides, verification documents, popular destinations, car
   makes/models (ride domain), wallet UI — noted, not built.

## Fresh design (D9)

- Tables: `zones`, `settings` (key/value + `Setting` helper w/ config defaults),
  `notifications` (send log), `notification_templates`, `email_templates`, `pages`, `languages`.
- Gates: `content.*` (extended with create/delete — additive, safe).
- `SendOrderStatusPush` listener renders `order.{status}` template → topic `customer` (+ record).
- Broadcast form: audience topic + subject + body → `FcmSender::sendToTopic()` → status sent/failed.
