<?php

namespace EscolaLms\Consultations\Http\Resources;

use EscolaLms\Consultations\Models\ConsultationUserProposedTerm;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *      schema="ConsultationUserProposedTerm",
 *      @OA\Property(
 *          property="id",
 *          description="id",
 *          type="number"
 *      ),
 *      @OA\Property(
 *          property="proposed_at",
 *          description="proposed_at",
 *          type="string",
 *          format="date-time"
 *      ),
 * )
 */

/**
 * @mixin ConsultationUserProposedTerm
 */
class ConsultationUserProposedTermResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'proposed_at' => $this->proposed_at,
        ];
    }
}
