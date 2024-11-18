<?php

namespace EscolaLms\Consultations\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationUserResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
