<?php

namespace EscolaLms\Consultations\Repositories\Contracts;

use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;

interface ConsultationRepositoryContract
{
    public function allQueryBuilder(array $search = [], array $criteria = []): Builder;
    public function updateModel(Consultation $consultation, array $data): Consultation;
    public function getByOrderId(int $orderItemId): ?Consultation;
    public function forCurrentUser(array $search = [], array $criteria = []): Builder;
}
