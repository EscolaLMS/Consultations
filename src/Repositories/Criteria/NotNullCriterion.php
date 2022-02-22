<?php

namespace EscolaLms\Consultations\Repositories\Criteria;

use EscolaLms\Core\Repositories\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class NotNullCriterion extends Criterion
{
    public function apply(Builder $query): Builder
    {
        return $query->whereNotNull($this->key);
    }
}
