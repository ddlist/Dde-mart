<?php

namespace App\Http\Resources\V1;

use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * DDE-Mart API — category resource (original).
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => Images::url($this->image_path),
            'section_id' => $this->section_id,
            'show_in_homepage' => (bool) $this->show_in_homepage,
        ];
    }
}
