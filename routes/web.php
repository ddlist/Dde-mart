<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\GiftCardController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\ParcelController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PayoutRequestController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PushTemplateController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RentalController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RideController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShowcaseController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\DineInController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SubscriptionLedgerController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\DisbursementController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EngagementController;
use App\Http\Controllers\Admin\OpsController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
 * DDE-Mart Admin — web routes (original implementation).
 * Login-only auth: no registration / password-reset routes in this panel.
 */

Route::redirect('/', '/shop')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Roles & permission matrix (D2). Fine-grained abilities enforced per action.
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('admin.can:roles,view')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])
        ->middleware('admin.can:roles,create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('admin.can:roles,create')->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('admin.can:roles,edit')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('admin.can:roles,edit')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('admin.can:roles,delete')->name('roles.destroy');

    // Staff users (D5). Self profile lives outside the users.* ability gate.
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('admin.can:users,view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('admin.can:users,create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('admin.can:users,create')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('admin.can:users,edit')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('admin.can:users,edit')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('admin.can:users,delete')->name('users.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Food orders (D7). No destroy route — records are cancelled, never deleted.
    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('admin.can:orders,view')->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('admin.can:orders,view')->name('orders.show');
    Route::post('/orders/{order}/transition', [OrderController::class, 'transition'])
        ->middleware('admin.can:orders,edit')->name('orders.transition');

    // Stores (vendors). Delete blocked while products are assigned.
    Route::get('/stores', [StoreController::class, 'index'])
        ->middleware('admin.can:stores,view')->name('stores.index');
    Route::get('/stores/create', [StoreController::class, 'create'])
        ->middleware('admin.can:stores,create')->name('stores.create');
    Route::post('/stores', [StoreController::class, 'store'])
        ->middleware('admin.can:stores,create')->name('stores.store');
    Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])
        ->middleware('admin.can:stores,edit')->name('stores.edit');
    Route::put('/stores/{store}', [StoreController::class, 'update'])
        ->middleware('admin.can:stores,edit')->name('stores.update');
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])
        ->middleware('admin.can:stores,delete')->name('stores.destroy');
    Route::post('/stores/{store}/transition', [StoreController::class, 'transition'])
        ->middleware('admin.can:stores,edit')->name('stores.transition');

    // Drivers incl. delivery riders and fleet (verification queue below).
    Route::get('/drivers', [DriverController::class, 'index'])
        ->middleware('admin.can:drivers,view')->name('drivers.index');
    Route::get('/drivers/create', [DriverController::class, 'create'])
        ->middleware('admin.can:drivers,create')->name('drivers.create');
    Route::post('/drivers', [DriverController::class, 'store'])
        ->middleware('admin.can:drivers,create')->name('drivers.store');
    Route::get('/drivers/{driver}', [DriverController::class, 'show'])
        ->middleware('admin.can:drivers,view')->name('drivers.show');
    Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])
        ->middleware('admin.can:drivers,edit')->name('drivers.edit');
    Route::put('/drivers/{driver}', [DriverController::class, 'update'])
        ->middleware('admin.can:drivers,edit')->name('drivers.update');
    Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])
        ->middleware('admin.can:drivers,delete')->name('drivers.destroy');
    Route::post('/drivers/{driver}/transition', [DriverController::class, 'transition'])
        ->middleware('admin.can:drivers,edit')->name('drivers.transition');

    // Verification: document types + review queue (drivers + stores).
    Route::get('/verification', [VerificationController::class, 'queue'])
        ->middleware('admin.can:drivers,view')->name('verifications.queue');
    Route::post('/verification/{verification}/review', [VerificationController::class, 'review'])
        ->middleware('admin.can:drivers,edit')->name('verifications.review');
    Route::get('/doc-types', [VerificationController::class, 'types'])
        ->middleware('admin.can:drivers,view')->name('doc-types.index');
    Route::get('/doc-types/create', [VerificationController::class, 'typeCreate'])
        ->middleware('admin.can:drivers,create')->name('doc-types.create');
    Route::post('/doc-types', [VerificationController::class, 'typeStore'])
        ->middleware('admin.can:drivers,create')->name('doc-types.store');
    Route::get('/doc-types/{documentType}/edit', [VerificationController::class, 'typeEdit'])
        ->middleware('admin.can:drivers,edit')->name('doc-types.edit');
    Route::put('/doc-types/{documentType}', [VerificationController::class, 'typeUpdate'])
        ->middleware('admin.can:drivers,edit')->name('doc-types.update');
    Route::delete('/doc-types/{documentType}', [VerificationController::class, 'typeDestroy'])
        ->middleware('admin.can:drivers,delete')->name('doc-types.destroy');

    // Catalog (D6). All six resources share the catalog.* ability gate.
    // NOTE: {param} names must match controller argument names for binding.
    foreach ([
        'sections' => [SectionController::class, 'section'],
        'categories' => [CategoryController::class, 'category'],
        'brands' => [BrandController::class, 'brand'],
        'attributes' => [AttributeController::class, 'attribute'],
        'products' => [ProductController::class, 'product'],
        'banners' => [BannerController::class, 'banner'],
    ] as $resource => [$controller, $param]) {
        Route::get("/{$resource}", [$controller, 'index'])
            ->middleware('admin.can:catalog,view')->name("{$resource}.index");
        Route::get("/{$resource}/create", [$controller, 'create'])
            ->middleware('admin.can:catalog,create')->name("{$resource}.create");
        Route::post("/{$resource}", [$controller, 'store'])
            ->middleware('admin.can:catalog,create')->name("{$resource}.store");
        Route::get("/{$resource}/{{$param}}/edit", [$controller, 'edit'])
            ->middleware('admin.can:catalog,edit')->name("{$resource}.edit");
        Route::put("/{$resource}/{{$param}}", [$controller, 'update'])
            ->middleware('admin.can:catalog,edit')->name("{$resource}.update");
        Route::delete("/{$resource}/{{$param}}", [$controller, 'destroy'])
            ->middleware('admin.can:catalog,delete')->name("{$resource}.destroy");
    }

    // Promotions (D8): coupons, ads, gift cards.
    foreach ([
        'coupons' => [CouponController::class, 'coupon'],
        'ads' => [AdvertisementController::class, 'advertisement'],
        'gifts' => [GiftCardController::class, 'giftCard'],
    ] as $resource => [$controller, $param]) {
        Route::get("/{$resource}", [$controller, 'index'])
            ->middleware('admin.can:promotions,view')->name("{$resource}.index");
        Route::get("/{$resource}/create", [$controller, 'create'])
            ->middleware('admin.can:promotions,create')->name("{$resource}.create");
        Route::post("/{$resource}", [$controller, 'store'])
            ->middleware('admin.can:promotions,create')->name("{$resource}.store");
        Route::get("/{$resource}/{{$param}}/edit", [$controller, 'edit'])
            ->middleware('admin.can:promotions,edit')->name("{$resource}.edit");
        Route::put("/{$resource}/{{$param}}", [$controller, 'update'])
            ->middleware('admin.can:promotions,edit')->name("{$resource}.update");
        Route::delete("/{$resource}/{{$param}}", [$controller, 'destroy'])
            ->middleware('admin.can:promotions,delete')->name("{$resource}.destroy");
    }
    Route::post('/ads/{advertisement}/transition', [AdvertisementController::class, 'transition'])
        ->middleware('admin.can:promotions,edit')->name('ads.transition');

    // Finance (D8): taxes, currencies, plans.
    foreach ([
        'taxes' => [TaxController::class, 'tax'],
        'currencies' => [CurrencyController::class, 'currency'],
        'plans' => [SubscriptionPlanController::class, 'plan'],
    ] as $resource => [$controller, $param]) {
        Route::get("/{$resource}", [$controller, 'index'])
            ->middleware('admin.can:finance,view')->name("{$resource}.index");
        Route::get("/{$resource}/create", [$controller, 'create'])
            ->middleware('admin.can:finance,create')->name("{$resource}.create");
        Route::post("/{$resource}", [$controller, 'store'])
            ->middleware('admin.can:finance,create')->name("{$resource}.store");
        Route::get("/{$resource}/{{$param}}/edit", [$controller, 'edit'])
            ->middleware('admin.can:finance,edit')->name("{$resource}.edit");
        Route::put("/{$resource}/{{$param}}", [$controller, 'update'])
            ->middleware('admin.can:finance,edit')->name("{$resource}.update");
        Route::delete("/{$resource}/{{$param}}", [$controller, 'destroy'])
            ->middleware('admin.can:finance,delete')->name("{$resource}.destroy");
    }

    // Payout workflow (D8). No destroy route — money trail is never deleted.
    Route::get('/payouts', [PayoutRequestController::class, 'index'])
        ->middleware('admin.can:finance,view')->name('payouts.index');
    Route::get('/payouts/{payout}', [PayoutRequestController::class, 'show'])
        ->middleware('admin.can:finance,view')->name('payouts.show');
    Route::post('/payouts/{payout}/transition', [PayoutRequestController::class, 'transition'])
        ->middleware('admin.can:finance,edit')->name('payouts.transition');
    Route::post('/payouts/{payout}/execute', [PayoutRequestController::class, 'execute'])
        ->middleware('admin.can:finance,edit')->name('payouts.execute');

    // Geo + content (D9). Settings is a singleton editor, not a resource.
    foreach ([
        'zones' => [ZoneController::class, 'zone'],
        'push' => [PushTemplateController::class, 'push'],
        'emails' => [EmailTemplateController::class, 'email'],
        'pages' => [PageController::class, 'page'],
        'languages' => [LanguageController::class, 'language'],
    ] as $resource => [$controller, $param]) {
        Route::get("/{$resource}", [$controller, 'index'])
            ->middleware('admin.can:content,view')->name("{$resource}.index");
        Route::get("/{$resource}/create", [$controller, 'create'])
            ->middleware('admin.can:content,create')->name("{$resource}.create");
        Route::post("/{$resource}", [$controller, 'store'])
            ->middleware('admin.can:content,create')->name("{$resource}.store");
        Route::get("/{$resource}/{{$param}}/edit", [$controller, 'edit'])
            ->middleware('admin.can:content,edit')->name("{$resource}.edit");
        Route::put("/{$resource}/{{$param}}", [$controller, 'update'])
            ->middleware('admin.can:content,edit')->name("{$resource}.update");
        Route::delete("/{$resource}/{{$param}}", [$controller, 'destroy'])
            ->middleware('admin.can:content,delete')->name("{$resource}.destroy");
    }

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware('admin.can:content,view')->name('notifications.index');
    Route::get('/notifications/create', [NotificationController::class, 'create'])
        ->middleware('admin.can:content,create')->name('notifications.create');
    Route::post('/notifications', [NotificationController::class, 'store'])
        ->middleware('admin.can:content,create')->name('notifications.store');

    Route::get('/settings', [SettingController::class, 'edit'])
        ->middleware('admin.can:content,view')->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])
        ->middleware('admin.can:content,edit')->name('settings.update');

    // Owners directory + wallet ledger + referrals (read-only).
    Route::get('/owners', [OwnerController::class, 'index'])
        ->middleware('admin.can:owners,view')->name('owners.index');
    Route::get('/owners/create', [OwnerController::class, 'create'])
        ->middleware('admin.can:owners,create')->name('owners.create');
    Route::post('/owners', [OwnerController::class, 'store'])
        ->middleware('admin.can:owners,create')->name('owners.store');
    Route::get('/owners/{owner}', [OwnerController::class, 'show'])
        ->middleware('admin.can:owners,view')->name('owners.show');
    Route::get('/owners/{owner}/edit', [OwnerController::class, 'edit'])
        ->middleware('admin.can:owners,edit')->name('owners.edit');
    Route::put('/owners/{owner}', [OwnerController::class, 'update'])
        ->middleware('admin.can:owners,edit')->name('owners.update');
    Route::delete('/owners/{owner}', [OwnerController::class, 'destroy'])
        ->middleware('admin.can:owners,delete')->name('owners.destroy');
    Route::post('/owners/{owner}/transition', [OwnerController::class, 'transition'])
        ->middleware('admin.can:owners,edit')->name('owners.transition');

    // Transport verticals (parcel + rental). No order deletes — cancel instead.
    Route::get('/parcel-categories', [ParcelController::class, 'categories'])
        ->middleware('admin.can:transport,view')->name('parcel-categories.index');
    Route::get('/parcel-categories/create', [ParcelController::class, 'categoryCreate'])
        ->middleware('admin.can:transport,create')->name('parcel-categories.create');
    Route::post('/parcel-categories', [ParcelController::class, 'categoryStore'])
        ->middleware('admin.can:transport,create')->name('parcel-categories.store');
    Route::get('/parcel-categories/{category}/edit', [ParcelController::class, 'categoryEdit'])
        ->middleware('admin.can:transport,edit')->name('parcel-categories.edit');
    Route::put('/parcel-categories/{category}', [ParcelController::class, 'categoryUpdate'])
        ->middleware('admin.can:transport,edit')->name('parcel-categories.update');
    Route::delete('/parcel-categories/{category}', [ParcelController::class, 'categoryDestroy'])
        ->middleware('admin.can:transport,delete')->name('parcel-categories.destroy');

    Route::get('/parcel-weights', [ParcelController::class, 'weights'])
        ->middleware('admin.can:transport,view')->name('parcel-weights.index');
    Route::post('/parcel-weights', [ParcelController::class, 'weightStore'])
        ->middleware('admin.can:transport,create')->name('parcel-weights.store');
    Route::put('/parcel-weights/{weight}', [ParcelController::class, 'weightUpdate'])
        ->middleware('admin.can:transport,edit')->name('parcel-weights.update');
    Route::delete('/parcel-weights/{weight}', [ParcelController::class, 'weightDestroy'])
        ->middleware('admin.can:transport,delete')->name('parcel-weights.destroy');

    Route::get('/parcel-orders', [ParcelController::class, 'orders'])
        ->middleware('admin.can:transport,view')->name('parcel-orders.index');
    Route::get('/parcel-orders/{parcelOrder}', [ParcelController::class, 'orderShow'])
        ->middleware('admin.can:transport,view')->name('parcel-orders.show');
    Route::post('/parcel-orders/{parcelOrder}/transition', [ParcelController::class, 'orderTransition'])
        ->middleware('admin.can:transport,edit')->name('parcel-orders.transition');

    Route::get('/rental-types', [RentalController::class, 'types'])
        ->middleware('admin.can:transport,view')->name('rental-types.index');
    Route::get('/rental-types/create', [RentalController::class, 'typeCreate'])
        ->middleware('admin.can:transport,create')->name('rental-types.create');
    Route::post('/rental-types', [RentalController::class, 'typeStore'])
        ->middleware('admin.can:transport,create')->name('rental-types.store');
    Route::get('/rental-types/{type}/edit', [RentalController::class, 'typeEdit'])
        ->middleware('admin.can:transport,edit')->name('rental-types.edit');
    Route::put('/rental-types/{type}', [RentalController::class, 'typeUpdate'])
        ->middleware('admin.can:transport,edit')->name('rental-types.update');
    Route::delete('/rental-types/{type}', [RentalController::class, 'typeDestroy'])
        ->middleware('admin.can:transport,delete')->name('rental-types.destroy');

    Route::get('/rental-packages', [RentalController::class, 'packages'])
        ->middleware('admin.can:transport,view')->name('rental-packages.index');
    Route::get('/rental-packages/create', [RentalController::class, 'packageCreate'])
        ->middleware('admin.can:transport,create')->name('rental-packages.create');
    Route::post('/rental-packages', [RentalController::class, 'packageStore'])
        ->middleware('admin.can:transport,create')->name('rental-packages.store');
    Route::get('/rental-packages/{package}/edit', [RentalController::class, 'packageEdit'])
        ->middleware('admin.can:transport,edit')->name('rental-packages.edit');
    Route::put('/rental-packages/{package}', [RentalController::class, 'packageUpdate'])
        ->middleware('admin.can:transport,edit')->name('rental-packages.update');
    Route::delete('/rental-packages/{package}', [RentalController::class, 'packageDestroy'])
        ->middleware('admin.can:transport,delete')->name('rental-packages.destroy');

    Route::get('/rental-orders', [RentalController::class, 'orders'])
        ->middleware('admin.can:transport,view')->name('rental-orders.index');
    Route::get('/rental-orders/{rentalOrder}', [RentalController::class, 'orderShow'])
        ->middleware('admin.can:transport,view')->name('rental-orders.show');
    Route::post('/rental-orders/{rentalOrder}/transition', [RentalController::class, 'orderTransition'])
        ->middleware('admin.can:transport,edit')->name('rental-orders.transition');

    // Rides/cab: fleet masters + ride pipeline. No ride deletes.
    Route::get('/fleet', [RideController::class, 'fleet'])
        ->middleware('admin.can:transport,view')->name('fleet.index');
    Route::post('/fleet/makes', [RideController::class, 'makeStore'])
        ->middleware('admin.can:transport,create')->name('fleet.makes.store');
    Route::delete('/fleet/makes/{make}', [RideController::class, 'makeDestroy'])
        ->middleware('admin.can:transport,delete')->name('fleet.makes.destroy');
    Route::post('/fleet/models', [RideController::class, 'modelStore'])
        ->middleware('admin.can:transport,create')->name('fleet.models.store');
    Route::delete('/fleet/models/{model}', [RideController::class, 'modelDestroy'])
        ->middleware('admin.can:transport,delete')->name('fleet.models.destroy');
    Route::post('/fleet/types', [RideController::class, 'typeStore'])
        ->middleware('admin.can:transport,create')->name('fleet.types.store');
    Route::delete('/fleet/types/{type}', [RideController::class, 'typeDestroy'])
        ->middleware('admin.can:transport,delete')->name('fleet.types.destroy');
    Route::post('/fleet/destinations', [RideController::class, 'destinationStore'])
        ->middleware('admin.can:transport,create')->name('fleet.destinations.store');
    Route::delete('/fleet/destinations/{destination}', [RideController::class, 'destinationDestroy'])
        ->middleware('admin.can:transport,delete')->name('fleet.destinations.destroy');

    Route::get('/rides', [RideController::class, 'rides'])
        ->middleware('admin.can:transport,view')->name('rides.index');
    Route::get('/rides/{ride}', [RideController::class, 'rideShow'])
        ->middleware('admin.can:transport,view')->name('rides.show');
    Route::post('/rides/{ride}/transition', [RideController::class, 'rideTransition'])
        ->middleware('admin.can:transport,edit')->name('rides.transition');
    Route::post('/rides/{ride}/assign', [RideController::class, 'rideAssign'])
        ->middleware('admin.can:transport,edit')->name('rides.assign');

    // On-demand services: providers, nested categories, services, workers, bookings.
    Route::get('/providers', [ServiceController::class, 'providers'])
        ->middleware('admin.can:transport,view')->name('providers.index');
    Route::get('/providers/{provider}', [ServiceController::class, 'providerShow'])
        ->middleware('admin.can:transport,view')->name('providers.show');
    Route::post('/providers/{provider}/transition', [ServiceController::class, 'providerTransition'])
        ->middleware('admin.can:transport,edit')->name('providers.transition');
    Route::get('/provider-categories', [ServiceController::class, 'categories'])
        ->middleware('admin.can:transport,view')->name('provider-categories.index');
    Route::post('/provider-categories', [ServiceController::class, 'categoryStore'])
        ->middleware('admin.can:transport,create')->name('provider-categories.store');
    Route::delete('/provider-categories/{category}', [ServiceController::class, 'categoryDestroy'])
        ->middleware('admin.can:transport,delete')->name('provider-categories.destroy');
    Route::get('/provider-services', [ServiceController::class, 'services'])
        ->middleware('admin.can:transport,view')->name('provider-services.index');
    Route::post('/provider-services/{service}/toggle', [ServiceController::class, 'serviceToggle'])
        ->middleware('admin.can:transport,edit')->name('provider-services.toggle');
    Route::get('/provider-workers', [ServiceController::class, 'workers'])
        ->middleware('admin.can:transport,view')->name('provider-workers.index');
    Route::post('/provider-workers/{worker}/toggle', [ServiceController::class, 'workerToggle'])
        ->middleware('admin.can:transport,edit')->name('provider-workers.toggle');
    Route::get('/provider-bookings', [ServiceController::class, 'bookings'])
        ->middleware('admin.can:transport,view')->name('provider-bookings.index');
    Route::get('/provider-bookings/{booking}', [ServiceController::class, 'bookingShow'])
        ->middleware('admin.can:transport,view')->name('provider-bookings.show');
    Route::post('/provider-bookings/{booking}/transition', [ServiceController::class, 'bookingTransition'])
        ->middleware('admin.can:transport,edit')->name('provider-bookings.transition');
    Route::post('/provider-bookings/{booking}/assign', [ServiceController::class, 'bookingAssign'])
        ->middleware('admin.can:transport,edit')->name('provider-bookings.assign');

    // Dine-in table bookings.
    Route::get('/dinein', [DineInController::class, 'index'])
        ->middleware('admin.can:transport,view')->name('dinein.index');
    Route::post('/dinein', [DineInController::class, 'store'])
        ->middleware('admin.can:transport,create')->name('dinein.store');
    Route::post('/dinein/{booking}/transition', [DineInController::class, 'transition'])
        ->middleware('admin.can:transport,edit')->name('dinein.transition');
    Route::delete('/dinein/{booking}', [DineInController::class, 'destroy'])
        ->middleware('admin.can:transport,delete')->name('dinein.destroy');

    Route::get('/wallet', [WalletController::class, 'entries'])
        ->middleware('admin.can:finance,view')->name('wallet.index');
    Route::get('/referrals', [WalletController::class, 'referrals'])
        ->middleware('admin.can:finance,view')->name('referrals.index');

    // Commerce follow-ups: reviews, subscriptions, gifts, disbursements, showcase.
    Route::get('/reviews', [ReviewController::class, 'index'])
        ->middleware('admin.can:orders,view')->name('reviews.index');
    Route::post('/reviews/{review}/moderate', [ReviewController::class, 'moderate'])
        ->middleware('admin.can:orders,edit')->name('reviews.moderate');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->middleware('admin.can:orders,edit')->name('reviews.destroy');
    Route::get('/review-criteria', [ReviewController::class, 'criteria'])
        ->middleware('admin.can:orders,view')->name('review-criteria.index');
    Route::post('/review-criteria', [ReviewController::class, 'criteriaStore'])
        ->middleware('admin.can:orders,edit')->name('review-criteria.store');
    Route::delete('/review-criteria/{criterion}', [ReviewController::class, 'criteriaDestroy'])
        ->middleware('admin.can:orders,edit')->name('review-criteria.destroy');

    Route::get('/subscriptions', [SubscriptionLedgerController::class, 'subscriptions'])
        ->middleware('admin.can:finance,view')->name('subscriptions.index');
    Route::get('/gift-orders', [SubscriptionLedgerController::class, 'gifts'])
        ->middleware('admin.can:finance,view')->name('gift-orders.index');
    Route::post('/gift-orders/{gift}/redeem', [SubscriptionLedgerController::class, 'redeem'])
        ->middleware('admin.can:finance,edit')->name('gift-orders.redeem');

    Route::get('/disbursements', [DisbursementController::class, 'index'])
        ->middleware('admin.can:finance,view')->name('disbursements.index');
    Route::get('/disbursements/create', [DisbursementController::class, 'create'])
        ->middleware('admin.can:finance,edit')->name('disbursements.create');
    Route::post('/disbursements', [DisbursementController::class, 'store'])
        ->middleware('admin.can:finance,edit')->name('disbursements.store');
    Route::get('/disbursements/{disbursement}', [DisbursementController::class, 'show'])
        ->middleware('admin.can:finance,view')->name('disbursements.show');
    Route::post('/disbursements/{disbursement}/pay', [DisbursementController::class, 'markPaid'])
        ->middleware('admin.can:finance,edit')->name('disbursements.pay');

    Route::get('/stories', [ShowcaseController::class, 'stories'])
        ->middleware('admin.can:content,view')->name('stories.index');
    Route::post('/stories/{story}/toggle', [ShowcaseController::class, 'storyToggle'])
        ->middleware('admin.can:content,edit')->name('stories.toggle');
    Route::delete('/stories/{story}', [ShowcaseController::class, 'storyDestroy'])
        ->middleware('admin.can:content,edit')->name('stories.destroy');
    Route::get('/filter-presets', [ShowcaseController::class, 'presets'])
        ->middleware('admin.can:content,view')->name('presets.index');
    Route::post('/filter-presets', [ShowcaseController::class, 'presetStore'])
        ->middleware('admin.can:content,edit')->name('presets.store');
    Route::delete('/filter-presets/{preset}', [ShowcaseController::class, 'presetDestroy'])
        ->middleware('admin.can:content,edit')->name('presets.destroy');

    // Support inbox: complaints, SOS, chat threads.
    Route::get('/complaints', [SupportController::class, 'complaints'])
        ->middleware('admin.can:content,view')->name('complaints.index');
    Route::post('/complaints/{complaint}/resolve', [SupportController::class, 'complaintResolve'])
        ->middleware('admin.can:content,edit')->name('complaints.resolve');
    Route::get('/sos', [SupportController::class, 'sos'])
        ->middleware('admin.can:content,view')->name('sos.index');
    Route::post('/sos/{alert}/resolve', [SupportController::class, 'sosResolve'])
        ->middleware('admin.can:content,edit')->name('sos.resolve');
    Route::get('/chats', [SupportController::class, 'chats'])
        ->middleware('admin.can:content,view')->name('chats.index');
    Route::get('/chats/{thread}', [SupportController::class, 'chatShow'])
        ->middleware('admin.can:content,view')->name('chats.show');
    Route::post('/chats/{thread}/close', [SupportController::class, 'chatClose'])
        ->middleware('admin.can:content,edit')->name('chats.close');

    // Engagement: slides, content blocks, scheduled pushes, manual email.
    Route::get('/slides', [EngagementController::class, 'slides'])
        ->middleware('admin.can:content,view')->name('slides.index');
    Route::post('/slides', [EngagementController::class, 'slideStore'])
        ->middleware('admin.can:content,create')->name('slides.store');
    Route::delete('/slides/{slide}', [EngagementController::class, 'slideDestroy'])
        ->middleware('admin.can:content,delete')->name('slides.destroy');
    Route::get('/blocks', [EngagementController::class, 'blocks'])
        ->middleware('admin.can:content,view')->name('blocks.index');
    Route::put('/blocks/{block}', [EngagementController::class, 'blockUpdate'])
        ->middleware('admin.can:content,edit')->name('blocks.update');
    Route::get('/scheduled', [EngagementController::class, 'scheduled'])
        ->middleware('admin.can:content,view')->name('scheduled.index');
    Route::post('/scheduled', [EngagementController::class, 'scheduleStore'])
        ->middleware('admin.can:content,create')->name('scheduled.store');
    Route::delete('/scheduled/{scheduled}', [EngagementController::class, 'scheduleDestroy'])
        ->middleware('admin.can:content,edit')->name('scheduled.destroy');
    Route::get('/email', [EmailController::class, 'compose'])
        ->middleware('admin.can:content,view')->name('email.compose');
    Route::post('/email', [EmailController::class, 'send'])
        ->middleware('admin.can:content,create')->name('email.send');

    // Ops: maintenance, locale, live map.
    Route::get('/ops/maintenance', [OpsController::class, 'maintenance'])
        ->middleware('admin.can:content,view')->name('ops.maintenance');
    Route::post('/ops/maintenance', [OpsController::class, 'maintenanceToggle'])
        ->middleware('admin.can:content,edit')->name('ops.maintenance.toggle');
    Route::post('/ops/locale', [OpsController::class, 'locale'])->name('ops.locale');
    Route::get('/ops/map', [OpsController::class, 'map'])
        ->middleware('admin.can:content,view')->name('ops.map');

    // Reports (D10). Read-only analytics over orders.
    Route::get('/reports/sales', [ReportController::class, 'sales'])
        ->middleware('admin.can:reports,view')->name('reports.sales');
    Route::get('/reports/sales/export', [ReportController::class, 'salesExport'])
        ->middleware('admin.can:reports,view')->name('reports.salesExport');
});
