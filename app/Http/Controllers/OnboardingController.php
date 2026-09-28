<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Section;
use App\Models\Store;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart — public vendor self-onboarding (original controller).
 * One form creates a pending Owner + pending Store for staff review.
 * Nothing here is authenticated; abuse is bounded by phone uniqueness
 * plus throttle middleware on the route.
 */
class OnboardingController extends Controller
{
    public function create(): View
    {
        return view('onboarding.vendor', [
            'sections' => Section::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:50', 'unique:owners,phone'],
            'email' => ['nullable', 'email', 'max:255'],
            'store_name' => ['required', 'string', 'max:200'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $owner = Owner::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'status' => 'pending',
        ]);

        $store = Store::create([
            'name' => $validated['store_name'],
            'phone' => $validated['store_phone'] ?? $validated['phone'],
            'address' => $validated['address'] ?? null,
            'section_id' => $validated['section_id'] ?? null,
            'owner_id' => $owner->id,
            'owner_name' => $validated['name'],
            'status' => 'pending',
            'is_open' => false,
        ]);

        if ($request->hasFile('logo')) {
            $store->update([
                'image_path' => ImageUploads::store($request->file('logo'), 'stores'),
            ]);
        }

        return redirect()->route('onboarding.done');
    }

    public function done(): View
    {
        return view('onboarding.done');
    }
}
