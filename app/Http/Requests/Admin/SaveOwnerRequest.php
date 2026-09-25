<?php

namespace App\Http\Requests\Admin;

/* DDE-Mart Admin — owner validation (original). */
class SaveOwnerRequest extends CatalogRequest
{
    protected string $group = 'owners';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
