<?php

namespace App\Http\Requests\Admin;

use App\Models\Tax;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — tax validation (original). */
class SaveTaxRequest extends CatalogRequest
{
    protected string $group = 'finance';

    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Tax::TYPES)],
            'value' => ['required', 'numeric', 'min:0'],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
