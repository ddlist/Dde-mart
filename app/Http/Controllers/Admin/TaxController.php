<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTaxRequest;
use App\Models\Section;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — taxes (original controller). */
class TaxController extends Controller
{
    public function index(Request $request): View
    {
        $taxes = Tax::with(['section'])
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.finance.taxes.index', ['taxes' => $taxes]);
    }

    public function create(): View
    {
        return view('admin.finance.taxes.form', $this->formData(new Tax()));
    }

    public function store(SaveTaxRequest $request): RedirectResponse
    {
        $tax = Tax::create($this->payload($request));

        return redirect()->route('admin.taxes.index')->with('success', "Tax '{$tax->title}' created.");
    }

    public function edit(Tax $tax): View
    {
        return view('admin.finance.taxes.form', $this->formData($tax));
    }

    public function update(SaveTaxRequest $request, Tax $tax): RedirectResponse
    {
        $tax->update($this->payload($request, $tax));

        return redirect()->route('admin.finance.taxes.index')->with('success', "Tax '{$tax->title}' updated.");
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return redirect()->route('admin.taxes.index')->with('success', "Tax '{$tax->title}' deleted.");
    }

    protected function formData(Tax $tax): array
    {
        return [
            'tax' => $tax,
            'sections' => Section::orderBy('name')->get(),
            'method' => $tax->exists ? 'PUT' : 'POST',
            'action' => $tax->exists
                ? route('admin.taxes.update', $tax)
                : route('admin.taxes.store'),
        ];
    }

    protected function payload(SaveTaxRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
