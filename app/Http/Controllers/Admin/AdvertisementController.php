<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAdvertisementRequest;
use App\Models\Advertisement;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — advertisements (original controller). Status machine included. */
class AdvertisementController extends Controller
{
    public function index(Request $request): View
    {
        $ads = Advertisement::query()
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.promotions.ads.index', ['ads' => $ads, 'statuses' => Advertisement::STATUSES]);
    }

    public function create(): View
    {
        return view('admin.promotions.ads.form', $this->formData(new Advertisement()));
    }

    public function store(SaveAdvertisementRequest $request): RedirectResponse
    {
        $ad = Advertisement::create($this->payload($request));

        return redirect()->route('admin.ads.index')->with('success', "Advertisement '{$ad->title}' created.");
    }

    public function edit(Advertisement $advertisement): View
    {
        return view('admin.promotions.ads.form', $this->formData($advertisement));
    }

    public function update(SaveAdvertisementRequest $request, Advertisement $advertisement): RedirectResponse
    {
        $advertisement->update($this->payload($request, $advertisement));

        return redirect()->route('admin.ads.index')->with('success', "Advertisement '{$advertisement->title}' updated.");
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        ImageUploads::delete($advertisement->cover_path);
        ImageUploads::delete($advertisement->profile_path);
        $advertisement->delete();

        return redirect()->route('admin.ads.index')->with('success', "Advertisement '{$advertisement->title}' deleted.");
    }

    public function transition(Request $request, Advertisement $advertisement): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! in_array($to, Advertisement::STATUSES, true) || ! $advertisement->canTransitionTo($to)) {
            return redirect()->route('admin.ads.index')->with('error', "Cannot move ad to [{$to}].");
        }

        $advertisement->update(['status' => $to]);

        return redirect()->route('admin.ads.index')->with('success', "Ad moved to {$to}.");
    }

    protected function formData(Advertisement $advertisement): array
    {
        return [
            'ad' => $advertisement,
            'method' => $advertisement->exists ? 'PUT' : 'POST',
            'action' => $advertisement->exists
                ? route('admin.ads.update', $advertisement)
                : route('admin.ads.store'),
        ];
    }

    protected function payload(SaveAdvertisementRequest $request, ?Advertisement $ad = null): array
    {
        $data = $request->safe()->except(['cover', 'profile', 'remove_cover', 'remove_profile', 'payment_status']);
        $data['show_rating'] = $request->boolean('show_rating');
        $data['show_review'] = $request->boolean('show_review');
        $data['payment_status'] = $request->input('payment_status') === 'paid' ? 'paid' : 'pending';

        foreach (['cover' => 'cover_path', 'profile' => 'profile_path'] as $input => $column) {
            if ($request->boolean("remove_{$input}")) {
                ImageUploads::delete($ad?->{$column});
                $data[$column] = null;
            } else {
                $data[$column] = ImageUploads::replace($request->file($input), $ad?->{$column}, 'ads');
            }
        }

        return $data;
    }
}
