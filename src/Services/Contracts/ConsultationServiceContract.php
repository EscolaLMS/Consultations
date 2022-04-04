<?php

namespace EscolaLms\Consultations\Services\Contracts;

use Carbon\Carbon;
use EscolaLms\Consultations\Models\User;
use EscolaLms\Consultations\Dto\ConsultationDto;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Core\Models\User as CoreUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

interface ConsultationServiceContract
{
    public function getConsultationsList(array $search = [], bool $onlyActive = false): Builder;
    public function store(ConsultationDto $consultationDto): Consultation;
    public function update(int $id, ConsultationDto $consultationDto): Consultation;
    public function show(int $id): Consultation;
    public function delete(int $id): ?bool;
    public function attachToUser(Consultation $consultation, CoreUser $user): void;
    public function reportTerm(int $orderItemId, string $executedAt): bool;
    public function approveTerm(int $consultationTermId): bool;
    public function rejectTerm(int $consultationTermId): bool;
    public function setStatus(ConsultationUserPivot $consultationTerm, string $status): ConsultationUserPivot;
    public function generateJitsi(int $consultationTermId): array;
    public function canGenerateJitsi(?string $executedAt, ?string $status, ?string $duration): bool;
    public function proposedTerms(int $consultationTermId): ?Collection;
    public function setRelations(Consultation $consultation, array $relations = []): void;
    public function setFiles(Consultation $consultation, array $files = []): void;
    public function getConsultationTermsByConsultationId(int $consultationId, array $search = []): Collection;
    public function getConsultationsListForCurrentUser(array $search = []): Builder;
    public function forCurrentUserResponse(ListConsultationsRequest $listConsultationsRequest): AnonymousResourceCollection;
    public function generateDateTo(string $dateTo, string $duration): ?Carbon;
    public function isEnded(?string $executedAt, ?string $duration): bool;
    public function isStarted(?string $executedAt, ?string $status, ?string $duration): bool;
    public function reminderAboutConsultation(string $reminderStatus): void;
    public function setReminderStatus(ConsultationUserPivot $consultationTerm, string $status): void;
    public function changeTerm(int $consultationTermId, string $executedAt): bool;
    public function getConsultationTermsForTutor(): Collection;
}
