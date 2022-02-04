<?php

namespace EscolaLms\Consultations\Services\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface ConsultationServiceContract
{
    public function getConsultationsList(array $search = []): Builder;
}
