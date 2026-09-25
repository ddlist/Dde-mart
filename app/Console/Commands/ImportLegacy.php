<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Disbursement;
use App\Models\DocumentType;
use App\Models\Driver;
use App\Models\FilterPreset;
use App\Models\Owner;
use App\Models\ParcelCategory;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ProviderBooking;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\ProviderWorker;
use App\Models\Referral;
use App\Models\GiftCard;
use App\Models\GiftOrder;
use App\Models\ItemReview;
use App\Models\RentalOrder;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Section;
use App\Models\Store;
use App\Models\Story;
use App\Models\CabType;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Complaint;
use App\Models\Destination;
use App\Models\OnboardingSlide;
use App\Models\Ride;
use App\Models\SosAlert;
use App\Models\TableBooking;
use App\Models\PlanSubscription;
use App\Models\ReviewCriterion;
use App\Models\Verification;
use App\Models\WalletEntry;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/*
 * DDE-Mart Admin — legacy Firestore importer (original command).
 *
 * Reads a `collections.json` export (see Firebase Import Export Collections.zip)
 * and upserts catalog + coupon documents into MySQL, keyed by `legacy_id`.
 * Idempotent: re-runs update the same rows. Cross-collection references resolve
 * through in-memory legacy→new id maps (import order: sections first).
 *
 *   php artisan ddemart:import --file=collections.json --only=sections,categories --dry-run
 *   php artisan ddemart:import --file=collections.json --limit=25
 *
 * Explicitly out of scope (owner modules don't exist yet): orders, payouts/wallets,
 * app users, parcel/rental entities. See docs/legacy-notes/reports-import.md.
 * People milestone: role=driver users → drivers; `documents` → types;
 * `documents_verify` → verifications (owner resolved driver→store).
 */
class ImportLegacy extends Command
{
    protected $signature = 'ddemart:import
        {--file= : Path to the collections.json export}
        {--only= : Comma list of sections,categories,brands,products,coupons (default: all)}
        {--limit= : Max documents per collection (default: no limit)}
        {--dry-run : Parse and report without writing}';

    protected $description = 'Import legacy Firestore catalog + coupons into MySQL (idempotent)';

    protected array $maps = [];

    protected array $stats = [];

    public function handle(): int
    {
        $file = $this->option('file');

        if (! $file || ! is_file($file)) {
            $this->error('Pass --file=path/to/collections.json');

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);

        if (! isset($data['__collections__'])) {
            $this->error('Not a collections.json export (missing __collections__).');

            return self::FAILURE;
        }

        $collections = $data['__collections__'];
        $only = $this->option('only')
            ? array_map('trim', explode(',', (string) $this->option('only')))
            : ['zones', 'sections', 'categories', 'brands', 'stores', 'products', 'coupons', 'drivers', 'document-types', 'verifications', 'owners', 'wallets', 'referrals', 'gift-cards', 'review-criteria', 'reviews', 'subscriptions', 'gift-orders', 'stories', 'presets', 'parcel-categories', 'parcel-weights', 'parcel-orders', 'rental-types', 'rental-packages', 'rental-orders', 'car-makes', 'car-models', 'cab-types', 'destinations', 'rides', 'providers', 'provider-categories', 'provider-services', 'provider-workers', 'provider-bookings', 'table-bookings', 'complaints', 'sos', 'chats', 'onboarding'];
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dry = (bool) $this->option('dry-run');

        $this->buildMaps($collections);

        $jobs = [
            'zones' => [$collections['zone'] ?? [], fn ($d) => $this->importZone($d, $dry)],
            'sections' => [$collections['sections'] ?? [], fn ($d) => $this->importSection($d, $dry)],
            'categories' => [$collections['vendor_categories'] ?? [], fn ($d) => $this->importCategory($d, $dry)],
            'brands' => [$collections['brands'] ?? [], fn ($d) => $this->importBrand($d, $dry)],
            'owners' => [$collections['users'] ?? [], fn ($d) => $this->importOwner($d, $dry)],
            'stores' => [$collections['vendors'] ?? [], fn ($d) => $this->importStore($d, $dry)],
            'products' => [$collections['vendor_products'] ?? [], fn ($d) => $this->importProduct($d, $dry)],
            'coupons' => [$collections['coupons'] ?? [], fn ($d) => $this->importCoupon($d, $dry)],
            'drivers' => [$collections['users'] ?? [], fn ($d) => $this->importDriver($d, $dry)],
            'document-types' => [$collections['documents'] ?? [], fn ($d) => $this->importDocumentType($d, $dry)],
            'verifications' => [$collections['documents_verify'] ?? [], fn ($d) => $this->importVerification($d, $dry)],
            'wallets' => [$collections['wallet'] ?? [], fn ($d) => $this->importWallet($d, $dry)],
            'referrals' => [$collections['referral'] ?? [], fn ($d) => $this->importReferral($d, $dry)],
            'gift-cards' => [$collections['gift_cards'] ?? [], fn ($d) => $this->importGiftCard($d, $dry)],
            'review-criteria' => [$collections['review_attributes'] ?? [], fn ($d) => $this->importCriterion($d, $dry)],
            'reviews' => [$collections['items_review'] ?? [], fn ($d) => $this->importReview($d, $dry)],
            'subscriptions' => [$collections['subscription_history'] ?? [], fn ($d) => $this->importSubscription($d, $dry)],
            'gift-orders' => [$collections['gift_purchases'] ?? [], fn ($d) => $this->importGiftOrder($d, $dry)],
            'stories' => [$collections['story'] ?? [], fn ($d) => $this->importStory($d, $dry)],
            'presets' => [$collections['vendors'] ?? [], fn ($d) => $this->importPreset($d, $dry)],
            'parcel-categories' => [$collections['parcel_categories'] ?? [], fn ($d) => $this->importParcelCategory($d, $dry)],
            'parcel-weights' => [$collections['parcel_weight'] ?? [], fn ($d) => $this->importParcelWeight($d, $dry)],
            'parcel-orders' => [$collections['parcel_orders'] ?? [], fn ($d) => $this->importParcelOrder($d, $dry)],
            'rental-types' => [$collections['rental_vehicle_type'] ?? [], fn ($d) => $this->importRentalType($d, $dry)],
            'rental-packages' => [$collections['rental_packages'] ?? [], fn ($d) => $this->importRentalPackage($d, $dry)],
            'rental-orders' => [$collections['rental_orders'] ?? [], fn ($d) => $this->importRentalOrder($d, $dry)],
            'car-makes' => [$collections['car_make'] ?? [], fn ($d) => $this->importCarMake($d, $dry)],
            'car-models' => [$collections['car_model'] ?? [], fn ($d) => $this->importCarModel($d, $dry)],
            'cab-types' => [$collections['vehicle_type'] ?? [], fn ($d) => $this->importCabType($d, $dry)],
            'destinations' => [$collections['popular_destinations'] ?? [], fn ($d) => $this->importDestination($d, $dry)],
            'rides' => [$collections['rides'] ?? [], fn ($d) => $this->importRide($d, $dry)],
            'providers' => [$collections['users'] ?? [], fn ($d) => $this->importProvider($d, $dry)],
            'provider-categories' => [$collections['provider_categories'] ?? [], fn ($d) => $this->importProviderCategory($d, $dry)],
            'provider-services' => [$collections['providers_services'] ?? [], fn ($d) => $this->importProviderService($d, $dry)],
            'provider-workers' => [$collections['providers_workers'] ?? [], fn ($d) => $this->importProviderWorker($d, $dry)],
            'provider-bookings' => [$collections['provider_orders'] ?? [], fn ($d) => $this->importProviderBooking($d, $dry)],
            'table-bookings' => [$collections['booked_table'] ?? [], fn ($d) => $this->importTableBooking($d, $dry)],
            'complaints' => [$collections['complaints'] ?? [], fn ($d) => $this->importComplaint($d, $dry)],
            'sos' => [$collections['SOS'] ?? [], fn ($d) => $this->importSos($d, $dry)],
            'onboarding' => [$collections['on_boarding'] ?? [], fn ($d) => $this->importSlide($d, $dry)],
        ];

        // Chats are nested per-audience (thread subcollections) — handled explicitly.
        if (in_array('chats', $only, true)) {
            foreach ([
                'admin' => $collections['chat_admin'] ?? [],
                'driver' => $collections['chat_driver'] ?? [],
                'store' => $collections['chat_store'] ?? [],
                'provider' => $collections['chat_provider'] ?? [],
                'worker' => $collections['chat_worker'] ?? [],
            ] as $audience => $threads) {
                $count = 0;

                foreach ($threads as $id => $thread) {
                    if ($limit && $count >= $limit) {
                        break;
                    }

                    $this->importChatThread($audience, array_merge($thread, ['_legacy_id' => "{$audience}:{$id}"]), $dry);
                    $count++;
                }

                $this->line(sprintf('%-10s scanned=%d created=%d updated=%d skipped=%d',
                    "chat-{$audience}", $count,
                    $this->stats['chats']['created'] ?? 0,
                    $this->stats['chats']['updated'] ?? 0,
                    $this->stats['chats']['skipped'] ?? 0,
                ));
            }
        }

        foreach ($jobs as $name => [$docs, $import]) {
            if (! in_array($name, $only, true)) {
                continue;
            }

            $count = 0;

            foreach ($docs as $id => $doc) {
                if ($limit && $count >= $limit) {
                    break;
                }

                $import(array_merge($doc, ['_legacy_id' => $id]));
                $count++;
            }

            $this->line(sprintf('%-10s scanned=%d created=%d updated=%d skipped=%d',
                $name, $count,
                $this->stats[$name]['created'] ?? 0,
                $this->stats[$name]['updated'] ?? 0,
                $this->stats[$name]['skipped'] ?? 0,
            ));
        }

        if ($dry) {
            $this->info('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }

    /** Preload legacy_id → id maps so references resolve on re-runs too. */
    protected function buildMaps(array $collections): void
    {
        foreach (['zones' => Zone::class, 'sections' => Section::class, 'categories' => Category::class, 'brands' => Brand::class, 'stores' => Store::class, 'drivers' => Driver::class, 'document_types' => DocumentType::class, 'owners' => Owner::class, 'gift_cards' => GiftCard::class, 'parcel_categories' => ParcelCategory::class, 'parcel_weights' => ParcelWeight::class, 'rental_types' => RentalVehicleType::class, 'rental_packages' => RentalPackage::class, 'car_makes' => CarMake::class, 'providers' => Provider::class, 'provider_categories' => ProviderCategory::class] as $key => $model) {
            $this->maps[$key] = $model::whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        }

        // Product doc `id` field → our id (reviews reference the field, not the key).
        $this->maps['products_by_field'] = Product::whereNotNull('legacy_ref')->pluck('id', 'legacy_ref')->all();
        $this->maps['criteria_titles'] = ReviewCriterion::whereNotNull('legacy_id')->pluck('title', 'legacy_id')->all();

        foreach ($collections['vendor_products'] ?? [] as $key => $doc) {
            if (! empty($doc['id']) && isset($this->maps['products'][$key])) {
                $this->maps['products_by_field'] += [$doc['id'] => $this->maps['products'][$key]];
            }
        }

        foreach ($collections['review_attributes'] ?? [] as $key => $doc) {
            $this->maps['criteria_titles'] ??= [];
            $this->maps['criteria_titles'] += [$key => $doc['title'] ?? $key];
        }

        // Product vendor links keyed by PRODUCT legacy_id (legacy vendorID is a string).
        $this->maps['product_vendors'] = [];
        foreach ($collections['vendor_products'] ?? [] as $id => $doc) {
            if (! empty($doc['vendorID'])) {
                $this->maps['product_vendors'][$id] = $doc['vendorID'];
            }
        }
    }

    protected function track(string $group, string $outcome): void
    {
        $this->stats[$group][$outcome] = ($this->stats[$group][$outcome] ?? 0) + 1;
    }

    /** Upsert by legacy_id; returns [model|null, created|updated|skipped]. */
    protected function upsert(string $model, string $legacyId, array $attributes): array
    {
        $existing = $model::where('legacy_id', $legacyId)->first();

        if ($existing) {
            $existing->update($attributes);

            return [$existing, 'updated'];
        }

        return [$model::create(array_merge($attributes, ['legacy_id' => $legacyId])), 'created'];
    }

    protected function uniqueSlug(string $model, string $base, string $legacyId): string
    {
        $slug = Str::slug($base) ?: Str::random(8);

        if (! $this->slugTaken($model, $slug, $legacyId)) {
            return $slug;
        }

        $i = 0;

        do {
            $candidate = substr($slug, 0, 180).'-'.substr($legacyId, 0, 6).($i ? "-{$i}" : '');
            $i++;
        } while ($this->slugTaken($model, $candidate, $legacyId));

        return $candidate;
    }

    protected function slugTaken(string $model, string $slug, string $legacyId): bool
    {
        return $model::where('slug', $slug)->where('legacy_id', '!=', $legacyId)->exists();
    }

    protected function ts(mixed $value): ?Carbon
    {
        if (is_array($value) && isset($value['value']['_seconds'])) {
            return Carbon::createFromTimestamp($value['value']['_seconds']);
        }

        return null;
    }

    protected function num(mixed $value, ?float $default = null): ?float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_numeric($value) ? (float) $value : $default;
    }

    protected function boolish(mixed $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    // -- Mappers ----------------------------------------------------------

    protected function importComplaint(array $doc, bool $dry): void
    {
        $status = match (strtolower((string) ($doc['status'] ?? 'open'))) {
            'resolved' => 'resolved', 'dismissed' => 'dismissed', default => 'open',
        };

        $attrs = [
            'title' => $doc['title'] ?? 'Complaint',
            'description' => $doc['description'] ?? null,
            'customer_name' => $doc['customerName'] ?? null,
            'driver_name' => $doc['driverName'] ?? null,
            'order_ref' => $doc['orderId'] ?? null,
            'status' => $status,
            'occurred_at' => $this->ts($doc['createdAt'] ?? null),
        ];

        if ($dry) {
            $this->track('complaints', Complaint::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Complaint::class, $doc['_legacy_id'], $attrs);
        $this->track('complaints', $outcome);
    }

    protected function importSos(array $doc, bool $dry): void
    {
        $latLng = $doc['latLong'] ?? [];

        if (is_string($latLng)) {
            $latLng = json_decode(str_replace("'", '"', $latLng), true) ?: [];
        }

        $attrs = [
            'order_ref' => $doc['orderId'] ?? null,
            'latitude' => $this->num($latLng['latitude'] ?? null),
            'longitude' => $this->num($latLng['longitude'] ?? null),
            'status' => strtolower((string) ($doc['status'] ?? '')) === 'completed' ? 'resolved' : 'open',
            'occurred_at' => $this->ts($doc['createdAt'] ?? null),
        ];

        if ($dry) {
            $this->track('sos', SosAlert::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(SosAlert::class, $doc['_legacy_id'], $attrs);
        $this->track('sos', $outcome);
    }

    protected function importChatThread(string $audience, array $doc, bool $dry): void
    {
        $attrs = [
            'audience' => $audience,
            'subject' => $doc['orderId'] ?? null,
            'last_message' => isset($doc['lastMessage']) ? substr((string) $doc['lastMessage'], 0, 500) : null,
            'status' => 'open',
        ];

        if ($dry) {
            $this->track('chats', ChatThread::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [$thread, $outcome] = $this->upsert(ChatThread::class, $doc['_legacy_id'], $attrs);
        $this->track('chats', $outcome);

        foreach ($doc['__collections__']['thread'] ?? [] as $msgId => $msg) {
            ChatMessage::updateOrCreate(
                ['legacy_id' => "{$doc['_legacy_id']}:{$msgId}"],
                [
                    'thread_id' => $thread->id,
                    'sender_ref' => $msg['senderId'] ?? null,
                    'body' => $msg['message'] ?? $msg['url'] ?? null,
                    'sent_at' => $this->ts($msg['createdAt'] ?? null),
                ]
            );
        }
    }

    protected function importSlide(array $doc, bool $dry): void
    {
        $attrs = [
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'description' => $doc['description'] ?? null,
            'image_path' => ($doc['image'] ?? null) ?: null,
            'audience' => strtolower((string) ($doc['type'] ?? 'customer')),
            'is_active' => true,
        ];

        if ($dry) {
            $this->track('onboarding', OnboardingSlide::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(OnboardingSlide::class, $doc['_legacy_id'], $attrs);
        $this->track('onboarding', $outcome);
    }

    protected function importCarMake(array $doc, bool $dry): void
    {
        $attrs = ['name' => $doc['name'] ?? $doc['_legacy_id'], 'is_active' => $this->boolish($doc['isActive'] ?? null)];

        if ($dry) {
            $this->track('car-makes', CarMake::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(CarMake::class, $doc['_legacy_id'], $attrs);
        $this->track('car-makes', $outcome);
        $this->maps['car_makes'][$doc['_legacy_id']] = CarMake::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importCarModel(array $doc, bool $dry): void
    {
        $attrs = [
            'car_make_id' => $this->maps['car_makes'][$doc['car_make_id'] ?? ''] ?? null,
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'is_active' => $this->boolish($doc['isActive'] ?? null),
        ];

        if ($dry) {
            $this->track('car-models', CarModel::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(CarModel::class, $doc['_legacy_id'], $attrs);
        $this->track('car-models', $outcome);
    }

    protected function importCabType(array $doc, bool $dry): void
    {
        $attrs = [
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(CabType::class, $doc['name'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'capacity' => is_numeric($doc['capacity'] ?? null) ? (int) $doc['capacity'] : null,
            'description' => $doc['description'] ?? $doc['short_description'] ?? null,
            'base_fare' => $this->num($doc['minimum_delivery_charges'] ?? null, 0),
            'per_km_fare' => $this->num($doc['delivery_charges_per_km'] ?? null, 0),
            'min_fare' => $this->num($doc['minimum_delivery_charges_within_km'] ?? null, 0),
            'icon_path' => ($doc['vehicle_icon'] ?? null) ?: null,
            'is_active' => $this->boolish($doc['isActive'] ?? null),
        ];

        if ($dry) {
            $this->track('cab-types', CabType::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(CabType::class, $doc['_legacy_id'], $attrs);
        $this->track('cab-types', $outcome);
    }

    protected function importDestination(array $doc, bool $dry): void
    {
        $attrs = [
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'latitude' => $this->num($doc['latitude'] ?? null),
            'longitude' => $this->num($doc['longitude'] ?? null),
            'image_path' => ($doc['image'] ?? null) ?: null,
            'is_active' => $this->boolish($doc['is_publish'] ?? null),
        ];

        if ($dry) {
            $this->track('destinations', Destination::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Destination::class, $doc['_legacy_id'], $attrs);
        $this->track('destinations', $outcome);
    }

    protected function importRide(array $doc, bool $dry): void
    {
        $customer = $doc['author'] ?? [];

        if (is_string($customer)) {
            $customer = ['name' => $customer];
        }

        $attrs = [
            'customer_name' => trim(($customer['firstName'] ?? '').' '.($customer['lastName'] ?? '')) ?: ($doc['authorID'] ?? $doc['_legacy_id']),
            'customer_phone' => $customer['phoneNumber'] ?? null,
            'driver_id' => $this->maps['drivers'][$doc['driverId'] ?? $doc['driverID'] ?? ''] ?? null,
            'source' => $doc['sourceLocationName'] ?? null,
            'destination' => $doc['destinationName'] ?? null,
            'distance_km' => $this->num($doc['distance'] ?? null),
            'subtotal' => $this->num($doc['subTotal'] ?? null, 0),
            'discount' => $this->num($doc['discount'] ?? null, 0),
            'tip' => $this->num($doc['tip_amount'] ?? null, 0),
            'total' => $this->num($doc['subTotal'] ?? null, 0),
            'payment_method' => strtolower((string) ($doc['paymentMethod'] ?? $doc['payment_method'] ?? 'cod')),
            'booking_at' => $this->ts($doc['bookingDateTime'] ?? $doc['createdAt'] ?? null),
            'started_at' => $this->ts($doc['startTime'] ?? null),
            'ended_at' => $this->ts($doc['endTime'] ?? null),
            'status' => $this->mapShipmentStatus($doc['status'] ?? null),
        ];

        if ($dry) {
            $this->track('rides', Ride::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [$ride, $outcome] = $this->upsert(Ride::class, $doc['_legacy_id'], $attrs);
        $this->track('rides', $outcome);

        if ($outcome === 'created') {
            $ride->history()->create(['from_status' => null, 'to_status' => $ride->status, 'note' => 'imported']);
        }
    }

    protected function importProvider(array $doc, bool $dry): void
    {
        if (($doc['role'] ?? null) !== 'provider') {
            return;
        }

        $name = trim((($doc['firstName'] ?? '').' '.($doc['lastName'] ?? ''))) ?: ($doc['phoneNumber'] ?? $doc['_legacy_id']);

        $attrs = [
            'name' => $name,
            'phone' => $doc['phoneNumber'] ?? null,
            'email' => ($doc['email'] ?? null) ?: null,
            'address' => is_string($doc['address'] ?? null) ? $doc['address'] : null,
            'status' => ($doc['active'] ?? false) ? 'active' : 'pending',
        ];

        if ($dry) {
            $this->track('providers', Provider::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Provider::class, $doc['_legacy_id'], $attrs);
        $this->track('providers', $outcome);
        $this->maps['providers'][$doc['_legacy_id']] = Provider::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importProviderCategory(array $doc, bool $dry): void
    {
        $parentLegacy = ($doc['parentCategoryId'] ?? null) === 'None' ? null : ($doc['parentCategoryId'] ?? null);

        $attrs = [
            'parent_id' => $parentLegacy ? ($this->maps['provider_categories'][$parentLegacy] ?? null) : null,
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'level' => (int) ($doc['level'] ?? 0),
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'image_path' => ($doc['image'] ?? null) ?: null,
            'is_active' => $this->boolish($doc['publish'] ?? null),
        ];

        if ($dry) {
            $this->track('provider-categories', ProviderCategory::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ProviderCategory::class, $doc['_legacy_id'], $attrs);
        $this->track('provider-categories', $outcome);
        $this->maps['provider_categories'][$doc['_legacy_id']] = ProviderCategory::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importProviderService(array $doc, bool $dry): void
    {
        $attrs = [
            'provider_id' => $this->maps['providers'][$doc['author'] ?? ''] ?? null,
            'category_id' => $this->maps['provider_categories'][$doc['categoryId'] ?? $doc['subCategoryId'] ?? ''] ?? null,
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'description' => $doc['description'] ?? null,
            'price' => $this->num($doc['price'] ?? null, 0),
            'discount_price' => $this->num($doc['disPrice'] ?? null),
            'price_unit' => $doc['priceUnit'] ?? null,
            'image_path' => is_array($doc['photos'] ?? null) ? ($doc['photos'][0] ?? null) : (($doc['photos'] ?? null) ?: null),
            'is_active' => $this->boolish($doc['publish'] ?? null),
        ];

        if ($dry) {
            $this->track('provider-services', ProviderService::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ProviderService::class, $doc['_legacy_id'], $attrs);
        $this->track('provider-services', $outcome);
    }

    protected function importProviderWorker(array $doc, bool $dry): void
    {
        $attrs = [
            'provider_id' => $this->maps['providers'][$doc['providerId'] ?? ''] ?? null,
            'name' => trim((($doc['firstName'] ?? '').' '.($doc['lastName'] ?? ''))) ?: $doc['_legacy_id'],
            'phone' => $doc['phoneNumber'] ?? null,
            'email' => ($doc['email'] ?? null) ?: null,
            'is_active' => $this->boolish($doc['active'] ?? null),
        ];

        if ($dry) {
            $this->track('provider-workers', ProviderWorker::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ProviderWorker::class, $doc['_legacy_id'], $attrs);
        $this->track('provider-workers', $outcome);
    }

    protected function importProviderBooking(array $doc, bool $dry): void
    {
        $author = $doc['author'] ?? [];

        if (is_string($author)) {
            $author = ['name' => $author];
        }

        $providerLegacy = $doc['providerId'] ?? (is_array($doc['provider'] ?? null) ? ($doc['provider']['id'] ?? null) : null);

        $attrs = [
            'customer_name' => trim(($author['firstName'] ?? '').' '.($author['lastName'] ?? '')) ?: ($doc['authorID'] ?? $doc['_legacy_id']),
            'customer_phone' => $author['phoneNumber'] ?? null,
            'provider_id' => $providerLegacy ? ($this->maps['providers'][$providerLegacy] ?? null) : null,
            'address' => is_string($doc['address'] ?? null) ? $doc['address'] : null,
            'scheduled_at' => $this->ts($doc['scheduleDateTime'] ?? $doc['newScheduleDateTime'] ?? null),
            'subtotal' => $this->num($doc['subTotal'] ?? $doc['subtotal'] ?? null, 0),
            'discount' => $this->num($doc['discount'] ?? null, 0),
            'extra_charges' => $this->num($doc['extraCharges'] ?? null, 0),
            'total' => $this->num($doc['subTotal'] ?? $doc['subtotal'] ?? null, 0),
            'payment_method' => strtolower((string) ($doc['payment_method'] ?? 'cod')),
            'status' => $this->mapShipmentStatus($doc['status'] ?? null),
            'notes' => $doc['notes'] ?? $doc['reason'] ?? null,
        ];

        if ($dry) {
            $this->track('provider-bookings', ProviderBooking::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [$booking, $outcome] = $this->upsert(ProviderBooking::class, $doc['_legacy_id'], $attrs);
        $this->track('provider-bookings', $outcome);

        if ($outcome === 'created') {
            $booking->history()->create(['from_status' => null, 'to_status' => $booking->status, 'note' => 'imported']);
        }
    }

    protected function importTableBooking(array $doc, bool $dry): void
    {
        $status = match (strtolower((string) ($doc['status'] ?? 'pending'))) {
            'confirmed' => 'confirmed', 'seated' => 'seated', 'completed' => 'completed',
            'cancelled', 'canceled' => 'cancelled', default => 'pending',
        };

        $attrs = [
            'store_id' => $this->maps['stores'][$doc['vendorID'] ?? ''] ?? null,
            'guest_name' => trim(($doc['guestFirstName'] ?? '').' '.($doc['guestLastName'] ?? '')) ?: ($doc['guestEmail'] ?? $doc['_legacy_id']),
            'guest_phone' => $doc['guestPhone'] ?? null,
            'guest_email' => ($doc['guestEmail'] ?? null) ?: null,
            'guests' => max(1, (int) ($doc['totalGuest'] ?? 2)),
            'booked_for' => $this->ts($doc['date'] ?? null),
            'occasion' => $doc['occasion'] ?? null,
            'special_request' => $doc['specialRequest'] ?? null,
            'status' => $status,
        ];

        if ($dry) {
            $this->track('table-bookings', TableBooking::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(TableBooking::class, $doc['_legacy_id'], $attrs);
        $this->track('table-bookings', $outcome);
    }

    /** Legacy status vocabulary → canonical transport status. */
    protected function mapShipmentStatus(?string $status): string
    {
        return match ($status) {
            'Order Placed' => 'placed',
            'Order Accepted', 'Driver Rejected' => 'accepted',
            'Order Shipped', 'In Transit', 'Order Ongoing' => 'shipped',
            'Order Completed' => 'completed',
            'Order Cancelled' => 'cancelled',
            'Order Rejected' => 'rejected',
            default => 'placed',
        };
    }

    protected function importParcelCategory(array $doc, bool $dry): void
    {
        $attrs = [
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'name' => $doc['title'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(ParcelCategory::class, $doc['title'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'image_path' => ($doc['image'] ?? null) ?: null,
            'sort_order' => (int) ($doc['set_order'] ?? 0),
            'is_active' => $this->boolish($doc['publish'] ?? null),
        ];

        if ($dry) {
            $this->track('parcel-categories', ParcelCategory::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ParcelCategory::class, $doc['_legacy_id'], $attrs);
        $this->track('parcel-categories', $outcome);
        $this->maps['parcel_categories'][$doc['_legacy_id']] = ParcelCategory::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importParcelWeight(array $doc, bool $dry): void
    {
        $maxKg = null;

        if (preg_match('/([\d.]+)\s*kg/i', (string) ($doc['title'] ?? ''), $m)) {
            $maxKg = (float) $m[1];
        }

        $attrs = [
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'max_kg' => $maxKg,
            'delivery_charge' => $this->num($doc['delivery_charge'] ?? null, 0),
            'is_active' => true,
        ];

        if ($dry) {
            $this->track('parcel-weights', ParcelWeight::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ParcelWeight::class, $doc['_legacy_id'], $attrs);
        $this->track('parcel-weights', $outcome);
        $this->maps['parcel_weights'][$doc['_legacy_id']] = ParcelWeight::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importParcelOrder(array $doc, bool $dry): void
    {
        $sender = $doc['sender'] ?? [];
        $receiver = $doc['receiver'] ?? [];

        if (is_string($sender)) {
            $sender = ['name' => $sender];
        }

        if (is_string($receiver)) {
            $receiver = ['name' => $receiver];
        }

        $attrs = [
            'sender_name' => $sender['name'] ?? null,
            'sender_phone' => $sender['phone'] ?? null,
            'sender_address' => is_array($sender) ? $sender : null,
            'receiver_name' => $receiver['name'] ?? null,
            'receiver_phone' => $receiver['phone'] ?? null,
            'receiver_address' => is_array($receiver) ? $receiver : null,
            'category_id' => $this->maps['parcel_categories'][$doc['parcelCategoryID'] ?? ''] ?? null,
            'distance_km' => $this->num($doc['distance'] ?? null),
            'subtotal' => $this->num($doc['subTotal'] ?? null, 0),
            'discount' => $this->num($doc['discount'] ?? null, 0),
            'total' => $this->num($doc['subTotal'] ?? null, 0),
            'payment_method' => strtolower((string) ($doc['payment_method'] ?? 'cod')),
            'collect_by_receiver' => $this->boolish($doc['paymentCollectByReceiver'] ?? null, false),
            'driver_id' => $this->maps['drivers'][$doc['driverID'] ?? ''] ?? null,
            'status' => $this->mapShipmentStatus($doc['status'] ?? null),
            'notes' => $doc['note'] ?? null,
        ];

        if ($dry) {
            $this->track('parcel-orders', ParcelOrder::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [$order, $outcome] = $this->upsert(ParcelOrder::class, $doc['_legacy_id'], $attrs);
        $this->track('parcel-orders', $outcome);

        if ($outcome === 'created') {
            $order->history()->create(['from_status' => null, 'to_status' => $order->status, 'note' => 'imported']);
        }
    }

    protected function importRentalType(array $doc, bool $dry): void
    {
        $attrs = [
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(RentalVehicleType::class, $doc['name'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'capacity' => is_numeric($doc['capacity'] ?? null) ? (int) $doc['capacity'] : null,
            'description' => $doc['description'] ?? $doc['short_description'] ?? null,
            'icon_path' => ($doc['rental_vehicle_icon'] ?? $doc['vehicle_icon'] ?? null) ?: null,
            'is_active' => $this->boolish($doc['isActive'] ?? null),
        ];

        if ($dry) {
            $this->track('rental-types', RentalVehicleType::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(RentalVehicleType::class, $doc['_legacy_id'], $attrs);
        $this->track('rental-types', $outcome);
        $this->maps['rental_types'][$doc['_legacy_id']] = RentalVehicleType::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importRentalPackage(array $doc, bool $dry): void
    {
        $attrs = [
            'vehicle_type_id' => $this->maps['rental_types'][$doc['vehicleTypeId'] ?? ''] ?? null,
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'description' => $doc['description'] ?? null,
            'base_fare' => $this->num($doc['baseFare'] ?? null, 0),
            'included_hours' => $this->num($doc['includedHours'] ?? null, 0),
            'included_km' => $this->num($doc['includedDistance'] ?? null, 0),
            'extra_km_fare' => $this->num($doc['extraKmFare'] ?? null, 0),
            'extra_minute_fare' => $this->num($doc['extraMinuteFare'] ?? null, 0),
            'sort_order' => (int) ($doc['ordering'] ?? 0),
            'is_active' => $this->boolish($doc['published'] ?? null),
        ];

        if ($dry) {
            $this->track('rental-packages', RentalPackage::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(RentalPackage::class, $doc['_legacy_id'], $attrs);
        $this->track('rental-packages', $outcome);
        $this->maps['rental_packages'][$doc['_legacy_id']] = RentalPackage::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importRentalOrder(array $doc, bool $dry): void
    {
        $author = $doc['author'] ?? [];

        if (is_string($author)) {
            $author = ['name' => $author];
        }

        $packageSnap = $doc['rentalPackageModel'] ?? [];

        if (is_string($packageSnap)) {
            $packageSnap = json_decode(str_replace("'", '"', $packageSnap), true) ?: [];
        }

        $attrs = [
            'customer_name' => trim(($author['firstName'] ?? '').' '.($author['lastName'] ?? '')) ?: ($doc['authorID'] ?? $doc['_legacy_id']),
            'customer_phone' => $author['phoneNumber'] ?? null,
            'package_id' => $this->maps['rental_packages'][$packageSnap['id'] ?? ''] ?? null,
            'source' => $doc['sourceLocationName'] ?? null,
            'distance_km' => null,
            'subtotal' => $this->num($doc['subTotal'] ?? null, 0),
            'discount' => $this->num($doc['discount'] ?? null, 0),
            'tip' => $this->num($doc['tip_amount'] ?? null, 0),
            'total' => $this->num($doc['subTotal'] ?? null, 0),
            'payment_method' => strtolower((string) ($doc['paymentMethod'] ?? 'cod')),
            'driver_id' => $this->maps['drivers'][$doc['driverId'] ?? ''] ?? null,
            'booking_at' => $this->ts($doc['bookingDateTime'] ?? null),
            'started_at' => $this->ts($doc['startTime'] ?? null),
            'ended_at' => $this->ts($doc['endTime'] ?? null),
            'status' => $this->mapShipmentStatus($doc['status'] ?? null),
        ];

        if ($dry) {
            $this->track('rental-orders', RentalOrder::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [$order, $outcome] = $this->upsert(RentalOrder::class, $doc['_legacy_id'], $attrs);
        $this->track('rental-orders', $outcome);

        if ($outcome === 'created') {
            $order->history()->create(['from_status' => null, 'to_status' => $order->status, 'note' => 'imported']);
        }
    }

    protected function importGiftCard(array $doc, bool $dry): void
    {
        $attrs = [
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'message' => $doc['message'] ?? null,
            'amount' => 0,
            'expiry_days' => (int) ($doc['expiryDay'] ?? 365) ?: 365,
            'image_path' => ($doc['image'] ?? null) ?: null,
            'is_active' => $this->boolish($doc['isEnable'] ?? null),
        ];

        if ($dry) {
            $this->track('gift-cards', GiftCard::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(GiftCard::class, $doc['_legacy_id'], $attrs);
        $this->track('gift-cards', $outcome);
        $this->maps['gift_cards'][$doc['_legacy_id']] = GiftCard::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importCriterion(array $doc, bool $dry): void
    {
        $attrs = ['title' => $doc['title'] ?? $doc['_legacy_id'], 'is_active' => true];

        if ($dry) {
            $this->track('review-criteria', ReviewCriterion::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ReviewCriterion::class, $doc['_legacy_id'], $attrs);
        $this->track('review-criteria', $outcome);
        $this->maps['criteria_titles'][$doc['_legacy_id']] = $doc['title'] ?? $doc['_legacy_id'];
    }

    protected function importReview(array $doc, bool $dry): void
    {
        // Product links: runtime map → legacy key → doc `id` field (live fallback).
        $ref = $doc['productId'] ?? '';
        $productId = $this->maps['products_by_field'][$ref] ?? null
            ?? ($this->maps['products'][$ref] ?? null)
            ?? Product::where('legacy_id', $ref)->value('id')
            ?? Product::where('legacy_ref', $ref)->value('id');

        $scores = [];
        $rawScores = $doc['reviewAttributes'] ?? null;

        if (is_string($rawScores)) {
            $rawScores = json_decode(str_replace("'", '"', $rawScores), true);
        }

        foreach ((array) $rawScores as $criterionId => $score) {
            $title = $this->maps['criteria_titles'][$criterionId] ?? $criterionId;
            $scores[$title] = (int) $score;
        }

        $attrs = [
            'product_id' => $productId,
            'store_id' => $this->maps['stores'][$doc['VendorId'] ?? ''] ?? null,
            'author_name' => $doc['uname'] ?? null,
            'rating' => min(5, max(1, (int) ($doc['rating'] ?? 5))),
            'comment' => $doc['comment'] ?? null,
            'criteria_scores' => $scores ?: null,
            'status' => 'pending', // everything re-moderated on import
            'occurred_at' => $this->ts($doc['createdAt'] ?? null),
        ];

        if ($dry) {
            $this->track('reviews', ItemReview::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(ItemReview::class, $doc['_legacy_id'], $attrs);
        $this->track('reviews', $outcome);
    }

    protected function importSubscription(array $doc, bool $dry): void
    {
        $subscriberRef = $doc['user_id'] ?? null;
        $subscriberType = 'unknown';
        $subscriberId = null;

        if ($subscriberRef && isset($this->maps['owners'][$subscriberRef])) {
            $subscriberType = 'owner';
            $subscriberId = $this->maps['owners'][$subscriberRef];
        } elseif ($subscriberRef && isset($this->maps['drivers'][$subscriberRef])) {
            $subscriberType = 'driver';
            $subscriberId = $this->maps['drivers'][$subscriberRef];
        }

        $endsAt = $this->ts($doc['expiry_date'] ?? null);
        $status = ($endsAt && $endsAt->isPast()) ? 'expired' : 'active';

        $attrs = [
            'subscriber_type' => $subscriberType,
            'subscriber_id' => $subscriberId,
            'subscriber_ref' => $subscriberRef,
            'amount' => 0,
            'status' => $status,
            'starts_at' => $this->ts($doc['createdAt'] ?? null),
            'ends_at' => $endsAt,
        ];

        if ($dry) {
            $this->track('subscriptions', PlanSubscription::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(PlanSubscription::class, $doc['_legacy_id'], $attrs);
        $this->track('subscriptions', $outcome);
    }

    protected function importGiftOrder(array $doc, bool $dry): void
    {
        $redeemed = $this->boolish($doc['redeem'] ?? null, false);

        $attrs = [
            'gift_id' => $this->maps['gift_cards'][$doc['giftId'] ?? ''] ?? null,
            'code' => ($doc['giftCode'] ?? null) ?: null,
            'buyer_ref' => $doc['userid'] ?? null,
            'amount' => $this->num($doc['price'] ?? null, 0),
            'status' => $redeemed ? 'redeemed' : 'active',
            'expires_at' => $this->ts($doc['expireDate'] ?? null),
        ];

        if ($dry) {
            $this->track('gift-orders', GiftOrder::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(GiftOrder::class, $doc['_legacy_id'], $attrs);
        $this->track('gift-orders', $outcome);
    }

    protected function importStory(array $doc, bool $dry): void
    {
        $video = $doc['videoUrl'] ?? null;

        if (is_string($video) && str_starts_with(trim($video), '[')) {
            preg_match_all("/'(https?:[^']+)'/", $video, $m);
            $video = $m[1][0] ?? null;
        }

        if (is_array($video)) {
            $video = $video[0] ?? null;
        }

        $attrs = [
            'store_id' => $this->maps['stores'][$doc['vendorID'] ?? ''] ?? null,
            'video_url' => is_string($video) ? substr($video, 0, 1000) : null,
            'thumbnail' => is_string($doc['videoThumbnail'] ?? null) ? substr($doc['videoThumbnail'], 0, 1000) : null,
            'status' => 'active',
            'occurred_at' => $this->ts($doc['createdAt'] ?? null),
        ];

        if ($dry) {
            $this->track('stories', Story::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Story::class, $doc['_legacy_id'], $attrs);
        $this->track('stories', $outcome);
    }

    protected function importPreset(array $doc, bool $dry): void
    {
        $labels = $doc['filters'] ?? null;

        if (is_string($labels)) {
            $labels = json_decode(str_replace("'", '"', $labels), true);
        }

        if (! is_array($labels)) {
            return;
        }

        foreach (array_keys($labels) as $label) {
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            if ($dry) {
                $this->track('presets', FilterPreset::where('name', $label)->exists() ? 'updated' : 'created');
                continue;
            }

            $preset = FilterPreset::firstOrCreate(['name' => substr($label, 0, 100)]);
            $this->track('presets', $preset->wasRecentlyCreated ? 'created' : 'updated');
        }
    }

    protected function importOwner(array $doc, bool $dry): void
    {
        if (($doc['role'] ?? null) !== 'vendor') {
            return;
        }

        $name = trim((($doc['firstName'] ?? '').' '.($doc['lastName'] ?? ''))) ?: ($doc['phoneNumber'] ?? $doc['_legacy_id']);

        $attrs = [
            'name' => $name,
            'phone' => $doc['phoneNumber'] ?? null,
            'email' => ($doc['email'] ?? null) ?: null,
            'status' => ($doc['active'] ?? false) ? 'active' : 'pending',
        ];

        if ($dry) {
            $this->track('owners', Owner::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Owner::class, $doc['_legacy_id'], $attrs);
        $this->track('owners', $outcome);
        $this->maps['owners'][$doc['_legacy_id']] = Owner::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importWallet(array $doc, bool $dry): void
    {
        $amount = $this->num($doc['amount'] ?? null);

        if ($amount === null) {
            $this->track('wallets', 'skipped');

            return;
        }

        $isTopUp = $this->boolish($doc['isTopUp'] ?? null, false);

        $attrs = [
            'owner_type' => in_array($doc['transactionUser'] ?? '', ['customer', 'driver', 'vendor', 'provider'], true)
                ? $doc['transactionUser'] : 'customer',
            'owner_ref' => $doc['user_id'] ?? null,
            'amount' => $amount,
            'kind' => $isTopUp ? 'topup' : 'order',
            'method' => $doc['payment_method'] ?? null,
            'status' => strtolower((string) ($doc['payment_status'] ?? 'success')),
            'note' => isset($doc['note']) ? substr((string) $doc['note'], 0, 255) : null,
            'order_ref' => $doc['order_id'] ?? null,
            'occurred_at' => $this->ts($doc['date'] ?? null),
        ];

        if ($dry) {
            $this->track('wallets', WalletEntry::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(WalletEntry::class, $doc['_legacy_id'], $attrs);
        $this->track('wallets', $outcome);
    }

    protected function importReferral(array $doc, bool $dry): void
    {
        $code = trim((string) ($doc['referralCode'] ?? ''));

        if ($code === '') {
            $this->track('referrals', 'skipped');

            return;
        }

        // Codes are globally unique — never steal another doc's code.
        $clash = Referral::where('code', $code)->where('legacy_id', '!=', $doc['_legacy_id'])->exists();

        if ($clash) {
            $this->track('referrals', 'skipped');

            return;
        }

        $attrs = ['code' => $code, 'referrer_ref' => ($doc['referralBy'] ?? null) ?: null];

        if ($dry) {
            $this->track('referrals', Referral::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Referral::class, $doc['_legacy_id'], $attrs);
        $this->track('referrals', $outcome);
    }

    protected function importDriver(array $doc, bool $dry): void
    {
        if (($doc['role'] ?? null) !== 'driver') {
            return; // customers/vendors/providers live with their owner milestones
        }

        $name = trim((($doc['firstName'] ?? '').' '.($doc['lastName'] ?? ''))) ?: ($doc['phoneNumber'] ?? $doc['_legacy_id']);

        $attrs = [
            'kind' => 'ride',
            'name' => $name,
            'phone' => $doc['phoneNumber'] ?? null,
            'email' => ($doc['email'] ?? null) ?: null,
            'photo_path' => ($doc['profilePictureURL'] ?? null) ?: null,
            'status' => ($doc['active'] ?? false) ? 'active' : 'pending',
        ];

        if ($dry) {
            $this->track('drivers', Driver::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Driver::class, $doc['_legacy_id'], $attrs);
        $this->track('drivers', $outcome);
        $this->maps['drivers'][$doc['_legacy_id']] = Driver::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importDocumentType(array $doc, bool $dry): void
    {
        $attrs = [
            'title' => $doc['title'] ?? $doc['_legacy_id'],
            'owner_type' => ($doc['type'] ?? '') === 'restaurant' ? 'store' : 'driver',
            'front_required' => $this->boolish($doc['frontSide'] ?? null),
            'back_required' => $this->boolish($doc['backSide'] ?? null, false),
            'is_active' => $this->boolish($doc['enable'] ?? null),
        ];

        if ($dry) {
            $this->track('document-types', DocumentType::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(DocumentType::class, $doc['_legacy_id'], $attrs);
        $this->track('document-types', $outcome);
        $this->maps['document_types'][$doc['_legacy_id']] = DocumentType::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importVerification(array $doc, bool $dry): void
    {
        // Owner resolves driver-first, then store (docverify ids match either).
        $owner = null;
        $legacyDriver = $doc['_legacy_id'];

        if (isset($this->maps['drivers'][$legacyDriver])) {
            $owner = [Driver::class, $this->maps['drivers'][$legacyDriver]];
        } else {
            $storeId = Store::where('legacy_id', $legacyDriver)->value('id');

            if ($storeId) {
                $owner = [Store::class, $storeId];
            }
        }

        if (! $owner) {
            $this->track('verifications', 'skipped');

            return;
        }

        $rows = is_string($doc['documents'] ?? null)
            ? json_decode(str_replace("'", '"', (string) $doc['documents']), true)
            : ($doc['documents'] ?? []);

        if (! is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as $row) {
            $typeId = $this->maps['document_types'][$row['documentId'] ?? ''] ?? null;
            $status = strtolower((string) ($row['status'] ?? 'pending'));
            $status = in_array($status, ['approved', 'rejected'], true) ? $status : 'pending';

            $attrs = [
                'verifiable_type' => $owner[0],
                'verifiable_id' => $owner[1],
                'document_type_id' => $typeId,
                'front_path' => ($row['frontImage'] ?? null) ?: null,
                'back_path' => ($row['backImage'] ?? null) ?: null,
                'status' => $status,
            ];

            if ($dry) {
                $this->track('verifications', 'created');
                continue;
            }

            [$model] = $this->upsertBy($attrs);

            if ($model->wasRecentlyCreated) {
                $this->track('verifications', 'created');
            } else {
                $model->update($attrs);
                $this->track('verifications', 'updated');
            }
        }
    }

    /** Upsert a verification on its natural key (owner + type + fronts). */
    protected function upsertBy(array $attrs): array
    {
        $model = Verification::firstOrNew([
            'verifiable_type' => $attrs['verifiable_type'],
            'verifiable_id' => $attrs['verifiable_id'],
            'document_type_id' => $attrs['document_type_id'],
            'front_path' => $attrs['front_path'],
        ]);

        if (! $model->exists) {
            $model->fill($attrs)->save();
        }

        return [$model];
    }

    protected function importZone(array $doc, bool $dry): void
    {
        $attrs = [
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'latitude' => $this->num($doc['latitude'] ?? null),
            'longitude' => $this->num($doc['longitude'] ?? null),
            'radius_km' => $this->num($doc['area'] ?? null, 5),
            'is_active' => $this->boolish($doc['publish'] ?? null),
        ];

        if ($dry) {
            $this->track('zones', Zone::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Zone::class, $doc['_legacy_id'], $attrs);
        $this->track('zones', $outcome);
        $this->maps['zones'][$doc['_legacy_id']] = Zone::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importStore(array $doc, bool $dry): void
    {
        $commission = ['type' => 'percentage', 'commission' => 0];

        if (is_string($doc['adminCommission'] ?? null)) {
            // Legacy stored a Python-dict string; extract the two scalars defensively.
            preg_match("/'type':\s*'([^']+)'/", $doc['adminCommission'], $t);
            preg_match("/'commission':\s*([\d.]+)/", $doc['adminCommission'], $c);
            $commission['type'] = $t[1] ?? 'percentage';
            $commission['commission'] = (float) ($c[1] ?? 0);
        } elseif (is_array($doc['adminCommission'] ?? null)) {
            $commission = array_merge($commission, $doc['adminCommission']);
        }

        $attrs = [
            'name' => $doc['title'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(Store::class, $doc['title'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'description' => is_scalar($doc['description'] ?? null) ? (string) $doc['description'] : null,
            'owner_name' => $doc['authorName'] ?? null,
            'phone' => $doc['phonenumber'] ?? null,
            'address' => $doc['location'] ?? null,
            'latitude' => $this->num($doc['latitude'] ?? null),
            'longitude' => $this->num($doc['longitude'] ?? null),
            'image_path' => ($doc['photo'] ?? null) ?: null,
            'section_id' => $this->maps['sections'][$doc['section_id'] ?? ''] ?? null,
            'zone_id' => $this->maps['zones'][$doc['zoneId'] ?? ''] ?? null,
            'owner_id' => $this->maps['owners'][$doc['author'] ?? ''] ?? null,
            'status' => 'active', // imported stores are known-good; new signups start pending
            'is_open' => $this->boolish($doc['reststatus'] ?? null),
            'commission_type' => ($commission['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage',
            'commission_value' => (float) ($commission['commission'] ?? 0),
            'created_at' => $this->ts($doc['createdAt'] ?? null) ?? now(),
        ];

        if ($dry) {
            $this->track('stores', Store::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Store::class, $doc['_legacy_id'], $attrs);
        $this->track('stores', $outcome);
        $this->maps['stores'][$doc['_legacy_id']] = Store::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importSection(array $doc, bool $dry): void
    {
        $attrs = [
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(Section::class, $doc['name'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'service_type' => $doc['serviceType'] ?? null,
            'color' => $doc['color'] ?? null,
            'image_path' => $doc['sectionImage'] ?? null,
            'is_active' => $this->boolish($doc['isActive'] ?? null),
        ];

        if ($dry) {
            $this->track('sections', Section::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Section::class, $doc['_legacy_id'], $attrs);
        $this->track('sections', $outcome);
        $this->maps['sections'][$doc['_legacy_id']] = Section::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importCategory(array $doc, bool $dry): void
    {
        $attrs = [
            'section_id' => $this->maps['sections'][$doc['section_id'] ?? ''] ?? null,
            'name' => $doc['title'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(Category::class, $doc['title'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'description' => $doc['description'] ?? null,
            'image_path' => $doc['photo'] ?? null,
            'sort_order' => (int) ($doc['order'] ?? 0),
            'show_in_homepage' => $this->boolish($doc['show_in_homepage'] ?? null, false),
            'is_active' => $this->boolish($doc['publish'] ?? null),
        ];

        if ($dry) {
            $this->track('categories', Category::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Category::class, $doc['_legacy_id'], $attrs);
        $this->track('categories', $outcome);
        $this->maps['categories'][$doc['_legacy_id']] = Category::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importBrand(array $doc, bool $dry): void
    {
        $attrs = [
            'section_id' => $this->maps['sections'][$doc['sectionId'] ?? ''] ?? null,
            'name' => $doc['title'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(Brand::class, $doc['title'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'image_path' => $doc['photo'] ?? null,
            'is_active' => $this->boolish($doc['is_publish'] ?? null),
        ];

        if ($dry) {
            $this->track('brands', Brand::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Brand::class, $doc['_legacy_id'], $attrs);
        $this->track('brands', $outcome);
        $this->maps['brands'][$doc['_legacy_id']] = Brand::where('legacy_id', $doc['_legacy_id'])->value('id');
    }

    protected function importProduct(array $doc, bool $dry): void
    {
        $price = $this->num($doc['price'] ?? null);

        if ($price === null) {
            $this->track('products', 'skipped');

            return;
        }

        $attrs = [
            'section_id' => $this->maps['sections'][$doc['section_id'] ?? ''] ?? null,
            'category_id' => $this->maps['categories'][$doc['categoryID'] ?? ''] ?? null,
            'brand_id' => $this->maps['brands'][$doc['brandID'] ?? ''] ?? null,
            'name' => $doc['name'] ?? $doc['_legacy_id'],
            'slug' => $this->uniqueSlug(Product::class, $doc['name'] ?? $doc['_legacy_id'], $doc['_legacy_id']),
            'description' => $doc['description'] ?? null,
            'price' => $price,
            'discount_price' => $this->num($doc['disPrice'] ?? null),
            'quantity' => (int) ($doc['quantity'] ?? 0),
            'veg' => $this->boolish($doc['veg'] ?? null),
            'is_takeaway' => $this->boolish($doc['takeawayOption'] ?? null, false),
            'calories' => isset($doc['calories']) ? (string) $doc['calories'] : null,
            'proteins' => isset($doc['proteins']) ? (string) $doc['proteins'] : null,
            'fats' => isset($doc['fats']) ? (string) $doc['fats'] : null,
            'grams' => isset($doc['grams']) ? (string) $doc['grams'] : null,
            'image_path' => $doc['photo'] ?? null,
            'is_active' => $this->boolish($doc['publish'] ?? null),
            'created_at' => $this->ts($doc['createdAt'] ?? null) ?? now(),
        ];

        if ($dry) {
            $this->track('products', Product::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [$product, $outcome] = $this->upsert(Product::class, $doc['_legacy_id'], $attrs);
        $this->track('products', $outcome);
        $this->maps['products'][$doc['_legacy_id']] = $product->id;

        if (! empty($doc['id'])) {
            $product->update(['legacy_ref' => $doc['id']]);
            $this->maps['products_by_field'][$doc['id']] = $product->id;
        }

        // Link the vendor (stores import before products, so the map is warm).
        $legacyVendor = $this->maps['product_vendors'][$doc['_legacy_id']] ?? null;
        $vendorId = $legacyVendor ? ($this->maps['stores'][$legacyVendor] ?? null) : null;

        if ($vendorId && $product->vendor_id !== $vendorId) {
            $product->update(['vendor_id' => $vendorId]);
        }

        // Parallel legacy arrays → addon rows.
        $titles = (array) ($doc['addOnsTitle'] ?? []);
        $prices = (array) ($doc['addOnsPrice'] ?? []);
        $product->addons()->delete();

        foreach (array_values($titles) as $i => $title) {
            if (trim((string) $title) === '') {
                continue;
            }

            $product->addons()->create([
                'name' => $title,
                'price' => $this->num($prices[$i] ?? 0, 0),
                'sort_order' => $i,
            ]);
        }
    }

    protected function importCoupon(array $doc, bool $dry): void
    {
        $code = strtoupper(trim((string) ($doc['code'] ?? '')));

        if ($code === '') {
            $this->track('coupons', 'skipped');

            return;
        }

        // Codes are globally unique — never steal another legacy doc's code.
        $clash = Coupon::where('code', $code)->where('legacy_id', '!=', $doc['_legacy_id'])->exists();

        if ($clash) {
            $this->track('coupons', 'skipped');

            return;
        }

        $type = ($doc['discountType'] ?? '') === 'Fix Price' ? 'fixed' : 'percentage';

        $attrs = [
            'code' => $code,
            'description' => $doc['description'] ?? null,
            'discount_type' => $type,
            'discount_value' => $this->num($doc['discount'] ?? null, 0),
            'scope' => 'all',
            'section_id' => $this->maps['sections'][$doc['section_id'] ?? ''] ?? null,
            'image_path' => ($doc['image'] ?? null) ?: null,
            'expires_at' => $this->ts($doc['expiresAt'] ?? null),
            'is_public' => $this->boolish($doc['isPublic'] ?? null),
            'is_active' => $this->boolish($doc['isEnabled'] ?? null),
            'created_at' => $this->ts($doc['createdAt'] ?? null) ?? now(),
        ];

        if ($dry) {
            $this->track('coupons', Coupon::where('legacy_id', $doc['_legacy_id'])->exists() ? 'updated' : 'created');

            return;
        }

        [, $outcome] = $this->upsert(Coupon::class, $doc['_legacy_id'], $attrs);
        $this->track('coupons', $outcome);
    }
}
