<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — currencies (original controller). Exactly one default. */
class CurrencyController extends Controller
{
    public function index(): View
    {
        $currencies = Currency::orderByDesc('is_default')->orderBy('code')->paginate(15);

        return view('admin.finance.currencies.index', compact('currencies'));
    }

    public function create(): View
    {
        return view('admin.finance.currencies.form', $this->formData(new Currency()));
    }

    public function store(SaveCurrencyRequest $request): RedirectResponse
    {
        $currency = Currency::create($this->payload($request));
        $this->ensureSingleDefault($currency);

        return redirect()->route('admin.currencies.index')->with('success', "Currency '{$currency->code}' created.");
    }

    public function edit(Currency $currency): View
    {
        return view('admin.finance.currencies.form', $this->formData($currency));
    }

    public function update(SaveCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $currency->update($this->payload($request, $currency));
        $this->ensureSingleDefault($currency);

        return redirect()->route('admin.currencies.index')->with('success', "Currency '{$currency->code}' updated.");
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return redirect()->route('admin.currencies.index')
                ->with('error', 'Cannot delete the default currency. Set another default first.');
        }

        $currency->delete();

        return redirect()->route('admin.currencies.index')->with('success', "Currency '{$currency->code}' deleted.");
    }

    protected function formData(Currency $currency): array
    {
        return [
            'currency' => $currency,
            'method' => $currency->exists ? 'PUT' : 'POST',
            'action' => $currency->exists
                ? route('admin.currencies.update', $currency)
                : route('admin.currencies.store'),
        ];
    }

    protected function payload(SaveCurrencyRequest $request, ?Currency $currency = null): array
    {
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);
        $data['symbol_at_right'] = $request->boolean('symbol_at_right');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    protected function ensureSingleDefault(Currency $currency): void
    {
        if ($currency->is_default) {
            Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
        } elseif (! Currency::where('is_default', true)->exists()) {
            // Never leave the shop without a default (e.g. first currency).
            $currency->update(['is_default' => true]);
        }
    }
}
