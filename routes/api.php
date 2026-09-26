<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DriverApiController;
use App\Http\Controllers\Api\V1\EngagementController;
use App\Http\Controllers\Api\V1\LifeApiController;
use App\Http\Controllers\Api\V1\ReviewApiController;
use App\Http\Controllers\Api\V1\TransportApiController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\VendorApiController;
use App\Http\Controllers\Api\V1\WorkAuthController;
use Illuminate\Support\Facades\Route;

/*
 * DDE-Mart API v1 (original). JSON only; Sanctum tokens with audience abilities.
 * Public browse is open; account routes need auth:sanctum + abilities.
 */

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public catalog + content feeds.
    Route::get('/sections', [CatalogController::class, 'sections'])->name('sections');
    Route::get('/notifications', [EngagementController::class, 'notifications'])->name('notifications');
    Route::get('/pages', [EngagementController::class, 'pages'])->name('pages');
    Route::get('/pages/{slug}', [EngagementController::class, 'page'])->name('pages.show');
    Route::get('/banners', [EngagementController::class, 'banners'])->name('banners');
    Route::get('/ads', [EngagementController::class, 'ads'])->name('ads');
    Route::get('/settings', [EngagementController::class, 'settings'])->name('settings');
    Route::get('/onboarding', [EngagementController::class, 'onboarding'])->name('onboarding');
    Route::get('/languages', [EngagementController::class, 'languages'])->name('languages');
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('/products', [CatalogController::class, 'products'])->name('products');
    Route::get('/products/{product}', [CatalogController::class, 'product'])->name('products.show');
    Route::get('/stores', [CatalogController::class, 'stores'])->name('stores');
    Route::get('/stores/{store}', [CatalogController::class, 'store'])->name('stores.show');

    // Customer auth (throttled).
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/auth/otp/request', [AuthController::class, 'otpRequest'])->name('auth.otp.request');
        Route::post('/auth/otp/verify', [AuthController::class, 'otpVerify'])->name('auth.otp.verify');
    });

    // Authenticated customer routes.
    Route::middleware(['auth:sanctum', 'abilities:customer'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Commerce.
        Route::post('/cart/quote', [CheckoutController::class, 'quote'])->name('cart.quote');
        Route::post('/coupons/validate', [CheckoutController::class, 'validateCoupon'])->name('coupons.validate');
        Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AccountController::class, 'order'])->name('orders.show');
        Route::get('/wallet', [AccountController::class, 'wallet'])->name('wallet');

        // Engagement.
        Route::post('/push-tokens', [EngagementController::class, 'registerToken'])->name('push-tokens.store');
        Route::delete('/push-tokens', [EngagementController::class, 'unregisterToken'])->name('push-tokens.destroy');
        Route::post('/reviews', [ReviewApiController::class, 'store'])->name('reviews.store');
        Route::get('/reviews', [ReviewApiController::class, 'mine'])->name('reviews.mine');

        // Transport bookings.
        Route::get('/parcel/meta', [TransportApiController::class, 'parcelMeta'])->name('parcel.meta');
        Route::post('/parcel/quote', [TransportApiController::class, 'parcelQuote'])->name('parcel.quote');
        Route::post('/parcel/book', [TransportApiController::class, 'parcelBook'])->name('parcel.book');
        Route::get('/parcel/orders', [TransportApiController::class, 'parcelOrders'])->name('parcel.orders');
        Route::get('/parcel/orders/{order}', [TransportApiController::class, 'parcelTrack'])->name('parcel.track');
        Route::post('/parcel/orders/{order}/cancel', [TransportApiController::class, 'parcelCancel'])->name('parcel.cancel');

        Route::get('/rental/meta', [TransportApiController::class, 'rentalMeta'])->name('rental.meta');
        Route::post('/rental/book', [TransportApiController::class, 'rentalBook'])->name('rental.book');
        Route::get('/rental/orders', [TransportApiController::class, 'rentalOrders'])->name('rental.orders');
        Route::get('/rental/orders/{order}', [TransportApiController::class, 'rentalTrack'])->name('rental.track');
        Route::post('/rental/orders/{order}/cancel', [TransportApiController::class, 'rentalCancel'])->name('rental.cancel');

        Route::post('/rides/request', [TransportApiController::class, 'rideRequest'])->name('rides.request');
        Route::get('/rides', [TransportApiController::class, 'rides'])->name('rides');
        Route::get('/rides/{ride}', [TransportApiController::class, 'rideTrack'])->name('rides.track');
        Route::post('/rides/{ride}/cancel', [TransportApiController::class, 'rideCancel'])->name('rides.cancel');

        // Services, dine-in, gifts, favorites.
        Route::get('/service-categories', [LifeApiController::class, 'serviceCategories'])->name('service.categories');
        Route::get('/services', [LifeApiController::class, 'providerServices'])->name('services');
        Route::post('/services/book', [LifeApiController::class, 'serviceBook'])->name('services.book');
        Route::get('/service-bookings', [LifeApiController::class, 'serviceBookings'])->name('service.bookings');
        Route::get('/service-bookings/{booking}', [LifeApiController::class, 'serviceTrack'])->name('service.track');

        Route::post('/dinein/book', [LifeApiController::class, 'dineinBook'])->name('dinein.book');
        Route::get('/dinein/bookings', [LifeApiController::class, 'dineinMine'])->name('dinein.bookings');

        Route::get('/gift-cards', [LifeApiController::class, 'giftCards'])->name('gifts.cards');
        Route::post('/gifts/buy', [LifeApiController::class, 'giftBuy'])->name('gifts.buy');
        Route::post('/gifts/redeem', [LifeApiController::class, 'giftRedeem'])->name('gifts.redeem');

        Route::get('/favorites', [LifeApiController::class, 'favorites'])->name('favorites');
        Route::post('/favorites/toggle', [LifeApiController::class, 'favoriteToggle'])->name('favorites.toggle');
    });

    // Workforce auth (OTP-only, per role).
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('/work/auth/otp/request', [WorkAuthController::class, 'otpRequest'])->name('work.otp.request');
        Route::post('/work/auth/otp/verify', [WorkAuthController::class, 'otpVerify'])->name('work.otp.verify');
    });

    // Driver app surfaces.
    Route::middleware(['auth:sanctum', 'abilities:driver'])->prefix('driver')->name('driver.')->group(function () {
        Route::get('/me', [WorkAuthController::class, 'me'])->name('me');
        Route::post('/logout', [WorkAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [DriverApiController::class, 'profile'])->name('profile');
        Route::post('/availability', [DriverApiController::class, 'availability'])->name('availability');
        Route::get('/jobs', [DriverApiController::class, 'jobs'])->name('jobs');
        Route::get('/documents', [DriverApiController::class, 'documents'])->name('documents');
        Route::post('/documents', [DriverApiController::class, 'documentSubmit'])->name('documents.submit');
        Route::get('/payouts', [DriverApiController::class, 'payouts'])->name('payouts');
        Route::post('/payouts', [DriverApiController::class, 'payoutRequest'])->name('payouts.request');
        Route::post('/jobs/accept', [DriverApiController::class, 'jobAccept'])->name('jobs.accept');
        Route::post('/jobs/transition', [DriverApiController::class, 'jobTransition'])->name('jobs.transition');
        Route::post('/uploads', [UploadController::class, 'store'])->name('uploads');
    });

    // Vendor/owner surfaces (either ability suffices).
    Route::middleware(['auth:sanctum', 'ability:vendor,owner'])->prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/me', [WorkAuthController::class, 'me'])->name('me');
        Route::post('/logout', [WorkAuthController::class, 'logout'])->name('logout');
        Route::get('/stores', [VendorApiController::class, 'stores'])->name('stores');
        Route::post('/stores/{store}/toggle', [VendorApiController::class, 'toggleStore'])->name('stores.toggle');
        Route::get('/orders', [VendorApiController::class, 'orders'])->name('orders');
        Route::post('/orders/{order}/transition', [VendorApiController::class, 'orderTransition'])->name('orders.transition');
        Route::get('/products', [VendorApiController::class, 'products'])->name('products');
        Route::post('/products/{product}/toggle', [VendorApiController::class, 'toggleProduct'])->name('products.toggle');
        Route::get('/payouts', [VendorApiController::class, 'payouts'])->name('payouts');
        Route::post('/payouts', [VendorApiController::class, 'payoutRequest'])->name('payouts.request');
        Route::post('/uploads', [UploadController::class, 'store'])->name('uploads');
    });

    // Customer uploads.
    Route::middleware(['auth:sanctum', 'abilities:customer'])->group(function () {
        Route::post('/uploads', [UploadController::class, 'store'])->name('uploads');
    });
});
