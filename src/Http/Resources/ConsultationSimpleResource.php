<?php

namespace EscolaLms\Consultations\Http\Resources;

use EscolaLms\Auth\Traits\ResourceExtandable;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationSimpleResource extends JsonResource
{
    use ResourceExtandable;

    public function toArray($request)
    {
        $fields = [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'name' => $this->name,
            'base_price' => $this->base_price,
            'author_id' => $this->author_id,
            'status' => $this->status,
            'description' => $this->description,
        ];
        return self::apply($fields, $this);
    }
}
