<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ContentBlock;
use App\Models\Coupon;
use App\Models\Driver;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — home (original). Sections, banners, public promos,
 * popular stores/products scoped to the session section when set.
 */
class HomeController extends Controller
{
    public function landing(): View
    {
        return view('shop.landing', [
            'sections' => Section::where('is_active', true)->orderBy('sort_order')->limit(4)->get(),
            'blocks' => ContentBlock::where('is_active', true)->whereIn('key', ['homepage_hero', 'homepage_promos'])->get(),
            'stats' => [
                'stores' => Store::where('status', 'active')->count(),
                'products' => Product::where('is_active', true)->count(),
                'drivers' => Driver::where('status', 'active')->count(),
            ],
        ]);
    }

    public function index(): View
    {
        $sectionId = session('shop.section_id');

        $sections = Section::where('is_active', true)->orderBy('sort_order')->get();
        $banners = Banner::where('is_active', true)
            ->when($sectionId, fn ($q) => $q->where(fn ($w) => $w->whereNull('section_id')->orWhere('section_id', $sectionId)))
            ->orderBy('sort_order')->limit(6)->get();
        $promos = Coupon::where('is_active', true)->where('is_public', true)
            ->when($sectionId, fn ($q) => $q->where(fn ($w) => $w->whereNull('section_id')->orWhere('section_id', $sectionId)))
            ->orderByDesc('id')->limit(4)->get();
        $stores = Store::where('status', 'active')
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderBy('name')->limit(8)->get();
        $products = Product::where('is_active', true)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderByDesc('id')->limit(8)->get();

        return view('shop.home', compact('sections', 'banners', 'promos', 'stores', 'products'));
    }
}
