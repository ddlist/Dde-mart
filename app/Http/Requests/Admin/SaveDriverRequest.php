<?php

namespace App\Http\Requests\Admin;

use App\Models\Driver;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — driver validation (original). */
class SaveDriverRequest extends CatalogRequest
{
    protected string $group = 'drivers';

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(Driver::KINDS)],
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'vehicle_info' => ['nullable', 'string', 'max:255'],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'store_id' => ['nullable', 'integer', Rule::exists('stores', 'id')],
            'owner_id' => ['nullable', 'integer', Rule::exists('owners', 'id')],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_branch' => ['nullable', 'string', 'max:150'],
            'bank_holder' => ['nullable', 'string', 'max:150'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'bank_other' => ['nullable', 'string', 'max:255'],
        ];
    }
}
