<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Consultations\Dto\FilterListDto;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
        DB::beginTransaction();
        try {
            $consultation = $this->consultationRepositoryContract->create($data);
            DB::commit();
            return $consultation;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException(__('Consultation create failed'));
        }
    }

    public function update(int $id, array $data = []): Consultation
    {
        $consultation = $this->show($id);
        DB::beginTransaction();
        try {
            $consultation = $this->consultationRepositoryContract->updateModel($consultation, $data);
            DB::commit();
            return $consultation;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException(__('Consultation update failed'));
        }
    }

    public function show(int $id): Consultation
    {
        $consultation = $this->consultationRepositoryContract->find($id);
        if (!$consultation) {
            throw new UnprocessableEntityHttpException(__('Consultation not found'));
        }
        return $consultation;
    }

    public function delete(int $id): bool
    {
        DB::beginTransaction();
        try {
            $this->consultationRepositoryContract->delete($id);
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException(__('Consultation deleted failed'));
        }
    }
}
