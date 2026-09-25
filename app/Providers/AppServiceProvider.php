<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\LogOrderNotification;
use App\Listeners\SendOrderStatusPush;
use App\Models\Page;
use App\Models\Section;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderStatusChanged::class, LogOrderNotification::class);
        Event::listen(OrderStatusChanged::class, SendOrderStatusPush::class);

        // Auth endpoints: per-IP throttle. Per-phone OTP caps + attempt limits in
        // OtpCode are the primary abuse control; this is the outer backstop.
        RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(120, 1));

        // Storefront shared bits (nav, cart badge, footer). Tiny tables; safe per view.
        // NOTE: the layout component name doesn't match shop.*, so list it explicitly.
        View::composer(['shop.*', 'components.store-layout'], function ($view) {
            try {
                $view->with('navSections', Section::where('is_active', true)->orderBy('sort_order')->limit(8)->get());
                $view->with('footerPages', Page::where('is_active', true)->orderBy('name')->limit(6)->get());
            } catch (\Throwable) {
                $view->with('navSections', collect())->with('footerPages', collect());
            }

            $view->with('cartCount', collect(session('cart', []))->sum('quantity'));
            $view->with('shopSiteName', \App\Models\Setting::get('site_name', 'DDE-Mart'));
        });
    }
}
