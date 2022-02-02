<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;

class ConsultationRepository extends BaseRepository implements ConsultationRepositoryContract
{
    protected $fieldSearchable = [
        'author_id',
        'name',
        'base_price',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Consultation::class;
    }
}
