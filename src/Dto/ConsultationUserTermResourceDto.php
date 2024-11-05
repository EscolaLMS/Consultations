<?php

namespace EscolaLms\Consultations\Dto;

use EscolaLms\Consultations\Models\User;
use Illuminate\Support\Collection;

class ConsultationUserTermResourceDto
{
    public int $consultation_user_id;
    public int $consultation_id;
    public string $executed_at;
    public string $executed_status;
    public string $duration;
    public Collection $users;
    public ?User $author;
    public ?string $finished_at;

    public function __construct(int $consultation_user_id, int $consultation_id, string $executed_at, string $status, string $duration, ?User $author, ?string $finished_at = null, ?Collection $users = null)
    {
        $this->consultation_user_id = $consultation_user_id;
        $this->consultation_id = $consultation_id;
        $this->executed_at = $executed_at;
        $this->executed_status = $status;
        $this->duration = $duration;
        $this->users = $users instanceof Collection ? $users : collect();
        $this->author = $author;
        $this->finished_at = $finished_at;
    }

}
