<?php

namespace EscolaLms\Consultations\Dto;

class ChangeTermConsultationDto extends BaseDto
{
    protected string $executedAt;
    protected string $term;
    protected ?int $userId = null;

    protected function setExecutedAt(string $executedAt): void
    {
        $this->executedAt = $executedAt;
    }

    public function getExecutedAt(): string
    {
        return $this->executedAt;
    }

    protected function setTerm(string $term): void
    {
        $this->term = $term;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    protected function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
