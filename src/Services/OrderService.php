<?php

namespace EscolaLms\Consultations\Services;

use EscolaLms\Consultations\Services\Contracts\OrderServiceContract;

class OrderService extends \EscolaLms\Cart\Services\OrderService implements OrderServiceContract
{
    public function reportTerm(int $id, $term): void
    {
        dd($id, $term);
    }
}
