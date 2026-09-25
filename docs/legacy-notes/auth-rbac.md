# Auth + RBAC — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 from the previous admin panel (read-only reference):
`app/Http/Controllers/Auth/*`, `RoleController.php`, `app/Models/{User,Role,Permission}.php`,
`app/Http/Middleware/{PermissionMiddleware,CheckUserRoleMiddleware}.php`,
`routes/web.php` (Auth::routes, role routes), `resources/views/auth/*`.

## Legacy behavior

- **Login:** stock `laravel/ui` (`AuthenticatesUsers` trait), `guest` middleware, redirect to
  `RouteServiceProvider::HOME` after login. `Auth::routes()` also exposes **public registration,
  password reset, email verification** — all enabled by default.
- **User:** `name, email, password, role_id` (single role FK, no pivot table).
- **Role:** table literally named `role` (singular), single column `role_name`.
- **Permission:** table `permissions`, one row per `(role_id, permission-group, routes)` triple,
  e.g. group `home-page` + route `homepageTemplate`. Built from checkbox matrix in role forms.
- **Enforcement:** `permission:<group>,<route>` middleware on route groups (2 params); a
  `CheckUserRoleMiddleware` stuffs role name + route list into session for the sidebar.
- **Role delete:** deletes the role's permissions AND **all users in that role**, accepts ids via
  GET (`role/delete/{id}`).

## Bugs / smells fixed in the DDE-Mart rebuild

1. `Role` and `Permission` extend `Authenticatable` instead of `Model` — wrong base class.
2. Deleting a role deletes every user in it (data loss) — rebuild **blocks** delete while users
   are attached and uses `DELETE` verb (CSRF-safe) instead of GET.
3. `PermissionMiddleware` imports a Spatie exception class from a package that isn't installed.
4. `CheckUserRoleMiddleware` fatals (`->roleName` on null) for users with no role.
5. Singular `role` table; no validation on role name (empty names allowed).
6. Public registration + password-reset enabled on an admin panel — rebuild is **login-only**.

## Fresh design (implemented in D2)

- Tables: `roles (id, name unique, slug unique, is_super, timestamps)`,
  `permissions (id, role_id FK cascade, group, ability, timestamps, unique(role_id,group,ability))`,
  `users.role_id nullable, nullOnDelete`.
- Catalog: `config/admin_permissions.php` — groups × abilities (`view/create/edit/delete`);
  stored key = `group.ability` (e.g. `roles.edit`).
- Middleware alias `admin.can:<group>[,<ability>]`: guests → login (via `auth`), wrong role →
  `403` view. `roles.is_super` bypasses everything. No session stuffing.
- Login-only auth (`/login`, `POST /login`, `POST /logout`), throttled, intended-redirect.
- Seeder: `Super Admin` role (`is_super`) + `admin@dde-mart.local`. Super role can't be
  edited down or deleted.
