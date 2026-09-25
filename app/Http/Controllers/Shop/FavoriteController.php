<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — favorites (original). Hearts for products + stores.
 */
class FavoriteController extends Controller
{
    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $favorites = Favorite::where('customer_id', $customer->id)->get();

        $products = Product::whereIn('id', $favorites->where('favorite_type', 'product')->pluck('favorite_id'))
            ->where('is_active', true)->get();
        $stores = Store::whereIn('id', $favorites->where('favorite_type', 'store')->pluck('favorite_id'))
            ->where('status', 'active')->get();

        return view('shop.favorites', compact('products', 'stores'));
    }

    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:product,store'],
            'id' => ['required', 'integer'],
        ]);

        $exists = $validated['type'] === 'product'
            ? Product::where('id', $validated['id'])->exists()
            : Store::where('id', $validated['id'])->exists();

        abort_unless($exists, 404);

        $favorite = Favorite::where('customer_id', Auth::guard('customer')->id())
            ->where('favorite_type', $validated['type'])
            ->where('favorite_id', $validated['id'])
            ->first();

        if ($favorite) {
            $favorite->delete();

            return redirect()->back()->with('success', 'Removed from favorites.');
        }

        Favorite::create([
            'customer_id' => Auth::guard('customer')->id(),
            'favorite_type' => $validated['type'],
            'favorite_id' => $validated['id'],
        ]);

        return redirect()->back()->with('success', 'Saved to favorites.');
    }
}
