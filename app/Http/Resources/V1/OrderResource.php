<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * DDE-Mart API — order resource (original). Customer-safe shape.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'delivery_charge' => (float) $this->delivery_charge,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'coupon_code' => $this->coupon_code,
            'notes' => $this->notes,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'driver' => $this->driver ? [
                'name' => $this->driver->name,
                'latitude' => $this->driver->latitude !== null ? (float) $this->driver->latitude : null,
                'longitude' => $this->driver->longitude !== null ? (float) $this->driver->longitude : null,
                'position_at' => $this->driver->location_updated_at?->toIso8601String(),
            ] : null,
            'items' => $this->items->map(fn ($item) => [
                'name' => $item->name,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'extras' => $item->extras ?? [],
                'subtotal' => (float) $item->subtotal,
            ]),
            'timeline' => $this->history->map(fn ($h) => [
                'from' => $h->from_status,
                'to' => $h->to_status,
                'note' => $h->note,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ];
    }
}
