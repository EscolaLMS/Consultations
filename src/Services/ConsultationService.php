<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\ProductProductable;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Dto\ConsultationDto;
use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\RejectTerm;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Helpers\StrategyHelper;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Http\Resources\ConsultationSimpleResource;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Jitsi\Services\Contracts\JitsiServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function getConsultationsListForCurrentUser(array $search = []): Builder
    {
        $now = now()->format('Y-m-d');
        $search['active_to'] = $search['active_to'] ?? $now;
        $search['active_from'] = $search['active_from'] ?? $now;
        $criteria = FilterListDto::prepareFilters($search);
        return $this->consultationRepositoryContract->forCurrentUser(
            $search,
            $criteria
        );
    }

    public function store(ConsultationDto $consultationDto): Consultation
    {
        return DB::transaction(function () use($consultationDto) {
            $consultation = $this->consultationRepositoryContract->create($consultationDto->toArray());
            $this->setRelations($consultation, $consultationDto->getRelations());
            $this->setFiles($consultation, $consultationDto->getFiles());
            $consultation->save();
            return $consultation;
        });
    }

    public function update(int $id, ConsultationDto $consultationDto): Consultation
    {
        $consultation = $this->show($id);
        return DB::transaction(function () use($consultation, $consultationDto) {
            $this->setFiles($consultation, $consultationDto->getFiles());
            $consultation = $this->consultationRepositoryContract->updateModel($consultation, $consultationDto->toArray());
            $this->setRelations($consultation, $consultationDto->getRelations());
            return $consultation;
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

    public function setPivotOrderConsultation(Order $order, User $user): void
    {
        Log::info('orderId', ['orderKey' => $order->getKey()]);
        $order->items->each(function (OrderItem $item) use ($user) {
            Log::info('order item', ['orderItemKey' => $item->getKey()]);
            $item->buyable->productables->each(function (ProductProductable $product) use ($item, $user) {
                Log::info('productable id', ['productableKey' => $product->productable->getKey()]);
                if ($product->productable instanceof Consultation) {
                    $data = [
                        'order_item_id' => $item->getKey(),
                        'consultation_id' => $product->productable->getKey(),
                        'user_id' => $user->getKey(),
                        'executed_status' => ConsultationTermStatusEnum::NOT_REPORTED
                    ];
                    $this->consultationTermsRepositoryContract->create($data);
                }
            });
        });
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
            $author = $consultationTerm->consultation->author;
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

    public function setRelations(Consultation $consultation, array $relations = []): void
    {
        foreach ($relations as $key => $value) {
            $className = 'ConsultationWith' . ucfirst($key) . 'Strategy';
            StrategyHelper::useStrategyPattern(
                $className,
                'RelationsStrategy',
                'setRelation',
                $consultation,
                $relations
            );
        }
    }

    public function proposedTerms(int $orderItemId): ?Collection
    {
        $orderItem = OrderItem::find($orderItemId);
        $proposedTerms = collect();
        foreach ($orderItem->buyable->productables as $productable) {
            $proposedTerms = $proposedTerms->merge($productable->productable->proposedTerms);
        }
        return $proposedTerms ?? null;
    }

    public function setFiles(Consultation $consultation, array $files = []): void
    {
        foreach ($files as $key => $file) {
            $consultation->$key = $file->storePublicly("consultation/{$consultation->getKey()}/images");
        }
    }

    public function getConsultationTermsByConsultationId(int $consultationId, array $search = []): Collection
    {
        $filterConsultationTermsDto = FilterConsultationTermsListDto::prepareFilters(
            array_merge($search, ['consultation_id' => $consultationId])
        );
        return $this->consultationTermsRepositoryContract->allQueryBuilder(
            $search,
            $filterConsultationTermsDto
        )->get();
    }

    public function forCurrentUserResponse(ListConsultationsRequest $listConsultationsRequest): AnonymousResourceCollection
    {
        $search = $listConsultationsRequest->except(['limit', 'skip', 'order', 'order_by']);
        $consultations = $this->getConsultationsListForCurrentUser($search);
        $consultations
            ->select(
                'consultations.*',
                'consultation_terms.order_item_id',
                'consultation_terms.executed_status',
                'consultation_terms.executed_at',
            )
            ->leftJoin('consultation_terms', 'consultation_terms.consultation_id', '=', 'consultations.id');
        $consultationsCollection = ConsultationSimpleResource::collection($consultations->paginate(
            $listConsultationsRequest->get('per_page') ??
            config('escolalms_consultations.perPage', ConstantEnum::PER_PAGE)
        ));
        ConsultationSimpleResource::extend(function (ConsultationSimpleResource $consultation) {
            return [
                'order_item_id' => $consultation->order_item_id,
                'executed_status' => $consultation->executed_status,
                'executed_at' => $consultation->executed_at,
            ];
        });
        return $consultationsCollection;
    }
}
