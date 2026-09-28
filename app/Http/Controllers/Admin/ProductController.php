<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — products. Original controller.
 * Add-ons are replaced wholesale on update (simplest correct sync for small sets);
 * attribute links are synced via pivot. Product delete cascades addons + pivot
 * (DB cascades) and removes the stored image.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['section', 'category', 'brand'])
            ->when($request->string('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->integer('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->integer('brand'), fn ($q, $id) => $q->where('brand_id', $id))
            ->when($request->integer('section'), fn ($q, $id) => $q->where('section_id', $id))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.catalog.products.index', [
            'products' => $products,
            'sections' => Section::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.products.form', $this->formData(new Product()));
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        $product = Product::create($this->payload($request));
        $this->syncRelations($product, $request);

        return redirect()->route('admin.products.index')->with('success', "Product '{$product->name}' created.");
    }

    public function show(Product $product): View
    {
        $product->load(['section', 'category', 'brand', 'addons', 'attributeValues.attribute']);

        return view('admin.catalog.products.show', ['product' => $product]);
    }

    public function edit(Product $product): View
    {
        $product->load(['addons', 'attributeValues']);

        return view('admin.catalog.products.form', $this->formData($product));
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($this->payload($request, $product));
        $this->syncRelations($product, $request);

        return redirect()->route('admin.products.index')->with('success', "Product '{$product->name}' updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        ImageUploads::delete($product->image_path);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', "Product '{$product->name}' deleted.");
    }

    protected function formData(Product $product): array
    {
        return [
            'product' => $product,
            'sections' => Section::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'stores' => Store::orderBy('name')->get(),
            'attributes' => CatalogAttribute::with('values')->orderBy('name')->get(),
            'method' => $product->exists ? 'PUT' : 'POST',
            'action' => $product->exists
                ? route('admin.products.update', $product)
                : route('admin.products.store'),
        ];
    }

    protected function payload(SaveProductRequest $request, ?Product $product = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image', 'attributes', 'variants', 'addons']);
        $data['veg'] = $request->boolean('veg');
        $data['is_takeaway'] = $request->boolean('is_takeaway');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($product?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace(
                $request->file('image'), $product?->image_path, 'products'
            );
        }

        return $data;
    }

    protected function syncRelations(Product $product, SaveProductRequest $request): void
    {
        $ids = collect($request->input('attributes', []))
            ->merge(array_keys($request->input('variants', [])))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn ($id) => \App\Models\AttributeValue::whereKey($id)->exists())
            ->values();

        $pivot = [];
        foreach ($ids as $id) {
            $row = $request->input("variants.{$id}", []);
            $pivot[$id] = [
                'price_delta' => $row['price'] ?? null,
                'quantity' => $row['quantity'] ?? null,
            ];
        }
        $product->attributeValues()->sync($pivot);

        $specs = collect($request->input('specs', []))
            ->map(fn ($row) => ['label' => trim((string) ($row['label'] ?? '')), 'value' => trim((string) ($row['value'] ?? ''))])
            ->filter(fn ($row) => $row['label'] !== '' && $row['value'] !== '')
            ->values()
            ->all();
        $product->update(['specs' => $specs === [] ? null : $specs]);

        $product->addons()->delete();

        foreach (array_values($request->input('addons', [])) as $order => $addon) {
            if (trim((string) ($addon['name'] ?? '')) === '') {
                continue;
            }

            $product->addons()->create([
                'name' => $addon['name'],
                'price' => $addon['price'] ?? 0,
                'sort_order' => $order,
            ]);
        }
    }
}
