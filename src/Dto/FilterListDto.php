<?php

namespace EscolaLms\Consultations\Dto;

use EscolaLms\Consultations\Dto\Traits\DtoHelper;
use EscolaLms\Consultations\Repositories\Criteria\ConsultationSearch;
use EscolaLms\Core\Repositories\Criteria\Primitives\EqualCriterion;
use EscolaLms\Core\Repositories\Criteria\Primitives\InCriterion;

class FilterListDto
{
    use DtoHelper;

    private string $name;
    private int $basePrice;
    private array $status;

    private array $criteria = [];

    public function __construct(array $data = [])
    {
        $this->setterByData($data);
    }

    public static function prepareFilters(array $search)
    {
        $dto = new self($search);
        if ($dto->getName()) {
            $dto->addToCriteria(new ConsultationSearch($dto->getName()));
        }
        if ($dto->getBasePrice()) {
            $dto->addToCriteria(new EqualCriterion('base_price', $dto->getBasePrice()));
        }
        if ($dto->getStatus()) {
            $dto->addToCriteria(new InCriterion('status', $dto->getStatus()));
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

    private function addToCriteria($value): void
    {
        $this->criteria[] = $value;
    }
}
