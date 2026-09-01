<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayoutSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'key'         => $this->key,
            'layout_type' => $this->layout_type,
            'order'       => $this->order,
            'is_active'   => $this->is_active,
            'blocks'      => LayoutSectionBlockResource::collection($this->whenLoaded('blocks')),
        ];    }
}
