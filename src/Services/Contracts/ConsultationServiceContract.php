<?php

namespace EscolaLms\Consultations\Services\Contracts;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationTerm;
use Illuminate\Database\Eloquent\Builder;

interface ConsultationServiceContract
{
    public function getConsultationsList(array $search = [], bool $onlyActive = false): Builder;
    public function store(array $data = []): Consultation;
    public function update(int $id, array $data = []): Consultation;
    public function show(int $id): Consultation;
    public function delete(int $id): ?bool;
    public function setPivotOrderConsultation($order, $user): void;
    public function reportTerm(int $orderItemId, string $executedAt): bool;
    public function approveTerm(int $consultationTermId): bool;
    public function rejectTerm(int $consultationTermId): bool;
    public function setStatus(ConsultationTerm $consultationTerm, string $status): ConsultationTerm;
    public function generateJitsi(int $consultationTermId): array;
    public function canGenerateJitsi(ConsultationTerm $consultationTerm): bool;
}
