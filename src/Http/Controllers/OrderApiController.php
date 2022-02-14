<?php

namespace EscolaLms\Consultations\Http\Controllers;

use EscolaLms\Consultations\Http\Requests\ReportTermConsultationRequest;
use EscolaLms\Consultations\Services\Contracts\OrderServiceContract;
use EscolaLms\Core\Http\Controllers\EscolaLmsBaseController;

class OrderApiController extends EscolaLmsBaseController
{
    private OrderServiceContract $orderServiceContract;

    public function __construct(
        OrderServiceContract $orderServiceContract
    ) {
        $this->orderServiceContract = $orderServiceContract;
    }

    public function reportTerm(int $id, ReportTermConsultationRequest $reportTermConsultationRequest)
    {
        $this->orderServiceContract->reportTerm($id, $reportTermConsultationRequest->input('term'));
        return $this->sendSuccess(__('Consultation report term successfully'));
    }
}
