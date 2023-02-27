<?php

namespace EscolaLms\Consultations\Events;

use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Core\Models\User;

class RejectTerm extends ConsultationTerm
{
    private ?string $message;

    public function __construct(User $user, ConsultationUserPivot $consultationTerm, ?string $message)
    {
        parent::__construct($user, $consultationTerm);
        $this->message = $message;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
