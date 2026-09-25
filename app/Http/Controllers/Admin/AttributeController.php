<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAttributeRequest;
use App\Models\CatalogAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — catalog attributes with inline values. Original controller.
 * Values are edited inline on the attribute form (update-by-id, create-new,
 * delete-removed). Delete is blocked while values exist (values in use by
 * products keep their pivot rows; detach first via product edit).
 */
class AttributeController extends Controller
{
    public function index(Request $request): View
    {
        $attributes = CatalogAttribute::withCount('values')
            ->when($request->string('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.catalog.attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        return view('admin.catalog.attributes.form', $this->formData(new CatalogAttribute()));
    }

    public function store(SaveAttributeRequest $request): RedirectResponse
    {
        $attribute = CatalogAttribute::create([
            'name' => $request->string('name'),
            'slug' => $request->input('slug'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncValues($attribute, $request->input('values', []));

        return redirect()->route('admin.attributes.index')->with('success', "Attribute '{$attribute->name}' created.");
    }

    public function edit(CatalogAttribute $attribute): View
    {
        $attribute->load('values');

        return view('admin.catalog.attributes.form', $this->formData($attribute));
    }

    public function update(SaveAttributeRequest $request, CatalogAttribute $attribute): RedirectResponse
    {
        $attribute->update([
            'name' => $request->string('name'),
            'slug' => $request->input('slug') ?? $attribute->slug,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncValues($attribute, $request->input('values', []));

        return redirect()->route('admin.attributes.index')->with('success', "Attribute '{$attribute->name}' updated.");
    }

    public function destroy(CatalogAttribute $attribute): RedirectResponse
    {
        if ($attribute->values()->exists()) {
            return redirect()->route('admin.attributes.index')->with(
                'error', "Cannot delete '{$attribute->name}': remove its {$attribute->values()->count()} value(s) first."
            );
        }

        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', "Attribute '{$attribute->name}' deleted.");
    }

    protected function formData(CatalogAttribute $attribute): array
    {
        return [
            'attribute' => $attribute,
            'method' => $attribute->exists ? 'PUT' : 'POST',
            'action' => $attribute->exists
                ? route('admin.attributes.update', $attribute)
                : route('admin.attributes.store'),
        ];
    }

    /** rows: [{id?, value}] → update kept, create new, delete removed. */
    protected function syncValues(CatalogAttribute $attribute, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $order => $row) {
            $value = trim((string) ($row['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            if (! empty($row['id'])) {
                $record = $attribute->values()->find($row['id']);

                if ($record) {
                    $record->update(['value' => $value, 'sort_order' => $order]);
                    $keptIds[] = $record->id;
                }

                continue;
            }

            $keptIds[] = $attribute->values()->create([
                'value' => $value,
                'sort_order' => $order,
            ])->id;
        }

        $attribute->values()->whereNotIn('id', $keptIds)->delete();
    }
}
