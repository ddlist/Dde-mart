<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — currency validation (original). */
class SaveCurrencyRequest extends CatalogRequest
{
    protected string $group = 'finance';

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('currencies', 'code')->ignore($this->currentId())],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'decimals' => ['nullable', 'integer', 'min:0', 'max:4'],
            'symbol_at_right' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
