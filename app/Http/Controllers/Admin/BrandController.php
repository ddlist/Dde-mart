<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBrandRequest;
use App\Models\Brand;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — brands. Original controller.
 * Delete is blocked while products use the brand.
 */
class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $brands = Brand::with(['section'])->withCount('products')
            ->when($request->string('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->integer('section'), fn ($q, $id) => $q->where('section_id', $id))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        $sections = Section::orderBy('name')->get();

        return view('admin.catalog.brands.index', compact('brands', 'sections'));
    }

    public function create(): View
    {
        return view('admin.catalog.brands.form', $this->formData(new Brand()));
    }

    public function store(SaveBrandRequest $request): RedirectResponse
    {
        $brand = Brand::create($this->payload($request));

        return redirect()->route('admin.brands.index')->with('success', "Brand '{$brand->name}' created.");
    }

    public function edit(Brand $brand): View
    {
        return view('admin.catalog.brands.form', $this->formData($brand));
    }

    public function update(SaveBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->payload($request, $brand));

        return redirect()->route('admin.brands.index')->with('success', "Brand '{$brand->name}' updated.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return redirect()->route('admin.brands.index')->with(
                'error', "Cannot delete '{$brand->name}': {$brand->products()->count()} product(s) still use it."
            );
        }

        ImageUploads::delete($brand->image_path);
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', "Brand '{$brand->name}' deleted.");
    }

    protected function formData(Brand $brand): array
    {
        return [
            'brand' => $brand,
            'sections' => Section::orderBy('name')->get(),
            'method' => $brand->exists ? 'PUT' : 'POST',
            'action' => $brand->exists
                ? route('admin.brands.update', $brand)
                : route('admin.brands.store'),
        ];
    }

    protected function payload(SaveBrandRequest $request, ?Brand $brand = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($brand?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace(
                $request->file('image'), $brand?->image_path, 'brands'
            );
        }

        return $data;
    }
}
