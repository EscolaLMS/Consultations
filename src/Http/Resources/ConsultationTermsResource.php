<?php

namespace EscolaLms\Consultations\Http\Resources;

use EscolaLms\Auth\Traits\ResourceExtandable;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
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
        $consultationServiceContract = app(ConsultationServiceContract::class);
        return [
            'consultation_term_id' => $this->getKey(),
            'date' => Carbon::make($this->executed_at) ?? '',
            'status' => $this->executed_status ?? '',
            'duration' => $this->consultation->duration ?? '',
            'user' => isset($this->user) ?
                ConsultationAuthorResource::make($this->user) :
                null,
            'is_started' => $consultationServiceContract->isStarted(
                $this->executed_at,
                $this->executed_status,
                $this->consultation->duration
            ),
            'is_ended' => $consultationServiceContract->isEnded($this->executed_at, $this->consultation->duration),
            'in_coming' => $consultationServiceContract->inComing($this->executed_at, $this->duration),
        ];
    }
}
