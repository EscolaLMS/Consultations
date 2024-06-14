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
     *          example="2022-04-15T04:00:00.000Z",
     *      ),
     *      @OA\Property(
     *          property="duration",
     *          description="duration",
     *          type="string",
     *          example="2 hours",
     *      ),
     *      @OA\Property(
     *          property="user",
     *          ref="#/components/schemas/User"
     *      ),
     *      @OA\Property(
     *          property="is_ended",
     *          description="is_ended",
     *          type="boolean",
     *      ),
     *      @OA\Property(
     *          property="is_started",
     *          description="is_started",
     *          type="boolean",
     *      ),
     *      @OA\Property(
     *          property="in_coming",
     *          description="in_coming",
     *          type="boolean",
     *      ),
     *      @OA\Property(
     *          property="consultation_term_id",
     *          description="consultation_term_id",
     *          type="integer",
     *      ),
     *      @OA\Property(
     *          property="busy_terms",
     *          description="busy_terms",
     *          type="array",
     *          @OA\Items(
     *             @OA\Property(
     *                  property="",
     *                  type="string",
     *                  example="2022-05-20T10:15:20.000000Z",
     *             ),
     *          ),
     *      ),
     * )
     *
     */
    use ResourceExtandable;

    public function toArray($request)
    {
        $consultationServiceContract = app(ConsultationServiceContract::class);
        $fields = [
            'consultation_term_id' => $this->resource->getKey(),
            'date' => Carbon::make($this->resource->executed_at) ?? '',
            'status' => $this->resource->executed_status ?? '',
            'duration' => $this->resource->consultation->getDuration(),
            'user' => isset($this->resource->user) ?
                ConsultationAuthorResource::make($this->resource->user) :
                null,
            'is_started' => $consultationServiceContract->isStarted(
                $this->resource->executed_at,
                $this->resource->executed_status,
                $this->resource->consultation->getDuration()
            ),
            'is_ended' => $consultationServiceContract->isEnded(
                $this->resource->executed_at,
                $this->resource->consultation->getDuration()
            ),
            'in_coming' => $consultationServiceContract->inComing(
                $this->resource->executed_at,
                $this->resource->executed_status,
                $this->resource->consultation->getDuration()
            ),
            'busy_terms' => ConsultationTermResource::collection($consultationServiceContract->getBusyTermsFormatDate($this->resource->consultation->getKey())),
            'author' =>  $this->resource->consultation->author ? ConsultationAuthorResource::make($this->resource->consultation->author) : null,
        ];
        return self::apply($fields, $this);
    }
}
