<?php

namespace EscolaLms\Consultations\Dto;

use EscolaLms\Consultations\Repositories\Criteria\NotNullCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\DateCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\EqualCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\InCriterion;

class FilterConsultationTermsListDto extends BaseDto
{
    private array $status;
    private string $dateTo;
    private string $dateFrom;
    private int $consultationId;

    private array $criteria = [];

    public static function prepareFilters(array $search): self
    {
        $dto = new self($search);
        $dto->addToCriteria(new NotNullCriterion('consultation_user.executed_at'));
        if ($dto->getStatus()) {
            $dto->addToCriteria(new InCriterion('consultation_user.status', $dto->getStatus()));
        }
        if ($dto->getDateFrom()) {
            $dto->addToCriteria(new DateCriterion('consultation_user.executed_at', $dto->getDateFrom(), '>='));
        }
        if ($dto->getDateTo()) {
            $dto->addToCriteria(new DateCriterion('consultation_user.executed_at', $dto->getDateTo(), '<='));
        }
        if ($dto->getConsultationId()) {
            $dto->addToCriteria(new EqualCriterion('consultation_user.consultation_id', $dto->getConsultationId(), '='));
        }
        return $dto;
    }

    public function getCriteria(): ?array
    {
        return $this->criteria ?? null;
    }

    public function getStatus(): ?array
    {
        return $this->status ?? null;
    }

    public function getDateFrom(): ?string
    {
        return $this->dateFrom ?? null;
    }

    public function getDateTo(): ?string
    {
        return $this->dateTo ?? null;
    }

    public function getConsultationId(): ?int
    {
        return $this->consultationId ?? null;
    }

    protected function setStatus(array $status): void
    {
        $this->status = $status;
    }

    protected function setDateFrom(string $dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    protected function setDateTo(string $dateTo): void
    {
        $this->dateTo = $dateTo;
    }

    protected function setConsultationId(int $consultationId): void
    {
        $this->consultationId = $consultationId;
    }

    private function addToCriteria($value): void
    {
        $this->criteria[] = $value;
    }
}
