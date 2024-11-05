<?php

namespace EscolaLms\Consultations\Repositories\Contracts;

use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Models\ConsultationUserTerm;
use EscolaLms\Core\Repositories\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ConsultationUserTermRepositoryContract extends BaseRepositoryContract
{
    public function createUserTerm(ConsultationUserPivot $consultationUserPivot, array $data): ConsultationUserTerm;

    public function updateUserTermByExecutedAt(ConsultationUserPivot $consultationUserPivot, string $executedAt, array $data): ConsultationUserTerm;
    public function updateByConsultationUserIdAndExecutedAt(int $consultationUserId, string $executedAt, array $data): ConsultationUserTerm;
    public function allQueryBuilder(?FilterConsultationTermsListDto $filterConsultationTermsListDto = null): Collection;
    /**
     * @return Collection<int, ConsultationUserTerm>
     */
    public function getBusyTerms(int $consultationId, ?string $date = null): Collection;
    public function getAllUserTermsByConsultationIdAndExecutedAt(int $consultationId, string $executedAt): Collection;
    public function getUserTermByConsultationUserIdAndExecutedAt(int $consultationUserId, string $executedAt): ConsultationUserTerm;
    public function updateModels(Collection $models, array $data): void;
    public function getByCurrentUserTutor(): \Illuminate\Support\Collection;

}
