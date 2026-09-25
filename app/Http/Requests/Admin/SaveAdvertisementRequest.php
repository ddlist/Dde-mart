<?php

namespace App\Http\Requests\Admin;

/* DDE-Mart Admin — advertisement validation (original). */
class SaveAdvertisementRequest extends CatalogRequest
{
    protected string $group = 'promotions';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:30'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'profile' => ['nullable', 'image', 'max:2048'],
            'remove_cover' => ['nullable', 'boolean'],
            'remove_profile' => ['nullable', 'boolean'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'show_rating' => ['nullable', 'boolean'],
            'show_review' => ['nullable', 'boolean'],
            'payment_status' => ['nullable', 'in:pending,paid'],
        ];
    }
}
