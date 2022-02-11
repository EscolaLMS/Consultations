<?php

namespace EscolaLms\Consultations\Services\Contracts;

use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;

interface ConsultationServiceContract
{
    public function getConsultationsList(array $search = []): Builder;
    public function store(array $data = []): Consultation;
    public function update(int $id, array $data = []): Consultation;
    public function show(int $id): Consultation;
    public function delete(int $id): ?bool;
}
