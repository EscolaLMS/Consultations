<?php

namespace EscolaLms\Consultations\Http\Controllers;

use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Http\Controllers\Swagger\ConsultationAPISwagger;
use EscolaLms\Consultations\Http\Requests\ListAPIConsultationsRequest;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Http\Requests\ReportTermConsultationRequest;
use EscolaLms\Consultations\Http\Requests\ShowAPIConsultationRequest;
use EscolaLms\Consultations\Http\Resources\ConsultationProposedTermResource;
use EscolaLms\Consultations\Http\Resources\ConsultationSimpleResource;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Consultations\Tests\APIs\ConsultationShowApiTest;
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

    public function index(ListAPIConsultationsRequest $listConsultationsRequest): JsonResponse
    {
        $search = $listConsultationsRequest->except(['limit', 'skip', 'order', 'order_by']);
        $consultations = $this->consultationServiceContract
            ->getConsultationsList($search, true)
            ->paginate(
                $listConsultationsRequest->get('per_page') ??
                config('escolalms_consultations.perPage', ConstantEnum::PER_PAGE)
            );

        return $this->sendResponseForResource(
            ConsultationSimpleResource::collection($consultations), __('Consultations retrieved successfully')
        );
    }

    public function show(ShowAPIConsultationRequest $showAPIConsultationRequest, int $id): JsonResponse
    {
        $consultation = $this->consultationServiceContract->show($id);
        return $this->sendResponseForResource(
            ConsultationSimpleResource::make($consultation),
            __('Consultation show successfully')
        );
    }

    public function forCurrentUser(ListConsultationsRequest $listConsultationsRequest): JsonResponse
    {
        return $this->sendResponseForResource(
            $this->consultationServiceContract->forCurrentUserResponse($listConsultationsRequest),
            __('Consultations retrieved successfully')
        );
    }

    public function reportTerm(int $consultationTermId, ReportTermConsultationRequest $request): JsonResponse
    {
        $this->consultationServiceContract->reportTerm($consultationTermId, $request->input('term'));
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

    public function proposedTerms(int $consultationTermId): JsonResponse
    {
        $proposedTerms = $this->consultationServiceContract->proposedTerms($consultationTermId);
        return $this->sendResponseForResource(
            ConsultationProposedTermResource::collection($proposedTerms),
            __('Consultations propsed terms retrieved successfully')
        );
    }

    public function generateJitsi(int $consultationTermId): JsonResponse
    {
        return $this->sendResponse(
            $this->consultationServiceContract->generateJitsi($consultationTermId),
            __('Consultation updated successfully')
        );
    }
}
