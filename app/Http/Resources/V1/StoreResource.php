<?php

namespace App\Http\Resources\V1;

use App\Models\ItemReview;
use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * DDE-Mart API — store resource (original). Public storefront fields only —
 * no commission internals, no owner identity.
 */
class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'image' => Images::url($this->image_path),
            'section_id' => $this->section_id,
            'zone_id' => $this->zone_id,
            'is_open' => (bool) $this->is_open,
            'min_order' => (float) $this->min_order,
            'delivery_fee' => (float) $this->delivery_fee,
            'self_delivery' => (bool) $this->self_delivery,
            'rating_avg' => ($avg = ItemReview::where('store_id', $this->id)
                ->where('status', 'approved')->avg('rating')) !== null
                ? round((float) $avg, 1)
                : null,
            'rating_count' => ItemReview::where('store_id', $this->id)
                ->where('status', 'approved')->count(),
        ];
    }
}
