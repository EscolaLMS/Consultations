<?php

namespace EscolaLms\Consultations\Events;

use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Core\Models\User;

class ReminderAboutTerm extends ConsultationTerm
{
    private string $status;

    public function __construct(User $user, ConsultationUserPivot $consultationTerm, string $status)
    {
        parent::__construct($user, $consultationTerm);
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
