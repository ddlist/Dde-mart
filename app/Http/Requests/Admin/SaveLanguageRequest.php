<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — language validation (original). */
class SaveLanguageRequest extends CatalogRequest
{
    protected string $group = 'content';

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('languages', 'code')->ignore($this->currentId())],
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
