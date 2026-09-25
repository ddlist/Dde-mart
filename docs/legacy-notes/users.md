# Users — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 from the previous admin panel (read-only reference):
`UserController.php` (681 lines), `resources/views/{users,admin_users}/*`.

## Legacy behavior

- **Two concepts in one controller:** `users/*` views (app customers — thin shells passing only
  an `id`; real data lives in Firestore) and `admin-users` (staff with `role_id`).
- **Staff list:** excludes `id = 1`, masks emails (`a**********@domain`) via `shortEmail()`.
- **Staff create:** name / email / password (min 8) + confirmation / role. No unique-email rule.
- **Staff update:** user `id 1` forcibly keeps role `1`; password change requires `old_password`
  verified with `password_verify`. No unique-email rule.
- **Staff delete:** `json_decode($id)` single-or-bulk via GET; no self-delete or last-admin guard.
- **Profile:** self edit (name/email, optional password + old password check).
- **Payouts:** `payToUser` / `checkPayoutStatus` (+ PayPal/Stripe/Razorpay/Flutterwave helpers)
  live in UserController — gateway logic coupled to user admin.

## Bugs / smells fixed in the DDE-Mart rebuild

1. Payout gateway code in UserController → moves to Finance (D8) as env-driven adapters.
2. Delete via GET, bulk-delete via JSON-in-URL → `DELETE` verb, single resource route.
3. No self-delete / last-super-admin protection → both blocked explicitly.
4. Hardcoded `id == 1` super-admin → `roles.is_super` flag (already in D2).
5. Missing `unique:users,email` rules → added (was possible to duplicate staff emails).
6. `users/*` customer pages depend on Firestore app data → deferred until app-user data
   strategy lands (not staff scope). Email masking dropped for staff (admins need real emails).

## Fresh design (implemented in D5)

- `Admin\UserController`: `index` (search name/email + role filter + paginate 15), `create/store`,
  `edit/update`, `destroy`. Guards: can't delete self, can't delete last super-admin,
  can't change own role, can't remove super flag path (no such input anyway).
- `ProfileController` (self): edit name/email, change password with `current_password` rule.
- Routes `admin.users.*` under `admin.can:users,<ability>`; `admin.profile.*` under `auth`.
