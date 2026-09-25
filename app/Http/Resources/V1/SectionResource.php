<?php

namespace App\Http\Resources\V1;

use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * DDE-Mart API — section resource (original).
 */
class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'service_type' => $this->service_type,
            'color' => $this->color,
            'image' => Images::url($this->image_path),
        ];
    }
}
