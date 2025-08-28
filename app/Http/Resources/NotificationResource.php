<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = json_decode($this->data, true);
        return [
            'id'        => $this->id,
            'title'     => $data['title'] ?? null,
            'message'   => $data['message'] ?? null,
            'read_at'   => $this->read_at ? $this->read_at->format('Y-m-d H:i:s') : null,
            'created_at'=> $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
