<?php

namespace EscolaLms\Consultations\Services\Contracts;

use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Dto\ConsultationDto;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
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
    public function setPivotOrderConsultation(Order $order, User $user): void;
    public function reportTerm(int $orderItemId, string $executedAt): bool;
    public function approveTerm(int $consultationTermId): bool;
    public function rejectTerm(int $consultationTermId): bool;
    public function setStatus(ConsultationTerm $consultationTerm, string $status): ConsultationTerm;
    public function generateJitsi(int $consultationTermId): array;
    public function canGenerateJitsi(ConsultationTerm $consultationTerm): bool;
    public function proposedTerms(int $orderItemId): ?Collection;
    public function setRelations(Consultation $consultation, array $relations = []): void;
    public function setFiles(Consultation $consultation, array $files = []): void;
    public function getConsultationTermsByConsultationId(int $consultationId, array $search = []): Collection;
    public function getConsultationsListForCurrentUser(array $search = []): Builder;
    public function forCurrentUserResponse(ListConsultationsRequest $listConsultationsRequest): AnonymousResourceCollection;
}
