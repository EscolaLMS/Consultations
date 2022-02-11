<?php

namespace EscolaLms\Consultations\Dto;

use EscolaLms\Consultations\Dto\Contracts\ModelDtoContract;
use EscolaLms\Consultations\Models\Consultation;

class ConsultationDto extends BaseDto implements ModelDtoContract
{
    protected string $name;
    protected string $status;
    protected string $description;
    protected ?string $startedAt;
    protected ?string $finishedAt;
    protected ?int $basePrice;
    protected ?int $authorId;

    public function model(): Consultation
    {
        return Consultation::newModelInstance();
    }

    public function toArray($filters = false): array
    {
        $result = $this->fillInArray($this->model()->getFillable());
        return $filters ? array_filter($result) : $result;
    }
}
