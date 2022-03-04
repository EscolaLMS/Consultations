<?php

namespace EscolaLms\Consultations\Listeners;

use EscolaLms\Cart\Events\OrderPaid;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;

class ReportTermListener
{
    private ConsultationServiceContract $consultationServiceContract;

    public function __construct(
        ConsultationServiceContract $consultationServiceContract
    ) {
        $this->consultationServiceContract = $consultationServiceContract;
    }

    public function handle(OrderPaid $event)
    {
        $this->consultationServiceContract->setPivotOrderConsultation($event->getOrder(), $event->getUser());
    }
}
