<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBannerRequest;
use App\Models\Banner;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — banners. Original controller.
 */
class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $banners = Banner::with(['section'])
            ->when($request->string('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->integer('section'), fn ($q, $id) => $q->where('section_id', $id))
            ->orderBy('sort_order')->orderBy('title')
            ->paginate(15)->withQueryString();

        $sections = Section::orderBy('name')->get();

        return view('admin.catalog.banners.index', compact('banners', 'sections'));
    }

    public function create(): View
    {
        return view('admin.catalog.banners.form', $this->formData(new Banner()));
    }

    public function store(SaveBannerRequest $request): RedirectResponse
    {
        $banner = Banner::create($this->payload($request));

        return redirect()->route('admin.banners.index')->with('success', "Banner '{$banner->title}' created.");
    }

    public function edit(Banner $banner): View
    {
        return view('admin.catalog.banners.form', $this->formData($banner));
    }

    public function update(SaveBannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->payload($request, $banner));

        return redirect()->route('admin.banners.index')->with('success', "Banner '{$banner->title}' updated.");
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        ImageUploads::delete($banner->image_path);
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', "Banner '{$banner->title}' deleted.");
    }

    protected function formData(Banner $banner): array
    {
        return [
            'banner' => $banner,
            'sections' => Section::orderBy('name')->get(),
            'method' => $banner->exists ? 'PUT' : 'POST',
            'action' => $banner->exists
                ? route('admin.banners.update', $banner)
                : route('admin.banners.store'),
        ];
    }

    protected function payload(SaveBannerRequest $request, ?Banner $banner = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($banner?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace(
                $request->file('image'), $banner?->image_path, 'banners'
            );
        }

        return $data;
    }
}
