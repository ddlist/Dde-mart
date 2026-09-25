<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSectionRequest;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — sections (business verticals). Original controller.
 * Delete is blocked while categories, brands, products, or banners use the section.
 */
class SectionController extends Controller
{
    public function index(Request $request): View
    {
        $sections = Section::withCount(['categories', 'brands', 'products', 'banners'])
            ->when($request->string('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.catalog.sections.index', compact('sections'));
    }

    public function create(): View
    {
        return view('admin.catalog.sections.form', $this->formData(new Section()));
    }

    public function store(SaveSectionRequest $request): RedirectResponse
    {
        $section = Section::create($this->payload($request));

        return redirect()->route('admin.sections.index')->with('success', "Section '{$section->name}' created.");
    }

    public function edit(Section $section): View
    {
        return view('admin.catalog.sections.form', $this->formData($section));
    }

    public function update(SaveSectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($this->payload($request, $section));

        return redirect()->route('admin.sections.index')->with('success', "Section '{$section->name}' updated.");
    }

    public function destroy(Section $section): RedirectResponse
    {
        $users = [
            'categories' => $section->categories()->count(),
            'brands' => $section->brands()->count(),
            'products' => $section->products()->count(),
            'banners' => $section->banners()->count(),
        ];

        if (array_sum($users) > 0) {
            $detail = collect($users)->filter()->map(fn ($c, $k) => "{$c} {$k}")->join(', ');

            return redirect()->route('admin.sections.index')
                ->with('error', "Cannot delete '{$section->name}': still used by {$detail}.");
        }

        ImageUploads::delete($section->image_path);
        $section->delete();

        return redirect()->route('admin.sections.index')->with('success', "Section '{$section->name}' deleted.");
    }

    protected function formData(Section $section): array
    {
        return [
            'section' => $section,
            'method' => $section->exists ? 'PUT' : 'POST',
            'action' => $section->exists
                ? route('admin.sections.update', $section)
                : route('admin.sections.store'),
        ];
    }

    protected function payload(SaveSectionRequest $request, ?Section $section = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($section?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace(
                $request->file('image'), $section?->image_path, 'sections'
            );
        }

        return $data;
    }
}
