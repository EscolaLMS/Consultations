<?php

namespace EscolaLms\Consultations\Http\Resources;

use Carbon\Carbon;
use EscolaLms\Auth\Traits\ResourceExtandable;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationSimpleResource extends JsonResource
{
    use ResourceExtandable;

    public function toArray($request)
    {
        $fields = [
            'id' => $this->id,
            'created_at' => Carbon::make($this->created_at),
            'updated_at' => Carbon::make($this->updated_at),
            'active_from' => Carbon::make($this->active_from),
            'active_to' => Carbon::make($this->active_to),
            'name' => $this->name,
            'base_price' => $this->base_price,
            'author' => $this->author ? ConsultationAuthorResource::make($this->author) : null,
            'status' => $this->status,
            'description' => $this->description,
            'short_desc' => $this->short_desc,
            'duration' => $this->duration,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'proposed_terms' => $this->proposedTerms->count() > 0 ?
                ConsultationProposedTermResource::collection($this->proposedTerms) :
                [],
            'categories' => $this->categories,
        ];
        return self::apply($fields, $this);
    }
}
