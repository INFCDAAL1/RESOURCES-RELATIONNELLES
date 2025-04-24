<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'published' => $this->published,
            'validated' => $this->validated,
            'link' => $this->link,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'download_url' => $this->download_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Related resources
            'type' => $this->whenLoaded('type'),
            'category' => $this->whenLoaded('category'),
            'visibility' => $this->whenLoaded('visibility'),
            'origin' => $this->whenLoaded('origin'),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}