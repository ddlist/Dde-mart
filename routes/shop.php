<?php

use App\Http\Controllers\Shop\AuthController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\DineInController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\FavoriteController;
use App\Http\Controllers\Shop\GiftController;
use App\Http\Controllers\Shop\LocationController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\ParcelController;
use App\Http\Controllers\Shop\RentalController;
use App\Http\Controllers\Shop\ServiceController;
use Illuminate\Support\Facades\Route;

/*
 * DDE-Mart storefront (original). Public shop: home, catalog, cart (session).
 * Customer accounts + checkout arrive next; this layer stays thin over
 * existing models/services (CartQuote for all pricing).
 */

Route::middleware('web')->name('shop.')->group(function () {
    Route::redirect('/', '/shop')->name('home-redirect');

    Route::prefix('shop')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/welcome', [HomeController::class, 'landing'])->name('landing');

        // Location + section context (session; replaces legacy cookie hacks).
        Route::get('/location', [LocationController::class, 'show'])->name('location');
        Route::post('/location', [LocationController::class, 'store'])->name('location.store');
        Route::post('/section', [LocationController::class, 'section'])->name('section.store');

        // Catalog browse.
        Route::get('/search', [CatalogController::class, 'search'])->name('search');
        Route::get('/categories/{category:slug}', [CatalogController::class, 'category'])->name('categories.show');
        Route::get('/brands/{brand:slug}', [CatalogController::class, 'brand'])->name('brands.show');
        Route::get('/stores/{store:slug}', [CatalogController::class, 'store'])->name('stores.show');
        Route::get('/products/{product:slug}', [CatalogController::class, 'product'])->name('products.show');
        Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

        // Session cart (server-priced via CartQuote).
        Route::get('/cart', [CartController::class, 'index'])->name('cart');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
        Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('/cart/coupon', [CartController::class, 'coupon'])->name('cart.coupon');

        // Shopper account (session guard `customer`).
        Route::middleware('guest:customer')->group(function () {
            Route::get('/login', [AuthController::class, 'login'])->name('login');
            Route::get('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/register', [AuthController::class, 'store'])->name('register.store');
            Route::post('/login', [AuthController::class, 'attempt'])->name('login.attempt');
            Route::post('/login/otp', [AuthController::class, 'otp'])->name('login.otp');
            Route::post('/login/otp/verify', [AuthController::class, 'otpVerify'])->name('login.otp.verify');
        });

        Route::middleware('auth:customer')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
            Route::put('/profile', [AuthController::class, 'profileUpdate'])->name('profile.update');

            // Unified checkout.
            Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
            Route::post('/checkout/place', [CheckoutController::class, 'place'])->name('checkout.place');
            Route::get('/checkout/callback/{gateway}', [CheckoutController::class, 'callback'])->name('checkout.callback');
            Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

            // My orders.
            Route::get('/orders', [OrderController::class, 'index'])->name('orders');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');

            // Parcel + rental + services + dine-in.
            Route::get('/parcel', [ParcelController::class, 'index'])->name('parcel');
            Route::post('/parcel/quote', [ParcelController::class, 'quote'])->name('parcel.quote');
            Route::post('/parcel/book', [ParcelController::class, 'store'])->name('parcel.book');
            Route::get('/parcel/orders', [ParcelController::class, 'mine'])->name('parcel.orders');
            Route::get('/parcel/orders/{parcelOrder}', [ParcelController::class, 'track'])->name('parcel.track');

            Route::get('/rental', [RentalController::class, 'index'])->name('rental');
            Route::post('/rental/book', [RentalController::class, 'store'])->name('rental.book');
            Route::get('/rental/orders', [RentalController::class, 'mine'])->name('rental.orders');
            Route::get('/rental/orders/{rentalOrder}', [RentalController::class, 'track'])->name('rental.track');

            Route::get('/services', [ServiceController::class, 'index'])->name('services');
            Route::get('/services/categories/{category}', [ServiceController::class, 'category'])->name('services.category');
            Route::post('/services/book', [ServiceController::class, 'book'])->name('services.book');
            Route::get('/bookings', [ServiceController::class, 'mine'])->name('bookings');
            Route::get('/bookings/{booking}', [ServiceController::class, 'track'])->name('bookings.track');

            Route::get('/dinein', [DineInController::class, 'index'])->name('dinein');
            Route::post('/dinein/book', [DineInController::class, 'store'])->name('dinein.book');

            // Gifts + favorites.
            Route::get('/gifts', [GiftController::class, 'index'])->name('gifts');
            Route::post('/gifts/buy', [GiftController::class, 'buy'])->name('gifts.buy');
            Route::post('/gifts/redeem', [GiftController::class, 'redeem'])->name('gifts.redeem');
            Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
            Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
        });
    });
});
