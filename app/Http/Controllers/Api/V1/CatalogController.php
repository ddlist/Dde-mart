<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Http\Resources\V1\ProductResource;
use App\Http\Resources\V1\SectionResource;
use App\Http\Resources\V1\StoreResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/*
 * DDE-Mart API — public catalog browse (original). No auth required.
 * Only active records; paginated (max 50/page).
 */
class CatalogController extends Controller
{
    protected function perPage(Request $request): int
    {
        return min(50, max(1, (int) $request->input('per_page', 15)));
    }

    public function sections(): AnonymousResourceCollection
    {
        return SectionResource::collection(
            Section::where('is_active', true)->orderBy('sort_order')->paginate($this->perPage(request()))
        );
    }

    public function categories(Request $request): AnonymousResourceCollection
    {
        $categories = Category::where('is_active', true)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', (int) $request->input('section_id')))
            ->orderBy('sort_order')
            ->paginate($this->perPage($request));

        return CategoryResource::collection($categories);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = Product::with(['brand', 'addons', 'attributeValues.attribute'])
            ->where('is_active', true)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', (int) $request->input('section_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', (int) $request->input('brand_id')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('vendor_id', (int) $request->input('store_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('sort_order')
            ->paginate($this->perPage($request));

        return ProductResource::collection($products);
    }

    public function product(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'addons', 'attributeValues.attribute', 'category', 'section']);

        return new ProductResource($product);
    }

    public function stores(Request $request): AnonymousResourceCollection
    {
        $stores = Store::where('status', 'active')
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', (int) $request->input('section_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return StoreResource::collection($stores);
    }

    public function store(Store $store)
    {
        abort_unless($store->status === 'active', 404);

        return new StoreResource($store);
    }
}
