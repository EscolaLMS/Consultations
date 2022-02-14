<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;

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
