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
            'content' => $this->content,
            'read' => $this->read,
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'receiver_id' => $this->receiver_id,
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}