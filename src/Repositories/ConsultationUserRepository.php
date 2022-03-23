<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class ConsultationUserRepository extends BaseRepository implements ConsultationUserRepositoryContract
{
    protected $fieldSearchable = [];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return ConsultationUserPivot::class;
    }

    public function allQueryBuilder(
        array $search = [],
        ?FilterConsultationTermsListDto $filterConsultationTermsListDto = null
    ): Builder {
        $query = $this->allQuery($search);
        $criteria = $filterConsultationTermsListDto->getCriteria();
        if ($criteria) {
            $query = $this->applyCriteria($query, $criteria);
        }
        $query->whereHas('orderItem', fn (Builder $query) =>
            $query->whereHasMorph('buyable', [Consultation::class], fn (Builder $query) =>
                $query->where('consultations.id', $filterConsultationTermsListDto->getConsultationId())
            )
        );
        return $query;
    }

    public function updateModel(ConsultationUserPivot $consultationUserPivot, array $data): ConsultationUserPivot
    {
        $consultationUserPivot->fill($data);
        $consultationUserPivot->save();
        return $consultationUserPivot;
    }

}
