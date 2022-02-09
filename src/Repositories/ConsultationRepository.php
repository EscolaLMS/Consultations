<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

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

    public function updateModel(Consultation $consultation, array $data): Consultation
    {
        $consultation->fill($data);
        $consultation->save();
        return $consultation;
    }
}
