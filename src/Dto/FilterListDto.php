<?php

namespace EscolaLms\Consultations\Dto;

use EscolaLms\Consultations\Repositories\Criteria\CategoriesCriterion;
use EscolaLms\Consultations\Repositories\Criteria\ConsultationSearch;
use EscolaLms\Consultations\Repositories\Criteria\ConsultationTermEqualCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\DateCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\HasCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\InCriterion;

class FilterListDto extends BaseDto
{
    private string $name;
    private int $consultationTermId;
    private array $status;
    private string $dateTo;
    private string $dateFrom;
    private array $categories;
    private bool $onlyWithCategories;

    private array $criteria = [];

    public static function prepareFilters(array $search)
    {
        $dto = new self($search);
        if ($dto->getName()) {
            $dto->addToCriteria(new ConsultationSearch($dto->getName()));
        }
        if ($dto->getStatus()) {
            $dto->addToCriteria(new InCriterion('consultations.status', $dto->getStatus()));
        }
        if ($dto->getDateFrom()) {
            $dto->addToCriteria(new DateCriterion('consultations.active_from', $dto->getDateFrom(), '>='));
        }
        if ($dto->getDateTo()) {
            $dto->addToCriteria(new DateCriterion('consultations.active_to', $dto->getDateTo(), '<='));
        }
        if ($dto->getCategories()) {
            $dto->addToCriteria(new CategoriesCriterion($dto->getCategories()));
        }
        if ($dto->getConsultationTermId()) {
            $dto->addToCriteria(new ConsultationTermEqualCriterion($dto->getConsultationTermId()));
        }
        if ($dto->getOnlyWithCategories()) {
            $dto->addToCriteria(new HasCriterion('categories', null));
        }
        return $dto->criteria;
    }

    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    public function getConsultationTermId(): ?int
    {
        return $this->consultationTermId ?? null;
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

    public function getCategories(): ?array
    {
        return $this->categories ?? null;
    }

    public function getOnlyWithCategories(): ?bool
    {
        return $this->onlyWithCategories ?? null;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function setOnlyWithCategories(string $onlyWithCategories): void
    {
        $this->onlyWithCategories = $onlyWithCategories === 'true';
    }

    protected function setConsultationTermId(int $consultationTermId): void
    {
        $this->consultationTermId = $consultationTermId;
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

    protected function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    private function addToCriteria($value): void
    {
        $this->criteria[] = $value;
    }
}
