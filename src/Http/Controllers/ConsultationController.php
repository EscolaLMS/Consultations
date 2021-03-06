<?php

namespace EscolaLms\Consultations\Http\Controllers;

use EscolaLms\Consultations\Dto\ConsultationDto;
use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Http\Controllers\Swagger\ConsultationSwagger;
use EscolaLms\Consultations\Http\Requests\ChangeTermConsultationRequest;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Http\Requests\ScheduleConsultationRequest;
use EscolaLms\Consultations\Http\Requests\ShowConsultationRequest;
use EscolaLms\Consultations\Http\Requests\StoreConsultationRequest;
use EscolaLms\Consultations\Http\Requests\UpdateConsultationRequest;
use EscolaLms\Consultations\Http\Resources\ConsultationSimpleResource;
use EscolaLms\Consultations\Http\Resources\ConsultationTermsResource;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Core\Http\Controllers\EscolaLmsBaseController;
use Illuminate\Http\JsonResponse;

class ConsultationController extends EscolaLmsBaseController implements ConsultationSwagger
{
    private ConsultationServiceContract $consultationServiceContract;

    public function __construct(
        ConsultationServiceContract $consultationServiceContract
    ) {
        $this->consultationServiceContract = $consultationServiceContract;
    }

    public function index(ListConsultationsRequest $listConsultationsRequest): JsonResponse
    {
        $search = $listConsultationsRequest->except(['limit', 'skip', 'order', 'order_by']);
        $consultations = $this->consultationServiceContract
            ->getConsultationsList($search)
            ->paginate(
                $listConsultationsRequest->get('per_page') ??
                config('escolalms_consultations.perPage', ConstantEnum::PER_PAGE)
            );

        return $this->sendResponseForResource(
            ConsultationSimpleResource::collection($consultations), __('Consultations retrieved successfully')
        );
    }

    public function schedule(int $id, ScheduleConsultationRequest $scheduleConsultationRequest): JsonResponse
    {
        $search = $scheduleConsultationRequest->except(['limit', 'skip', 'order', 'order_by']);
        $consultationTerms = $this->consultationServiceContract
            ->getConsultationTermsByConsultationId($id, $search);
        return $this->sendResponseForResource(
            ConsultationTermsResource::collection($consultationTerms),
            __('Consultation schedules retrieved successfully')
        );
    }

    public function store(StoreConsultationRequest $storeConsultationRequest): JsonResponse
    {
        $dto = new ConsultationDto($storeConsultationRequest->all());
        $consultation = $this->consultationServiceContract->store($dto);
        return $this->sendResponseForResource(
            ConsultationSimpleResource::make($consultation),
            __('Consultation saved successfully')
        );
    }

    public function update(int $id, UpdateConsultationRequest $updateConsultationRequest): JsonResponse
    {
        $dto = new ConsultationDto($updateConsultationRequest->all());
        $consultation = $this->consultationServiceContract->update($id, $dto);
        return $this->sendResponseForResource(
            ConsultationSimpleResource::make($consultation),
            __('Consultation updated successfully')
        );
    }

    public function show(ShowConsultationRequest $showConsultationRequest, int $id): JsonResponse
    {
        $consultation = $this->consultationServiceContract->show($id);
        return $this->sendResponseForResource(
            ConsultationSimpleResource::make($consultation),
            __('Consultation show successfully')
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->consultationServiceContract->delete($id);
        return $this->sendSuccess(__('Consultation deleted successfully'));
    }

    public function changeTerm(ChangeTermConsultationRequest $changeTermConsultationRequest, int $consultationTermId): JsonResponse
    {
        $this->consultationServiceContract->changeTerm(
            $consultationTermId,
            $changeTermConsultationRequest->input('executed_at')
        );
        return $this->sendSuccess(__('Consultation term changed successfully'));
    }
}
