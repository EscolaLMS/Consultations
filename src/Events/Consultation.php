<?php

namespace EscolaLms\Consultations\Events;

use EscolaLms\Auth\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EscolaLms\Consultations\Models\Consultation as ConsultationModel;

abstract class Consultation
{
    use Dispatchable, SerializesModels;

    private User $user;
    private ConsultationModel $consultation;

    public function __construct(User $user, ConsultationModel $consultation)
    {
        $this->user = $user;
        $this->consultation = $consultation;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCourse(): ConsultationModel
    {
        return $this->consultation;
    }
}
