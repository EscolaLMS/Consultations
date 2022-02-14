<?php

namespace EscolaLms\Consultations\Http\Controllers;

use EscolaLms\Consultations\Http\Controllers\Swagger\ConsultationAPISwagger;
use EscolaLms\Consultations\Http\Requests\ReportTermConsultationRequest;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Core\Http\Controllers\EscolaLmsBaseController;
use Illuminate\Http\JsonResponse;

class ConsultationAPIController extends EscolaLmsBaseController implements ConsultationAPISwagger
{
    private ConsultationServiceContract $consultationServiceContract;

    public function __construct(
        ConsultationServiceContract $consultationServiceContract
    ) {
        $this->consultationServiceContract = $consultationServiceContract;
    }

    public function reportTerm(int $orderItemId, ReportTermConsultationRequest $request): JsonResponse
    {
        $this->consultationServiceContract->reportTerm($orderItemId, $request->input('term'));
        return $this->sendSuccess(__('Consultation reserved term successfully'));
    }

    public function approveTerm(int $consultationTermId): JsonResponse
    {
        $this->consultationServiceContract->approveTerm($consultationTermId);
        return $this->sendSuccess(__('Consultation term approved successfully'));
    }

    public function rejectTerm(int $consultationTermId): JsonResponse
    {
        $this->consultationServiceContract->rejectTerm($consultationTermId);
        return $this->sendSuccess(__('Consultation term reject successfully'));
    }

    public function generateJitsi(int $consultationTermId): JsonResponse
    {
        return $this->sendResponse(
            $this->consultationServiceContract->generateJitsi($consultationTermId),
            __('Consultation updated successfully')
        );
    }
}
