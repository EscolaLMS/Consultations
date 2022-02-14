<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConsultationService implements ConsultationServiceContract
{
    private ConsultationRepositoryContract $consultationRepositoryContract;
    private ConsultationTermsRepositoryContract $consultationTermsRepositoryContract;

    public function __construct(
        ConsultationRepositoryContract $consultationRepositoryContract,
        ConsultationTermsRepositoryContract $consultationTermsRepositoryContract
    ) {
        $this->consultationRepositoryContract = $consultationRepositoryContract;
        $this->consultationTermsRepositoryContract = $consultationTermsRepositoryContract;
    }

    public function getConsultationsList(array $search = []): Builder
    {
        $criteria = FilterListDto::prepareFilters($search);
        return $this->consultationRepositoryContract->allQueryBuilder(
            $search,
            $criteria
        );
    }

    public function store(array $data = []): Consultation
    {
        return DB::transaction(function () use($data) {
            return $this->consultationRepositoryContract->create($data);
        });
    }

    public function update(int $id, array $data = []): Consultation
    {
        $consultation = $this->show($id);
        return DB::transaction(function () use($consultation, $data) {
            return $this->consultationRepositoryContract->updateModel($consultation, $data);
        });
    }

    public function show(int $id): Consultation
    {
        $consultation = $this->consultationRepositoryContract->find($id);
        if (!$consultation) {
            throw new NotFoundHttpException(__('Consultation not found'));
        }
        return $consultation;
    }

    public function delete(int $id): ?bool
    {
        return DB::transaction(function () use($id) {
            return $this->consultationRepositoryContract->delete($id);
        });
    }

    public function setPivotOrderConsultation($order, $user)
    {
        foreach ($order->items as $item) {
            if ($item->buyable instanceof Consultation) {
                $data = [
                    'order_item_id' => $item->getKey(),
                    'user_id' => $user->getKey(),
                    'executed_status' => ConsultationTermStatusEnum::NOT_REPORTED
                ];
                $this->consultationTermsRepositoryContract->create($data);
            }
        }
    }

    public function reportTerm(int $orderItemId, string $executedAt): bool
    {
        return DB::transaction(function () use ($orderItemId, $executedAt) {
            $consultationTerm = $this->consultationTermsRepositoryContract->findByOrderItem($orderItemId);
            $author = $consultationTerm->orderItem->buyable->author;
            $data = [
                'executed_status' => ConsultationTermStatusEnum::REPORTED,
                'executed_at' => $executedAt
            ];
            $this->consultationTermsRepositoryContract->updateModel($consultationTerm, $data);
            event(new ReportTerm($author, $consultationTerm));
            return true;
        });
    }
}
