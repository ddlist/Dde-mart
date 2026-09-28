<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCouponRequest;
use App\Models\Coupon;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — coupons (original controller). One table for all scopes. */
class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $coupons = Coupon::with(['section'])
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope', $request->input('scope')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.promotions.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.promotions.coupons.form', $this->formData(new Coupon()));
    }

    public function store(SaveCouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($this->payload($request));
        $coupon->update(['code' => strtoupper($coupon->code)]);

        return redirect()->route('admin.coupons.index')->with('success', "Coupon '{$coupon->code}' created.");
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.promotions.coupons.form', $this->formData($coupon));
    }

    public function update(SaveCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->payload($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('success', "Coupon '{$coupon->code}' updated.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        ImageUploads::delete($coupon->image_path);
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', "Coupon '{$coupon->code}' deleted.");
    }

    protected function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'sections' => Section::orderBy('name')->get(),
            'stores' => \App\Models\Store::orderBy('name')->get(),
            'method' => $coupon->exists ? 'PUT' : 'POST',
            'action' => $coupon->exists
                ? route('admin.coupons.update', $coupon)
                : route('admin.coupons.store'),
        ];
    }

    protected function payload(SaveCouponRequest $request, ?Coupon $coupon = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['code'] = strtoupper($data['code']);
        $data['is_public'] = $request->boolean('is_public');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($coupon?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace($request->file('image'), $coupon?->image_path, 'coupons');
        }

        return $data;
    }
}
