<?php

namespace App\Http\Requests\Admin;

/* DDE-Mart Admin — gift card validation (original). */
class SaveGiftCardRequest extends CatalogRequest
{
    protected string $group = 'promotions';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'message' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expiry_days' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
