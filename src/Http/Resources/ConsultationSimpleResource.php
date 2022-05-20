<?php

namespace EscolaLms\Consultations\Http\Resources;

use Carbon\Carbon;
use EscolaLms\Auth\Traits\ResourceExtandable;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationSimpleResource extends JsonResource
{
    use ResourceExtandable;

    public function toArray($request)
    {
        $consultationServiceContract = app(ConsultationServiceContract::class);
        $fields = [
            'id' => $this->id,
            'created_at' => Carbon::make($this->created_at),
            'updated_at' => Carbon::make($this->updated_at),
            'active_from' => Carbon::make($this->active_from),
            'active_to' => Carbon::make($this->active_to),
            'name' => $this->name,
            'author' => $this->author ? ConsultationAuthorResource::make($this->author) : null,
            'status' => $this->status,
            'description' => $this->description,
            'short_desc' => $this->short_desc,
            'duration' => $this->resource->getDuration(),
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'logotype_path' => $this->logotype_path,
            'logotype_url' => $this->logotype_url,
            'proposed_terms' => $this->proposedTerms->count() > 0 ?
                ConsultationProposedTermResource::collection($this->proposedTerms) :
                [],
            'busy_terms' => ConsultationTermResource::collection($consultationServiceContract->getBusyTermsFormatDate($this->getKey())),
            'categories' => $this->categories,
        ];
        return self::apply($fields, $this);
    }
}
