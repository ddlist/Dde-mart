<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — banner validation (original). */
class SaveBannerRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'title' => ['required', 'string', 'max:150'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'redirect_type' => ['required', 'string', Rule::in(Banner::REDIRECT_TYPES)],
            'redirect_target' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'string', Rule::in(Banner::POSITIONS)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
