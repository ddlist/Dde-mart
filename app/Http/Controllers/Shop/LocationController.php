<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — location + section context (original).
 * Session-based; replaces legacy cookie hacks (section_id/address cookies).
 */
class LocationController extends Controller
{
    public function show(): View
    {
        $sections = Section::where('is_active', true)->orderBy('sort_order')->get();

        return view('shop.location', compact('sections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        session([
            'shop.address' => $validated['label'] ?? null,
            'shop.lat' => $validated['latitude'] ?? null,
            'shop.lng' => $validated['longitude'] ?? null,
        ]);

        if (! empty($validated['section_id'])) {
            session(['shop.section_id' => (int) $validated['section_id']]);
        }

        return redirect()->route('shop.home')->with('success', 'Location saved.');
    }

    public function section(Request $request): RedirectResponse
    {
        $validated = $request->validate(['section_id' => ['nullable', 'integer', 'exists:sections,id']]);

        if (empty($validated['section_id'])) {
            session()->forget('shop.section_id');
        } else {
            session(['shop.section_id' => (int) $validated['section_id']]);
        }

        return redirect()->back()->with('success', 'Section updated.');
    }
}
