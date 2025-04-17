<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ResourceResource;

class ResourceInteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'resource_id' => $this->resource_id,
            'resource' => new ResourceResource($this->whenLoaded('resource')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}