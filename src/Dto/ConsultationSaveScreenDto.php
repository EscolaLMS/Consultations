<?php

namespace EscolaLms\Consultations\Dto;

use Illuminate\Http\UploadedFile;

class ConsultationSaveScreenDto extends BaseDto
{
    protected int $consultationId;
    protected int $userTerminId;
    protected string $userEmail;
    protected string $executedAt;
    protected array $files;

    public function getConsultationId(): int
    {
        return $this->consultationId;
    }

    public function getUserTerminId(): int
    {
        return $this->userTerminId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getExecutedAt(): string
    {
        return $this->executedAt;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }
}
