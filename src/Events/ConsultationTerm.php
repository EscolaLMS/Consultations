<?php

namespace EscolaLms\Consultations\Events;

use EscolaLms\Auth\Models\User;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EscolaLms\Consultations\Models\ConsultationTerm as ConsultationTermModel;

abstract class ConsultationTerm
{
    use Dispatchable, SerializesModels;

    private User $user;
    private ConsultationUserPivot $consultationTerm;

    public function __construct(User $user, ConsultationUserPivot $consultationTerm)
    {
        $this->user = $user;
        $this->consultationTerm = $consultationTerm;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getConsultationTerm(): ConsultationUserPivot
    {
        return $this->consultationTerm;
    }
}
