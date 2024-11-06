<?php

namespace EscolaLms\Consultations\Dto;

class ChangeTermConsultationDto extends BaseDto
{
    protected string $executedAt;
    protected string $term;
    protected bool $forAllUsers = false;

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

    protected function setForAllUsers(bool $forAllUsers): void
    {
        $this->forAllUsers = $forAllUsers;
    }

    public function getForAllUsers(): bool
    {
        return $this->forAllUsers;
    }
}
