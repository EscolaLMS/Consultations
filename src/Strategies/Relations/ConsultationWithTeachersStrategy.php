<?php

namespace EscolaLms\Consultations\Strategies\Relations;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Strategies\Contracts\RelationStrategyContract;

class ConsultationWithTeachersStrategy implements RelationStrategyContract
{
    private Consultation $consultation;
    private array $data;

    public function __construct(array $params) {
        $this->consultation = $params[0];
        $this->data = $params[1] ?? [];
    }

    public function setRelation(): void
    {
        $this->consultation->teachers()->sync($this->data['teachers']);
    }
}
