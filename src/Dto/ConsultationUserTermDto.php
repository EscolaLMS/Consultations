<?php

namespace EscolaLms\Consultations\Dto;

class ConsultationUserTermDto extends BaseDto
{
    protected string $term;
    protected bool $forAllUsers = false;

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
