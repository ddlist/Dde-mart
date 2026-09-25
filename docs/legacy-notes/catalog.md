# Catalog — legacy behavior spec (study-only, no copied code)

Studied 2026-09-25 (read-only): `Category/Brand/Food/Attribute/Banner/SectionController.php`
(~22-line shells), `resources/views/{categories,brands,items,attributes,banners,section}/*`,
previous SQL dump (auth tables only — no catalog), Firestore `collections.json`
(9.9 MB export; field names only, no code).

## Legacy behavior

- **Controllers are empty shells** (`index/edit/create` returning views with an id). All data +
  writes happen **browser-side via Firebase JS SDK** straight into Firestore. No server
  validation, no RBAC enforcement on writes, no transactions.
- **Collections + fields:**
  - `sections` (8): name, serviceType, color, image, isActive, delivery_charge, adminCommision,
    dine_in_active, theme, markerIcon… (verticals: food/grocery/pharmacy/rides/parcel…)
  - `vendor_categories` (24): title, description, photo, section_id, order, publish,
    show_in_homepage, review_attributes[] (ids)
  - `brands` (9): title, photo, sectionId, is_publish
  - `vendor_products` (573): name, description, price, disPrice, quantity, categoryID, brandID,
    section_id, vendorID, photo + photos[], veg/nonveg, takeawayOption, calories/proteins/fats/grams,
    product_specification, addOnsTitle[] + addOnsPrice[] (parallel arrays!), item_attribute (mostly null),
    publish, reviewsCount/reviewsSum (aggregates)
  - `vendor_attributes` (7) / `review_attributes` (10): bare `{id, title}` axes
  - `banner_items` (27): title, photo, web_banner, sectionId, redirect_type/id, position,
    set_order, is_publish
  - `advertisements` (31): vendor-promoted interstitial format → deferred to Promotions (D8)

## Bugs / smells fixed in the DDE-Mart rebuild

1. Browser-direct Firestore writes → server-side CRUD with FormRequests + `catalog.*` gates.
2. Add-ons as parallel arrays (`addOnsTitle[i] ↔ addOnsPrice[i]`) → `product_addons` rows.
3. `reviewsCount/reviewsSum` stored + drift-prone → computed when reviews land (D7+).
4. No slugs, no uniqueness, no image lifecycle → slugs auto-generated + unique, uploads stored
   on `public` disk with old-file cleanup.
5. Rides/parcel/rental collections share the panel but are separate domains → only
   food/grocery-style catalog here; vehicle/parcel types belong to their modules (D7+).
6. `advertisements` (paid vendor promos) → D8 Promotions, not catalog.

## Fresh design (D6, three slices)

- Tables: `sections`, `categories (→section)`, `brands (→section)`, `attributes`,
  `attribute_values (→attribute)`, `products (→section/category/brand, vendor_id reserved)`,
  `product_addons (→product)`, `product_attribute_value` pivot, `banners`.
- Table naming: plain Laravel plurals (matches `users/roles/permissions` already shipped;
  documented divergence from the `dde_mart_*` idea in AGENT.md §4).
- Slice 1: sections + categories + brands. Slice 2: attributes + products (+addons, +pivot).
  Slice 3: banners. Each with index/form views, `catalog.*` gates, image uploads, delete-blocks
  when children exist (products attached → block; addons cascade with product).
- D10 data import will map Firestore docs → these tables (ids preserved in a `legacy_id` column
  only where needed — TBD in `data-import.md`).
