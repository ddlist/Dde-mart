<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveGiftCardRequest;
use App\Models\GiftCard;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — gift cards (original controller). */
class GiftCardController extends Controller
{
    public function index(Request $request): View
    {
        $cards = GiftCard::query()
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.promotions.gifts.index', ['cards' => $cards]);
    }

    public function create(): View
    {
        return view('admin.promotions.gifts.form', $this->formData(new GiftCard()));
    }

    public function store(SaveGiftCardRequest $request): RedirectResponse
    {
        $card = GiftCard::create($this->payload($request));

        return redirect()->route('admin.gifts.index')->with('success', "Gift card '{$card->title}' created.");
    }

    public function edit(GiftCard $giftCard): View
    {
        return view('admin.promotions.gifts.form', $this->formData($giftCard));
    }

    public function update(SaveGiftCardRequest $request, GiftCard $giftCard): RedirectResponse
    {
        $giftCard->update($this->payload($request, $giftCard));

        return redirect()->route('admin.gifts.index')->with('success', "Gift card '{$giftCard->title}' updated.");
    }

    public function destroy(GiftCard $giftCard): RedirectResponse
    {
        ImageUploads::delete($giftCard->image_path);
        $giftCard->delete();

        return redirect()->route('admin.gifts.index')->with('success', "Gift card '{$giftCard->title}' deleted.");
    }

    protected function formData(GiftCard $card): array
    {
        return [
            'card' => $card,
            'method' => $card->exists ? 'PUT' : 'POST',
            'action' => $card->exists
                ? route('admin.gifts.update', $card)
                : route('admin.gifts.store'),
        ];
    }

    protected function payload(SaveGiftCardRequest $request, ?GiftCard $card = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($card?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace($request->file('image'), $card?->image_path, 'gifts');
        }

        return $data;
    }
}
