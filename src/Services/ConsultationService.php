<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\RejectTerm;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Jitsi\Services\Contracts\JitsiServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConsultationService implements ConsultationServiceContract
{
    private ConsultationRepositoryContract $consultationRepositoryContract;
    private ConsultationTermsRepositoryContract $consultationTermsRepositoryContract;
    private JitsiServiceContract $jitsiServiceContract;

    public function __construct(
        ConsultationRepositoryContract $consultationRepositoryContract,
        ConsultationTermsRepositoryContract $consultationTermsRepositoryContract,
        JitsiServiceContract $jitsiServiceContract
    ) {
        $this->consultationRepositoryContract = $consultationRepositoryContract;
        $this->consultationTermsRepositoryContract = $consultationTermsRepositoryContract;
        $this->jitsiServiceContract = $jitsiServiceContract;
    }

    public function getConsultationsList(array $search = [], bool $onlyActive = false): Builder
    {
        if ($onlyActive) {
            $now = now()->format('Y-m-d');
            $search['active_to'] = $search['active_to'] ?? $now;
            $search['active_from'] = $search['active_from'] ?? $now;
        }
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

    public function setPivotOrderConsultation($order, $user): void
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
            $data = [
                'executed_status' => ConsultationTermStatusEnum::REPORTED,
                'executed_at' => $executedAt
            ];
            $this->consultationTermsRepositoryContract->updateModel($consultationTerm, $data);
            $author = $consultationTerm->orderItem->buyable->author;
            event(new ReportTerm($author, $consultationTerm));
            return true;
        });
    }

    public function approveTerm(int $consultationTermId): bool
    {
        $consultationTerm = $this->consultationTermsRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::APPROVED);
        event(new ApprovedTerm($consultationTerm->user, $consultationTerm));
        return true;
    }

    public function rejectTerm(int $consultationTermId): bool
    {
        $consultationTerm = $this->consultationTermsRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::REJECT);
        event(new RejectTerm($consultationTerm->user, $consultationTerm));
        return true;
    }

    public function setStatus(ConsultationTerm $consultationTerm, string $status): ConsultationTerm
    {
        return DB::transaction(function () use ($status, $consultationTerm) {
            if ($consultationTerm->executed_status !== ConsultationTermStatusEnum::REPORTED) {
                throw new NotFoundHttpException(__('Consultation term not found'));
            }
            return $this->consultationTermsRepositoryContract->updateModel($consultationTerm, ['executed_status' => $status]);
        });
    }

    public function generateJitsi(int $consultationTermId): array
    {
        $consultationTerm = $this->consultationTermsRepositoryContract->find($consultationTermId);
        if ($this->canGenerateJitsi($consultationTerm)) {
            throw new NotFoundHttpException(__('Consultation term is not available'));
        }

        return $this->jitsiServiceContract->getChannelData(
            auth()->user(),
            Str::studly($consultationTerm->orderItem->buyable->name)
        );
    }

    public function canGenerateJitsi(ConsultationTerm $consultationTerm): bool
    {
        $modifyTimeStrings = [
            'seconds', 'minutes', 'hours', 'weeks', 'years'
        ];
        $now = now();
        $explode = explode(' ', $consultationTerm->orderItem->buyable->duration);
        $count = $explode[0];
        $string = in_array($explode[1], $modifyTimeStrings) ? $explode[1] : 'hours';
        $dateTo = Carbon::make($consultationTerm->executed_at)->modify('+' . $count . ' ' . $string);

        return !$consultationTerm->isApproved() ||
            $now < $consultationTerm->executed_at ||
            $now > $dateTo;
    }
}