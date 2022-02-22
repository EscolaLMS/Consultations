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
     * )
     *
     */
    use ResourceExtandable;

    public function toArray($request)
    {
        return [
            'date' => Carbon::make($this->executed_at)->format('Y-m-d H:i:s'),
            'status' => $this->executed_status,
        ];
    }
}