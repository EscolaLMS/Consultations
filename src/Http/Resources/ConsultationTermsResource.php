<?php

namespace EscolaLms\Consultations\Http\Resources;

use EscolaLms\Auth\Traits\ResourceExtandable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ConsultationTermsResource extends JsonResource
{
    /**
     * @OA\Schema(
     *      schema="ConsultationTerm",
     *      @OA\Property(
     *          property="status",
     *          description="status",
     *          type="string"
     *      ),
     *      @OA\Property(
     *          property="date",
     *          description="date",
     *          type="datetime",
     *      ),
     *      @OA\Property(
     *          property="is_ended",
     *          description="is_ended",
     *          type="boolean",
     *      ),
     *      @OA\Property(
     *          property="consultation_term_id",
     *          description="consultation_term_id",
     *          type="integer",
     *      ),
     * )
     *
     */
    use ResourceExtandable;

    public function toArray($request)
    {
        return [
            'consultation_term_id' => $this->getKey(),
            'date' => Carbon::make($this->executed_at)->format('Y-m-d H:i:s') ?? '',
            'status' => $this->executed_status ?? '',
            'duration' => $this->consultation->duration ?? '',
            'author' => isset($this->consultation->author) ?
                ConsultationAuthorResource::make($this->consultation->author) :
                null
        ];
    }
}
