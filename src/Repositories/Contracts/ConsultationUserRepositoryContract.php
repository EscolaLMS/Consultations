<?php

namespace EscolaLms\Consultations\Repositories\Contracts;

use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use Illuminate\Database\Eloquent\Builder;

interface ConsultationUserRepositoryContract
{
    public function allQueryBuilder(
        array $search = [],
        ?FilterConsultationTermsListDto $filterConsultationTermsListDto = null
    ): Builder;
    public function updateModel(ConsultationUserPivot $consultationUserPivot, array $data): ConsultationUserPivot;
}
