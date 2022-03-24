<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class ConsultationRepository extends BaseRepository implements ConsultationRepositoryContract
{
    protected $fieldSearchable = [];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Consultation::class;
    }

    public function allQueryBuilder(array $search = [], array $criteria = []): Builder
    {
        $query = $this->allQuery($search);
        if (!empty($criteria)) {
            $query = $this->applyCriteria($query, $criteria);
        }
        return $query;
    }

    public function forCurrentUser(array $search = [], array $criteria = []): Builder
    {
        $q = $this->allQueryBuilder($search, $criteria);
        $q->whereHas('users', fn ($query) =>
            $query->where(['users.id' => auth()->user()->getKey()])
        );
        return $q;
    }

    public function updateModel(Consultation $consultation, array $data): Consultation
    {
        $consultation->fill($data);
        $consultation->save();
        return $consultation;
    }

    public function getBoughtConsultationsByQuery(Builder $query): Builder
    {
        return $query
            ->select(
                'consultations.*',
                'consultation_user.id as cuid',
                'consultation_user.executed_status',
                'consultation_user.executed_at',
            )
            ->leftJoin('consultation_user', 'consultation_user.consultation_id', '=', 'consultations.id')
            ->where(['consultation_user.id' => auth()->user()->getKey()]);
    }
}
