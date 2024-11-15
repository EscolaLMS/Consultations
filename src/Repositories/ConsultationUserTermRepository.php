<?php

namespace EscolaLms\Consultations\Repositories;

use EscolaLms\Consultations\Dto\ConsultationUserTermResourceDto;
use EscolaLms\Consultations\Dto\FilterConsultationTermsListDto;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Models\ConsultationUserTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserTermRepositoryContract;
use EscolaLms\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ConsultationUserTermRepository extends BaseRepository implements ConsultationUserTermRepositoryContract
{
    protected $fieldSearchable = [];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return ConsultationUserTerm::class;
    }

    public function createUserTerm(ConsultationUserPivot $consultationUserPivot, array $data): ConsultationUserTerm
    {
        return $consultationUserPivot->userTerms()->create($data);
    }

    public function updateUserTermByExecutedAt(ConsultationUserPivot $consultationUserPivot, string $executedAt, array $data): ConsultationUserTerm
    {
        /** @var ConsultationUserTerm $userTerm */
        $userTerm = $consultationUserPivot->userTerms()->where('executed_at', '=', $executedAt)->firstOrFail();

        $userTerm->update($data);

        return $userTerm;
    }

    public function updateByConsultationUserIdAndExecutedAt(int $consultationUserId, string $executedAt, array $data): ConsultationUserTerm
    {
        /** @var ConsultationUserTerm $model */
        $model = $this->model->newQuery()
            ->where('consultation_user_id', '=', $consultationUserId)
            ->where('executed_at', '=', $executedAt)
            ->firstOrFail();

        $model->fill($data);
        $model->save();

        return $model;
    }

    public function allQueryBuilder(?FilterConsultationTermsListDto $filterConsultationTermsListDto = null): Collection
    {
        $query = $this->model->newQuery();

        if ($filterConsultationTermsListDto) {
            $criteria = $filterConsultationTermsListDto->getCriteria();
            if ($criteria) {
                $query = $this->applyCriteria($query, $criteria);
            }
        }

        return $this->applyPagination($query)->get();
    }

    /**
     * @return Collection<int, Model>
     */
    public function getBusyTerms(int $consultationId, ?string $date = null): Collection
    {
        $query = $this->model->newQuery();
        $query->whereHas('consultationUser', fn($query) => $query->where('consultation_id', '=', $consultationId));

        $query->whereNotNull('executed_at');

        if ($date) {
            $query->where([
                'executed_at' => $date
            ]);
        }
        $query->whereIn('executed_status', [ConsultationTermStatusEnum::APPROVED, ConsultationTermStatusEnum::REPORTED]);
        return $query->get();
    }

    public function getAllUserTermsByConsultationIdAndExecutedAt(int $consultationId, string $executedAt): Collection
    {
        return $this->model->newQuery()
            ->whereHas('consultationUser', fn($query) => $query->where('consultation_id', '=', $consultationId))
            ->where('executed_at', '=', $executedAt)
            ->get();
    }

    public function getUserTermByUserIdAndExecutedAt(int $userId, string $executedAt): ConsultationUserTerm
    {
        /** @var ConsultationUserTerm $model */
        $model = $this->model->newQuery()
            ->whereHas('consultationUser', fn (Builder $query) => $query->where('user_id', '=', $userId))
            ->where('executed_at', '=', $executedAt)
            ->firstOrFail();
        return $model;
    }

    public function updateModels(Collection $models, array $data): void
    {
        $this->model->newQuery()->whereIn('id', $models->pluck('id'))->update($data);
    }

    public function getByCurrentUserTutor(): Collection
    {
        $result = collect();
        $terms = $this->model->newQuery()
            ->whereHas('consultationUser', fn (Builder $query) => $query
                ->whereHas('consultation', fn (Builder $query) => $query
                    ->whereAuthorId(auth()->user()->getKey())
                    ->orWhereHas('teachers', fn (Builder $query) => $query->where('users.id', '=', auth()->user()->getKey()))
                )
            )
            ->get();

        /** @var ConsultationUserTerm $term */
        foreach ($terms as $term) {
            /** @var ConsultationUserTermResourceDto|null $userTerm */
            // @phpstan-ignore-next-line
            $userTerm = $result->first(fn (ConsultationUserTermResourceDto $dto) => $dto->consultation_id === $term->consultationUser->consultation_id && $term->executed_at === $dto->executed_at);

            if ($userTerm) {
                $userTerm->users->push($term->consultationUser->user);
            } else {
                $result->push(new ConsultationUserTermResourceDto(
                    $term->consultation_user_id,
                    $term->consultationUser->consultation_id,
                    $term->executed_at,
                    $term->executed_status,
                    $term->consultationUser->consultation->getDuration(),
                    $term->consultationUser->consultation->author,
                    $term->finished_at,
                    collect([$term->consultationUser->user]),
                ));
            }
        }

        return $result;
    }

    public function updateModel(ConsultationUserTerm $userTerm, array $data): ConsultationUserTerm
    {
        $userTerm->fill($data);
        $userTerm->save();
        return $userTerm;
    }
}
