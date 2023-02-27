<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Models\ConsultationUserProposedTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserProposedTermRepositoryContract;
use EscolaLms\Core\Tests\Mocks\BaseRepository;

class ConsultationUserProposedTermRepository extends BaseRepository implements ConsultationUserProposedTermRepositoryContract
{
    public function model(): string
    {
        return ConsultationUserProposedTerm::class;
    }

    public function getFieldsSearchable(): array
    {
        return [
            'consultation_user_id',
            'proposed_at',
        ];
    }
}
