<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class ConsultationTermsRepository extends BaseRepository implements ConsultationTermsRepositoryContract
{
    protected $fieldSearchable = [];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return ConsultationTerm::class;
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

    public function findByOrderItem(int $orderItemId): ConsultationTerm
    {
        return $this->model->newQuery()->whereOrderItemId($orderItemId)->firstOrFail();
    }

    public function updateModel(ConsultationTerm $consultationTerm, array $data): ConsultationTerm
    {
        $consultationTerm->fill($data);
        $consultationTerm->save();
        return $consultationTerm;
    }

}
