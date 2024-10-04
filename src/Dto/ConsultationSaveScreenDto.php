<?php

namespace EscolaLms\Consultations\Dto;

use Illuminate\Http\UploadedFile;

class ConsultationSaveScreenDto extends BaseDto
{
    protected int $consultationId;
    protected int $userTerminId;
    protected string $userEmail;
    protected UploadedFile|string $file;
    protected string $timestamp;

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

    public function getFile(): string|UploadedFile
    {
        return $this->file;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
