<?php

namespace EscolaLms\Consultations\Services\Contracts;

interface OrderServiceContract
{
    public function reportTerm(int $id, string $term): void;
}
