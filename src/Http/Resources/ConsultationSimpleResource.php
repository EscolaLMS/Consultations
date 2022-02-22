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
            'active_from' => $this->active_from,
            'active_to' => $this->active_to,
            'name' => $this->name,
            'base_price' => $this->base_price,
            'author_id' => $this->author_id,
            'status' => $this->status,
            'description' => $this->description,
            'duration' => $this->duration,
            'image_path' => $this->image_path,
            'proposed_terms' => ConsultationProposedTermResource::collection($this->proposedTerms),
            'categories' => $this->categories,
        ];
        return self::apply($fields, $this);
    }
}
