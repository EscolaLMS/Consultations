<?php

namespace EscolaLms\Consultations\Services;

use Carbon\Carbon;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Events\ChangeTerm;
use EscolaLms\Consultations\Events\RejectTermWithTrainer;
use EscolaLms\Consultations\Events\ReminderTrainerAboutTerm;
use EscolaLms\Consultations\Exceptions\ChangeTermException;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use EscolaLms\Core\Models\User as CoreUser;
use EscolaLms\Consultations\Events\ReminderAboutTerm;
use EscolaLms\Consultations\Dto\ConsultationDto;
use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\ApprovedTermWithTrainer;
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
            $search['active_to'] = isset($search['active_to']) ? Carbon::make($search['active_to'])->format('Y-m-d') : $now;
            $search['active_from'] = isset($search['active_from']) ? Carbon::make($search['active_from'])->format('Y-m-d') : $now;
            $search['status'] = [ConsultationStatusEnum::PUBLISHED];
        }
        $criteria = FilterListDto::prepareFilters($search);
        return $this->consultationRepositoryContract->allQueryBuilder(
            $search,
            $criteria
        );
    }

    public function getConsultationsListForCurrentUser(array $search = []): Builder
    {
        $now = now();
        $search['active_to'] = isset($search['active_to']) ? Carbon::make($search['active_to']) : $now;
        $search['active_from'] = isset($search['active_from']) ? Carbon::make($search['active_from']) : $now;
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
            if ($this->termIsBusy($consultationTerm->consultation_id, $executedAt)) {
                abort(400, __('Term is busy, change your term.'));
            }
            $data = [
                'executed_status' => ConsultationTermStatusEnum::REPORTED,
                'executed_at' => Carbon::make($executedAt)
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
        event(new ApprovedTermWithTrainer(auth()->user(), $consultationTerm));
        return true;
    }

    public function rejectTerm(int $consultationTermId): bool
    {
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::REJECT);
        event(new RejectTerm($consultationTerm->user, $consultationTerm));
        event(new RejectTermWithTrainer(auth()->user(), $consultationTerm));
        return true;
    }

    public function setStatus(ConsultationUserPivot $consultationTerm, string $status): ConsultationUserPivot
    {
        return DB::transaction(function () use ($status, $consultationTerm) {
            return $this->consultationUserRepositoryContract->updateModel($consultationTerm, ['executed_status' => $status]);
        });
    }

    public function generateJitsi(int $consultationTermId): array
    {
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        if (!$this->canGenerateJitsi(
            $consultationTerm->executed_at,
            $consultationTerm->executed_status,
            $consultationTerm->consultation->getDuration()
        )) {
            throw new NotFoundHttpException(__('Consultation term is not available'));
        }
        $isModerator = false;
        $configOverwrite = [];
        $configInterface = [];
        if ($consultationTerm->consultation->author === auth()->user()->getKey()) {
            $configOverwrite = [
                "disableModeratorIndicator" => true,
                "startScreenSharing" => false,
                "enableEmailInStats" => false,
            ];
            $isModerator = true;
        }
        if ($consultationTerm->consultation->logotype_path) {
            $configInterface = [
                'BRAND_WATERMARK_LINK' => '',
                'DEFAULT_LOGO_URL' => $consultationTerm->consultation->logotype_url,
                'DEFAULT_WELCOME_PAGE_LOGO_URL' => $consultationTerm->consultation->logotype_url,
                'HIDE_INVITE_MORE_HEADER' => true
            ];
        }

        return $this->jitsiServiceContract->getChannelData(
            auth()->user(),
            Str::studly($consultationTerm->consultation->name),
            $isModerator,
            $configOverwrite,
            $configInterface
        );
    }

    public function canGenerateJitsi(?string $executedAt, ?string $status, ?string $duration): bool
    {
        $now = now();
        if (isset($executedAt)) {
            $dateTo = Carbon::make($executedAt);
            return in_array($status, [ConsultationTermStatusEnum::APPROVED, ConsultationTermStatusEnum::REJECT]) &&
                $now->getTimestamp() >= $dateTo->getTimestamp() &&
                !$this->isEnded($executedAt, $duration);
        }
        return false;
    }

    public function generateDateTo(string $dateTo, string $duration): ?Carbon
    {
        $modifyTimeStrings = [
            'seconds', 'second', 'minutes', 'minute', 'hours', 'hour', 'weeks', 'week', 'years', 'year'
        ];
        $explode = array_filter(explode(' ', $duration));
        $count = $explode[0] ?? 0;
        $string = $explode[1] ?? 'hours';
        $string = in_array($string, $modifyTimeStrings) ? $string : 'hours';
        return Carbon::make($dateTo)->modify('+' . ((int)$count) . ' ' . $string);
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

    public function proposedTerms(int $consultationTermId): ?array
    {
        $consultationUserPivot = $this->consultationUserRepositoryContract->find($consultationTermId);
        return $this->filterProposedTerms($consultationUserPivot->consultation_id, $consultationUserPivot->consultation->proposedTerms) ?? null;
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
        $consultationsCollection = ConsultationSimpleResource::collection($consultations->paginate(
            $listConsultationsRequest->get('per_page') ??
            config('escolalms_consultations.perPage', ConstantEnum::PER_PAGE)
        ));
        ConsultationSimpleResource::extend(function (ConsultationSimpleResource $consultation) {
            return [
                'consultation_term_id' => $consultation->consultation_user_id,
                'name' => $consultation->name,
                'image_path' => $consultation->image_path,
                'image_url' => $consultation->image_url,
                'executed_status' => $consultation->executed_status,
                'executed_at' => Carbon::make($consultation->executed_at),
                'is_started' => $this->isStarted(
                    $consultation->executed_at,
                    $consultation->executed_status,
                    $consultation->getDuration()
                ),
                'is_ended' => $this->isEnded($consultation->executed_at, $consultation->getDuration()),
                'in_coming' => $this->inComing(
                    $consultation->executed_at,
                    $consultation->executed_status,
                    $consultation->getDuration()
                ),
            ];
        });
        return $consultationsCollection;
    }

    public function attachToUser(array $data): void
    {
        $this->consultationUserRepositoryContract->create($data);
    }

    public function isEnded(?string $executedAt, ?string $duration): bool
    {
        if ($executedAt && $duration !== '') {
            $dateTo = $this->generateDateTo($executedAt, $duration);
            return $dateTo->getTimestamp() <= now()->getTimestamp();
        }
        return false;
    }

    public function isStarted(?string $executedAt, ?string $status, ?string $duration): bool
    {
        return $this->canGenerateJitsi($executedAt, $status, $duration);
    }

    public function inComing(?string $executedAt, ?string $status, ?string $duration): bool
    {
        return !$this->isStarted($executedAt, $status, $duration) && !$this->isEnded($executedAt, $duration);
    }

    public function reminderAboutConsultation(string $reminderStatus): void
    {
        foreach ($this->getReminderData($reminderStatus) as $consultationTerm) {
            event(new ReminderAboutTerm(
                $consultationTerm->user,
                $consultationTerm,
                $reminderStatus
            ));
            event(new ReminderTrainerAboutTerm(
                $consultationTerm->consultation->author,
                $consultationTerm,
                $reminderStatus
            ));
        }
    }

    public function setReminderStatus(ConsultationUserPivot $consultationTerm, string $status): void
    {
        $this->consultationUserRepositoryContract->updateModel($consultationTerm, ['reminder_status' => $status]);
    }

    public function changeTerm(int $consultationTermId, string $executedAt): bool
    {
        DB::transaction(function () use($consultationTermId, $executedAt) {
            if ($consultationUser = $this->consultationUserRepositoryContract->update([
                'executed_at' => Carbon::make($executedAt),
                'executed_status' => ConsultationTermStatusEnum::APPROVED
            ], $consultationTermId)) {
                if (!$consultationUser->user || !$consultationUser) {
                    throw new ChangeTermException(__('Term is changed but not executed event because user or term is no exists'));
                }
                event(new ChangeTerm($consultationUser->user, $consultationUser));
                return true;
            }
            throw new ChangeTermException(__('Term is not changed'));
        });
        return false;
    }

    public function getConsultationTermsForTutor(): Collection
    {
        return $this->consultationUserRepositoryContract->getByCurrentUserTutor();
    }

    public function termIsBusy(int $consultationId, string $date): bool
    {
        return $this->consultationUserRepositoryContract->getBusyTerms($consultationId, $date)->count() > 0;
    }

    public function getBusyTermsFormatDate(int $consultationId): array
    {
        return $this->consultationUserRepositoryContract->getBusyTerms($consultationId)->map(
            fn ($term) => Carbon::make($term->executed_at)
        )->unique()->toArray();
    }

    public function filterProposedTerms(int $consultationId, Collection $proposedTerms): array
    {
        $busyTerms = $this->getBusyTermsFormatDate($consultationId);
        return $proposedTerms->map(fn(ConsultationProposedTerm $proposedTerm) => Carbon::make($proposedTerm->proposed_at))->filter(fn (Carbon $proposedTerm) => !in_array($proposedTerm, $busyTerms))->toArray();
    }

    private function getReminderData(string $reminderStatus): Collection
    {
        $now = now();
        $reminderDate = now()->modify(config('escolalms_consultations.modifier_date.' . $reminderStatus, '+1 hour'));
        $exclusionStatuses = config('escolalms_consultations.exclusion_reminder_status.' . $reminderStatus, []);
        $data = [
            'date_time_to' => $reminderDate,
            'date_time_from' => $now,
            'reminder_status' => $exclusionStatuses,
            'status' => [ConsultationTermStatusEnum::APPROVED]
        ];
        return $this->consultationUserRepositoryContract->getIncomingTerm(
            FilterConsultationTermsListDto::prepareFilters($data)->getCriteria()
        );
    }
}
