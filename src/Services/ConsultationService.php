<?php

namespace EscolaLms\Consultations\Services;

use Carbon\Carbon;
use EscolaLms\Consultations\Models\User;
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
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Jitsi\Services\Contracts\JitsiServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConsultationService implements ConsultationServiceContract
{
    private ConsultationRepositoryContract $consultationRepositoryContract;
    private ConsultationUserRepositoryContract $consultationUserRepositoryContract;
    private JitsiServiceContract $jitsiServiceContract;

    public function __construct(
        ConsultationRepositoryContract $consultationRepositoryContract,
        ConsultationUserRepositoryContract $consultationUserRepositoryContract,
        JitsiServiceContract $jitsiServiceContract
    ) {
        $this->consultationRepositoryContract = $consultationRepositoryContract;
        $this->consultationUserRepositoryContract = $consultationUserRepositoryContract;
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

    public function reportTerm(int $consultationTermId, string $executedAt): bool
    {
        return DB::transaction(function () use ($consultationTermId, $executedAt) {
            $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
            $data = [
                'executed_status' => ConsultationTermStatusEnum::REPORTED,
                'executed_at' => $executedAt
            ];
            $this->consultationUserRepositoryContract->updateModel($consultationTerm, $data);
            $author = $consultationTerm->consultation->author;
            event(new ReportTerm($author, $consultationTerm));
            return true;
        });
    }

    public function approveTerm(int $consultationTermId): bool
    {
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::APPROVED);
        event(new ApprovedTerm($consultationTerm->user, $consultationTerm));
        return true;
    }

    public function rejectTerm(int $consultationTermId): bool
    {
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::REJECT);
        event(new RejectTerm($consultationTerm->user, $consultationTerm));
        return true;
    }

    public function setStatus(ConsultationUserPivot $consultationTerm, string $status): ConsultationUserPivot
    {
        return DB::transaction(function () use ($status, $consultationTerm) {
            if ($consultationTerm->executed_status !== ConsultationTermStatusEnum::REPORTED) {
                throw new NotFoundHttpException(__('Consultation term not found'));
            }
            return $this->consultationUserRepositoryContract->updateModel($consultationTerm, ['executed_status' => $status]);
        });
    }

    public function generateJitsi(int $consultationTermId): array
    {
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        if ($this->canGenerateJitsi($consultationTerm)) {
            throw new NotFoundHttpException(__('Consultation term is not available'));
        }

        return $this->jitsiServiceContract->getChannelData(
            auth()->user(),
            Str::studly($consultationTerm->consultation->name)
        );
    }

    public function canGenerateJitsi(ConsultationUserPivot $consultationTerm): bool
    {
        $modifyTimeStrings = [
            'seconds', 'minutes', 'hours', 'weeks', 'years'
        ];
        $now = now();
        $explode = explode(' ', $consultationTerm->consultation->duration);
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

    public function proposedTerms(int $consultationTermId): ?Collection
    {
        $consultationUserPivot = $this->consultationUserRepositoryContract->find($consultationTermId);
        return $consultationUserPivot->consultation->proposedTerms ?? null;
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
        return $this->consultationUserRepositoryContract->allQueryBuilder(
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
                'consultation_user.id as cuid',
                'consultation_user.executed_status',
                'consultation_user.executed_at',
            )
            ->leftJoin('consultation_user', 'consultation_user.consultation_id', '=', 'consultations.id');
        $consultationsCollection = ConsultationSimpleResource::collection($consultations->paginate(
            $listConsultationsRequest->get('per_page') ??
            config('escolalms_consultations.perPage', ConstantEnum::PER_PAGE)
        ));
        ConsultationSimpleResource::extend(function (ConsultationSimpleResource $consultation) {
            return [
                'consultation_user_id' => $consultation->cuid,
                'executed_status' => $consultation->executed_status,
                'executed_at' => $consultation->executed_at,
            ];
        });
        return $consultationsCollection;
    }

    public function attachToUser(Consultation $consultation, User $user): void
    {
        $data = [
            'consultation_id' => $consultation->getKey(),
            'user_id' => $user->getKey(),
            'executed_status' => ConsultationTermStatusEnum::NOT_REPORTED
        ];
        $this->consultationUserRepositoryContract->create($data);
    }
}
