<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ConsultationService implements ConsultationServiceContract
{
    private ConsultationRepositoryContract $consultationRepositoryContract;

    public function __construct(
        ConsultationRepositoryContract $consultationRepositoryContract
    ) {
        $this->consultationRepositoryContract = $consultationRepositoryContract;
    }

    public function getConsultationsList(array $search = []): Builder
    {
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
}
