<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        if ($filterConsultationTermsListDto) {
            $criteria = $filterConsultationTermsListDto->getCriteria();
            if ($criteria) {
                $query = $this->applyCriteria($query, $criteria);
            }
        }

        return $query;
    }

    public function updateModel(ConsultationUserPivot $consultationUserPivot, array $data): ConsultationUserPivot
    {
        $consultationUserPivot->fill($data);
        $consultationUserPivot->save();
        return $consultationUserPivot;
    }

    public function getIncomingTerm(array $criteria = []): Collection
    {
        $query = $this->model->newQuery();
        if ($criteria) {
            $query = $this->applyCriteria($query, $criteria);
        }
        return $query->get();
    }

    public function getByCurrentUserTutor(): Collection
    {
        return $this->model->newQuery()
            ->whereHas('consultation', fn (Builder $query) => $query
            ->whereAuthorId(auth()->user()->getKey())
            ->orWhereHas('teachers', fn (Builder $query) => $query->where('users.id', '=', auth()->user()->getKey()))
        )->get();
    }

    public function getBusyTerms(int $consultationId, ?string $date = null): Collection
    {
        $query = $this->model->newQuery();
        $query->where([
            'consultation_id' => $consultationId
        ]);
        $query->whereNotNull('executed_at');
        if ($date) {
            $query->where([
                'executed_at' => $date
            ]);
        }
        $query->whereIn('executed_status', [ConsultationTermStatusEnum::APPROVED, ConsultationTermStatusEnum::REPORTED]);
        return $query->get();
    }

}
