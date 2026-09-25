<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — catalog browse (original). Active records only,
 * section-scoped when the shopper picked one.
 */
class CatalogController extends Controller
{
    protected function sectionId(): ?int
    {
        return session('shop.section_id');
    }

    public function search(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $products = Product::where('is_active', true)
            ->when($this->sectionId(), fn ($query, $id) => $query->where('section_id', $id))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(24)->withQueryString();

        $stores = Store::where('status', 'active')
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(6)->get();

        return view('shop.search', compact('products', 'stores', 'q'));
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        $products = Product::with(['brand'])->where('is_active', true)
            ->where('category_id', $category->id)
            ->orderBy('sort_order')
            ->paginate(24);

        return view('shop.category', compact('category', 'products'));
    }

    public function brand(Brand $brand): View
    {
        abort_unless($brand->is_active, 404);

        $products = Product::where('is_active', true)
            ->where('brand_id', $brand->id)
            ->orderBy('sort_order')
            ->paginate(24);

        return view('shop.brand', compact('brand', 'products'));
    }

    public function store(Store $store): View
    {
        abort_unless($store->status === 'active', 404);

        $store->loadCount('products');

        $products = Product::where('is_active', true)
            ->where('vendor_id', $store->id)
            ->orderBy('sort_order')
            ->paginate(24);

        return view('shop.store', compact('store', 'products'));
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category', 'section', 'addons', 'attributeValues.attribute', 'store']);

        $related = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->orderByDesc('id')->limit(4)->get();

        $reviews = \App\Models\ItemReview::where('product_id', $product->id)
            ->where('status', 'approved')
            ->orderByDesc('id')->limit(5)->get();

        return view('shop.product', compact('product', 'related', 'reviews'));
    }
}
