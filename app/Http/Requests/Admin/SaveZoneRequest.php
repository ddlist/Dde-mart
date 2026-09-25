<?php

namespace App\Http\Requests\Admin;

use App\Models\Notification;

/* DDE-Mart Admin — zone validation (original). */
class SaveZoneRequest extends CatalogRequest
{
    protected string $group = 'content';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'min:0.1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
