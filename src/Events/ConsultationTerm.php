<?php

namespace EscolaLms\Consultations\Events;

use EscolaLms\Auth\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EscolaLms\Consultations\Models\ConsultationTerm as ConsultationTermModel;

abstract class ConsultationTerm
{
    use Dispatchable, SerializesModels;

    private User $user;
    private ConsultationTermModel $consultationTerm;

    public function __construct(User $user, ConsultationTermModel $consultationTerm)
    {
        $this->user = $user;
        $this->consultationTerm = $consultationTerm;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getConsultationTerm(): ConsultationTermModel
    {
        return $this->consultationTerm;
    }
}
