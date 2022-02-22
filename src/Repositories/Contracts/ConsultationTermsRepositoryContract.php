<?php

namespace EscolaLms\Consultations\Repositories\Contracts;

use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Models\ConsultationTerm;
use Illuminate\Database\Eloquent\Builder;

interface ConsultationTermsRepositoryContract
{
    public function allQueryBuilder(
        array $search = [],
        ?FilterConsultationTermsListDto $filterConsultationTermsListDto = null
    ): Builder;
    public function findByOrderItem(int $orderItemId): ConsultationTerm;
    public function updateModel(ConsultationTerm $consultationTerm, array $data): ConsultationTerm;
}
