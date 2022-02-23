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

    public function forUser(array $search = [], array $criteria = []): Builder
    {
        $q = $this->allQueryBuilder($search, $criteria);
        $q->whereHas('orderItems', function ($query) {
            return $query
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.user_id', '=', auth()->user()->getKey());
        });
        return $q;
    }

    public function updateModel(Consultation $consultation, array $data): Consultation
    {
        $consultation->fill($data);
        $consultation->save();
        return $consultation;
    }

    public function getByOrderId(int $orderItemId): ?Consultation
    {
        return $this->model->newQuery()
            ->whereRelation('orderItems', fn (Builder $query) =>
                $query->whereId($orderItemId)
            )
            ->firstOrFail();
    }
}
