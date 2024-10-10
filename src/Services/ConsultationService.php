<?php

namespace EscolaLms\Consultations\Services;

use Auth;
use Carbon\Carbon;
use EscolaLms\Consultations\Dto\ConsultationDto;
use EscolaLms\Consultations\Dto\ConsultationSaveScreenDto;
use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\ApprovedTermWithTrainer;
use EscolaLms\Consultations\Events\ChangeTerm;
use EscolaLms\Consultations\Events\RejectTerm;
use EscolaLms\Consultations\Events\RejectTermWithTrainer;
use EscolaLms\Consultations\Events\ReminderAboutTerm;
use EscolaLms\Consultations\Events\ReminderTrainerAboutTerm;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Exceptions\ChangeTermException;
use EscolaLms\Consultations\Exceptions\ConsultationNotFound;
use EscolaLms\Consultations\Helpers\StrategyHelper;
use EscolaLms\Consultations\Http\Requests\ConsultationScreenSaveRequest;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Http\Resources\ConsultationSimpleResource;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Models\User;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Core\Dtos\OrderDto;
use EscolaLms\Files\Helpers\FileHelper;
use EscolaLms\Jitsi\Helpers\StringHelper;
use EscolaLms\Jitsi\Services\Contracts\JitsiServiceContract;
use EscolaLms\ModelFields\Facades\ModelFields;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function getConsultationsList(array $search = [], bool $onlyActive = false, OrderDto $orderDto = null): Builder
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
        )->orderBy($orderDto->getOrderBy() ?? 'created_at', $orderDto->getOrder() ?? 'desc');
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
            /** @var Consultation $consultation */
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
        /** @var Consultation|null $consultation */
        $consultation = $this->consultationRepositoryContract->find($id);
        if (!$consultation) {
            throw new ConsultationNotFound();
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
            /** @var ConsultationUserPivot $consultationTerm */
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
        /** @var ConsultationUserPivot $consultationTerm */
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::APPROVED);
        event(new ApprovedTerm($consultationTerm->user, $consultationTerm));
        /** @var User $authUser */
        $authUser = auth()->user();
        event(new ApprovedTermWithTrainer($authUser, $consultationTerm));
        return true;
    }

    public function rejectTerm(int $consultationTermId): bool
    {
        /** @var ConsultationUserPivot $consultationTerm */
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        $this->setStatus($consultationTerm, ConsultationTermStatusEnum::REJECT);
        event(new RejectTerm($consultationTerm->user, $consultationTerm));
        /** @var User $authUser */
        $authUser = auth()->user();
        event(new RejectTermWithTrainer($authUser, $consultationTerm));
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
        /** @var ConsultationUserPivot $consultationTerm */
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
        if ($consultationTerm->consultation->author->getKey() === auth()->user()->getKey() || in_array(auth()->user()->getKey(), $consultationTerm->consultation->teachers()->pluck('users.id')->toArray())) {
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
        /** @var User $authUser */
        $authUser = auth()->user();
        return $this->jitsiServiceContract->getChannelData(
            $authUser,
            StringHelper::convertToJitsiSlug($consultationTerm->consultation->name, [], ConstantEnum::DIRECTORY, $consultationTerm->consultation_id, Carbon::make($consultationTerm->executed_at)->getTimestamp()),
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

    public function generateJitsiUrlForEmail(int $consultationTermId, int $userId): ?string
    {
        /** @var ConsultationUserPivot $consultationTerm */
        $consultationTerm = $this->consultationUserRepositoryContract->find($consultationTermId);
        $isModerator = false;
        $configOverwrite = [];
        $configInterface = [];
        if ($consultationTerm->consultation->author->getKey() === $userId || in_array($userId, $consultationTerm->consultation->teachers()->pluck('users.id')->toArray())) {
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
        $user = User::find($userId);
        $result = $this->jitsiServiceContract->getChannelData(
            $user,
            StringHelper::convertToJitsiSlug($consultationTerm->consultation->name, [], ConstantEnum::DIRECTORY, $consultationTerm->consultation_id, Carbon::make($consultationTerm->executed_at)->getTimestamp()),
            $isModerator,
            $configOverwrite,
            $configInterface
        );
        return key_exists('url', $result) ? $result['url'] : null;
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
        /** @var ConsultationUserPivot $consultationUserPivot */
        $consultationUserPivot = $this->consultationUserRepositoryContract->find($consultationTermId);
        return $this->filterProposedTerms($consultationUserPivot->consultation_id, $consultationUserPivot->consultation->proposedTerms);
    }

    public function setFiles(Consultation $consultation, array $files = []): void
    {
        foreach ($files as $key => $file) {
            $consultation->$key = FileHelper::getFilePath($file, ConstantEnum::DIRECTORY . "/{$consultation->getKey()}/images");
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

    public function forCurrentUserResponse(
        ListConsultationsRequest $listConsultationsRequest
    ): AnonymousResourceCollection {
        $search = $listConsultationsRequest->except(['limit', 'skip', 'order', 'order_by', 'paginate']);
        $consultations = $this->getConsultationsListForCurrentUser($search);
        if ($listConsultationsRequest->input('paginate', false)) {
            $consultationsCollection = ConsultationSimpleResource::collection($consultations->paginate(
                $listConsultationsRequest->get('per_page') ??
                config('escolalms_consultations.perPage', ConstantEnum::PER_PAGE)
            ));
        } else {
            $consultationsCollection = ConsultationSimpleResource::collection($consultations->get());
        }
        ConsultationSimpleResource::extend(function (ConsultationSimpleResource $consultation) {
            return [
                'consultation_term_id' => $consultation->resource->consultation_user_id,
                'name' => $consultation->resource->name,
                'image_path' => $consultation->resource->image_path,
                'image_url' => $consultation->resource->image_url,
                'executed_status' => $consultation->resource->executed_status,
                'executed_at' => Carbon::make($consultation->resource->executed_at),
                'is_started' => $this->isStarted(
                    $consultation->resource->executed_at,
                    $consultation->resource->executed_status,
                    $consultation->resource->getDuration()
                ),
                'is_ended' => $this->isEnded($consultation->resource->executed_at, $consultation->resource->getDuration()),
                'in_coming' => $this->inComing(
                    $consultation->resource->executed_at,
                    $consultation->resource->executed_status,
                    $consultation->resource->getDuration()
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
            if ($consultationTerm->consultation->teachers) {
                $consultationTerm->consultation->teachers->each(
                    fn (User $teacher) => event(new ReminderTrainerAboutTerm(
                        $teacher,
                        $consultationTerm,
                        $reminderStatus
                    ))
                );
            } else {
                event(new ReminderTrainerAboutTerm(
                    $consultationTerm->consultation->author,
                    $consultationTerm,
                    $reminderStatus
                ));
            }
        }
    }

    public function setReminderStatus(ConsultationUserPivot $consultationTerm, string $status): void
    {
        $this->consultationUserRepositoryContract->updateModel($consultationTerm, ['reminder_status' => $status]);
    }

    public function changeTerm(int $consultationTermId, string $executedAt): bool
    {
        DB::transaction(function () use ($consultationTermId, $executedAt) {
            try {
                /** @var ConsultationUserPivot $consultationUser */
                $consultationUser = $this->consultationUserRepositoryContract->update([
                    'executed_at' => Carbon::make($executedAt),
                    'executed_status' => ConsultationTermStatusEnum::APPROVED
                ], $consultationTermId);
                if (!$consultationUser->user) {
                    throw new ChangeTermException(__('Term is changed but not executed event because user or term is no exists'));
                }
                event(new ChangeTerm($consultationUser->user, $consultationUser));
                return true;
            } catch (Exception $e) {
                throw new ChangeTermException(__('Term is not changed'));
            }
        });
        return false;
    }

    public function getConsultationTermsForTutor(): Collection
    {
        return $this->consultationUserRepositoryContract->getByCurrentUserTutor();
    }

    public function termIsBusy(int $consultationId, string $date): bool
    {
        /** @var Consultation $consultation */
        $consultation = $this->consultationRepositoryContract->find($consultationId);
        $terms = $this->consultationUserRepositoryContract->getBusyTerms($consultationId, $date);
        $userId = Auth::user()->getKey();
        if ($terms->first(fn (ConsultationUserPivot $userPivot) => $userPivot->user_id === $userId) !== null) {
            abort(400, __('You already reported this term.'));
        }

        return $terms->count() >= $consultation->max_session_students;
    }

    public function termIsBusyForUser(int $consultationId, string $date, int $userId): bool
    {
        /** @var Consultation $consultation */
        $consultation = $this->consultationRepositoryContract->find($consultationId);
        $terms = $this->consultationUserRepositoryContract->getBusyTerms($consultationId, $date);
        if ($terms->first(fn (ConsultationUserPivot $userPivot) => $userPivot->user_id === $userId) !== null) {
            abort(400, __('Term is busy for this user.'));
        }

        return $terms->count() >= $consultation->max_session_students;
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

    public function updateModelFieldsFromRequest(Consultation $consultation, FormRequest $request): void
    {
        $keys = ModelFields::getFieldsMetadata(Consultation::class)->pluck('name');
        $fields = $request->collect()->only($keys)->toArray();
        $this->consultationRepositoryContract->update($fields, $consultation->getKey());
    }

    private function getReminderData(string $reminderStatus): Collection
    {
        $dateTimeFrom = now()
            ->modify(config('escolalms_consultations.modifier_date.' . $reminderStatus, '+1 hour'))
            ->subMinutes(30);
        $dateTimeTo = now()
            ->modify(config('escolalms_consultations.modifier_date.' . $reminderStatus, '+1 hour'))
            ->addMinutes(30);
        $exclusionStatuses = config('escolalms_consultations.exclusion_reminder_status.' . $reminderStatus, []);
        $data = [
            'date_time_to' => $dateTimeTo,
            'date_time_from' => $dateTimeFrom,
            'reminder_status' => $exclusionStatuses,
            'status' => [ConsultationTermStatusEnum::APPROVED]
        ];
        return $this->consultationUserRepositoryContract->getIncomingTerm(
            FilterConsultationTermsListDto::prepareFilters($data)->getCriteria()
        );
    }

    public function saveScreen(ConsultationSaveScreenDto $dto): void
    {
        /** @var ConsultationUserPivot $consultationUser */
        $consultationUser = ConsultationUserPivot::query()->where('consultation_id', '=', $dto->getConsultationId())->where('id', '=', $dto->getUserTerminId())->firstOrFail();
        $user = User::query()->where('email', '=', $dto->getUserEmail())->firstOrFail();

        if ($user->getKey() !== $consultationUser->user_id || $consultationUser->executed_at === null) {
            throw new NotFoundHttpException(__('Consultation term for this user is not available'));
        }

        $termin = Carbon::make($consultationUser->executed_at);
        // consultation_id/term_start_timestamp/user_id/timestamp.jpg
        $folder = "consultations/{$dto->getConsultationId()}/{$termin->getTimestamp()}/{$user->getKey()}";

        $extension = $dto->getFile() instanceof UploadedFile ? $dto->getFile()->getClientOriginalExtension() : Str::between($dto->getFile(), 'data:image/', ';base64');
        Storage::putFileAs($folder, $dto->getFile(), Carbon::make($dto->getTimestamp())->getTimestamp() . '.' . $extension);
    }
}
