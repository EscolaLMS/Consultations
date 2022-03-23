<?php

namespace EscolaLms\Consultations\Http\Resources;

use EscolaLms\Auth\Traits\ResourceExtandable;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationAuthorResource extends JsonResource
{
    use ResourceExtandable;

    public function toArray($request)
    {
        $fields = array_merge(
            $this->resource->toArray(),
            ['categories' => $this->categories]
        );
        return self::apply($fields, $this);
    }
}
