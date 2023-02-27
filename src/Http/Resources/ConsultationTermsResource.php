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
     *      @OA\Property(
     *          property="user_proposed_terms",
     *          description="user_proposed_terms",
     *          type="array",
     *          @OA\Items(ref="#/components/schemas/ConsultationUserProposedTerm"),
     *      ),
     * )
     *
     */
    use ResourceExtandable;

    public function toArray($request)
    {
        $consultationServiceContract = app(ConsultationServiceContract::class);
        $fields = [
            'consultation_term_id' => $this->getKey(),
            'date' => Carbon::make($this->executed_at) ?? '',
            'status' => $this->executed_status ?? '',
            'duration' => $this->consultation->getDuration(),
            'user' => isset($this->user) ?
                ConsultationAuthorResource::make($this->user) :
                null,
            'is_started' => $consultationServiceContract->isStarted(
                $this->executed_at,
                $this->executed_status,
                $this->consultation->getDuration()
            ),
            'is_ended' => $consultationServiceContract->isEnded(
                $this->executed_at,
                $this->consultation->getDuration()
            ),
            'in_coming' => $consultationServiceContract->inComing(
                $this->executed_at,
                $this->executed_status,
                $this->consultation->getDuration()
            ),
            'busy_terms' => ConsultationTermResource::collection($consultationServiceContract->getBusyTermsFormatDate($this->consultation->getKey())),
            'author' =>  $this->consultation->author ? ConsultationAuthorResource::make($this->consultation->author) : null,
            'user_proposed_terms' => ConsultationUserProposedTermResource::collection($this->consultationUserProposedTerms),
        ];
        return self::apply($fields, $this);
    }
}
