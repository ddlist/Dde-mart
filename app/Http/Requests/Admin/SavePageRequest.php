<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — CMS page validation (original). */
class SavePageRequest extends CatalogRequest
{
    protected string $group = 'content';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('pages', 'slug')->ignore($this->currentId())],
            'body' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
