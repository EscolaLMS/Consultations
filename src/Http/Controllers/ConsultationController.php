<?php

namespace EscolaLms\Consultations\Http\Controllers;

use EscolaLms\Consultations\Http\Controllers\Swagger\ConsultationAPISwagger;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Http\Resources\ConsultationSimpleResource;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Core\Http\Controllers\EscolaLmsBaseController;

class ConsultationController extends EscolaLmsBaseController implements ConsultationAPISwagger
{
    private ConsultationServiceContract $consultationServiceContract;
    public function __construct(
        ConsultationServiceContract $consultationServiceContract
    ) {
        $this->consultationServiceContract = $consultationServiceContract;
    }

    public function index(ListConsultationsRequest $listConsultationsRequest)
    {
        $search = $listConsultationsRequest->except(['limit', 'skip', 'order', 'order_by']);
        $consultations = $this->consultationServiceContract
            ->getConsultationsList($search)
            ->paginate($listConsultationsRequest->get('per_page') ?? 15);
        return $this->sendResponseForResource(
            ConsultationSimpleResource::collection($consultations), __('Consultations retrieved successfully')
        );
    }
}
