<?php

namespace App\Http\Resources\V1;

use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * DDE-Mart API — product resource (original). Absolute image URLs,
 * effective pricing, nested addons + attribute values.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price !== null ? (float) $this->discount_price : null,
            'selling_price' => $this->sellingPrice(),
            'quantity' => $this->quantity,
            'veg' => (bool) $this->veg,
            'image' => Images::url($this->image_path),
            'section_id' => $this->section_id,
            'category_id' => $this->category_id,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand?->only(['id', 'name'])),
            'store_id' => $this->vendor_id,
            'addons' => AddonResource::collection($this->whenLoaded('addons')),
            'attributes' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues
                ->groupBy('attribute_id')
                ->map(fn ($values, $attributeId) => [
                    'attribute_id' => $attributeId,
                    'name' => $values->first()->attribute?->name,
                    'values' => $values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])->values(),
                ])->values()),
        ];
    }
}
