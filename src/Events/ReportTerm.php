<?php

namespace EscolaLms\Consultations\Events;

use EscolaLms\Auth\Models\User;
use EscolaLms\Consultations\Models\ConsultationTerm;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EscolaLms\Consultations\Models\Consultation as ConsultationModel;

class ReportTerm
{
    use Dispatchable, SerializesModels;

    private User $user;
    private ConsultationTerm $consultationTerm;

    public function __construct(User $user, ConsultationTerm $consultationTerm)
    {
        $this->user = $user;
        $this->consultationTerm = $consultationTerm;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getConsultationTerm(): ConsultationTerm
    {
        return $this->consultationTerm;
    }
}
