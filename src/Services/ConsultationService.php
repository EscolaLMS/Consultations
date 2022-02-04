<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use Illuminate\Database\Eloquent\Builder;

class ConsultationService implements ConsultationServiceContract
{
    private ConsultationRepositoryContract $consultationRepositoryContract;

    public function __construct(
        ConsultationRepositoryContract $consultationRepositoryContract
    ) {
        $this->consultationRepositoryContract = $consultationRepositoryContract;
    }

    public function getConsultationsList(array $search = []): Builder
    {
        $criteria = FilterListDto::prepareFilters($search);
        return $this->consultationRepositoryContract->allQueryBuilder(
            $search,
            $criteria
        );
    }
}
