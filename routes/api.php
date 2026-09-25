<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DriverApiController;
use App\Http\Controllers\Api\V1\EngagementController;
use App\Http\Controllers\Api\V1\ReviewApiController;
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
        Route::post('/uploads', [UploadController::class, 'store'])->name('uploads');
    });

    // Vendor/owner surfaces (either ability suffices).
    Route::middleware(['auth:sanctum', 'ability:vendor,owner'])->prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/me', [WorkAuthController::class, 'me'])->name('me');
        Route::post('/logout', [WorkAuthController::class, 'logout'])->name('logout');
        Route::get('/stores', [VendorApiController::class, 'stores'])->name('stores');
        Route::post('/stores/{store}/toggle', [VendorApiController::class, 'toggleStore'])->name('stores.toggle');
        Route::get('/orders', [VendorApiController::class, 'orders'])->name('orders');
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
