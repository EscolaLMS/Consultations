<?php

namespace EscolaLms\Consultations\Dto;

use EscolaLms\Consultations\Dto\Traits\DtoHelper;
use EscolaLms\Consultations\Repositories\Criteria\ConsultationSearch;
use EscolaLms\Core\Repositories\Criteria\Primitives\DateCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\EqualCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\InCriterion;

class FilterListDto extends BaseDto
{
    private string $name;
    private int $basePrice;
    private array $status;
    private string $activeTo;
    private string $activeFrom;

    private array $criteria = [];

    public static function prepareFilters(array $search)
    {
        $dto = new self($search);
        if ($dto->getName()) {
            $dto->addToCriteria(new ConsultationSearch($dto->getName()));
        }
        if ($dto->getBasePrice()) {
            $dto->addToCriteria(new EqualCriterion('consultations.base_price', $dto->getBasePrice()));
        }
        if ($dto->getStatus()) {
            $dto->addToCriteria(new InCriterion('consultations.status', $dto->getStatus()));
        }
        if ($dto->getActiveFrom()) {
            $dto->addToCriteria(new DateCriterion('consultations.active_from', $dto->getActiveFrom(), '>='));
        }
        if ($dto->getActiveTo()) {
            $dto->addToCriteria(new DateCriterion('consultations.active_to', $dto->getActiveTo(), '<='));
        }
        return $dto->criteria;
    }

    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    public function getBasePrice(): ?int
    {
        return $this->basePrice ?? null;
    }

    public function getStatus(): ?array
    {
        return $this->status ?? null;
    }

    public function getActiveFrom(): ?string
    {
        return $this->activeFrom ?? null;
    }

    public function getActiveTo(): ?string
    {
        return $this->activeTo ?? null;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function setBasePrice(int $basePrice): void
    {
        $this->basePrice = $basePrice;
    }

    protected function setStatus(array $status): void
    {
        $this->status = $status;
    }

    protected function setActiveFrom(string $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    protected function setActiveTo(string $activeTo): void
    {
        $this->activeTo = $activeTo;
    }

    private function addToCriteria($value): void
    {
        $this->criteria[] = $value;
    }
}
