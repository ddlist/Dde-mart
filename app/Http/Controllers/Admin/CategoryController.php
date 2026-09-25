<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — categories. Original controller.
 * Delete is blocked while products use the category.
 */
class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::with(['section'])->withCount('products')
            ->when($request->string('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->integer('section'), fn ($q, $id) => $q->where('section_id', $id))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(15)->withQueryString();

        $sections = Section::orderBy('name')->get();

        return view('admin.catalog.categories.index', compact('categories', 'sections'));
    }

    public function create(): View
    {
        return view('admin.catalog.categories.form', $this->formData(new Category()));
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($this->payload($request));

        return redirect()->route('admin.categories.index')->with('success', "Category '{$category->name}' created.");
    }

    public function edit(Category $category): View
    {
        return view('admin.catalog.categories.form', $this->formData($category));
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($this->payload($request, $category));

        return redirect()->route('admin.categories.index')->with('success', "Category '{$category->name}' updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')->with(
                'error', "Cannot delete '{$category->name}': {$category->products()->count()} product(s) still use it."
            );
        }

        ImageUploads::delete($category->image_path);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', "Category '{$category->name}' deleted.");
    }

    protected function formData(Category $category): array
    {
        return [
            'category' => $category,
            'sections' => Section::orderBy('name')->get(),
            'method' => $category->exists ? 'PUT' : 'POST',
            'action' => $category->exists
                ? route('admin.categories.update', $category)
                : route('admin.categories.store'),
        ];
    }

    protected function payload(SaveCategoryRequest $request, ?Category $category = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_homepage'] = $request->boolean('show_in_homepage');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($category?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace(
                $request->file('image'), $category?->image_path, 'categories'
            );
        }

        return $data;
    }
}
