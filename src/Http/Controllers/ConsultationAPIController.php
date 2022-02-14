<?php

namespace EscolaLms\Consultations\Http\Controllers;

use EscolaLms\Consultations\Http\Controllers\Swagger\ConsultationAPISwagger;
use EscolaLms\Consultations\Http\Requests\ReportTermConsultationRequest;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Core\Http\Controllers\EscolaLmsBaseController;

class ConsultationAPIController extends EscolaLmsBaseController implements ConsultationAPISwagger
{
    private ConsultationServiceContract $consultationServiceContract;

    public function __construct(
        ConsultationServiceContract $consultationServiceContract
    ) {
        $this->consultationServiceContract = $consultationServiceContract;
    }
}
