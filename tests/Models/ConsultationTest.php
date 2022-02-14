<?php

namespace EscolaLms\Consultations\Tests\Models;

use EscolaLms\Consultations\Database\Factories\ConsultationFactory;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\Database\Factories\ConsultationTestFactory;
use Treestoneit\ShoppingCart\Buyable;
use Treestoneit\ShoppingCart\BuyableTrait;

class ConsultationTest extends Consultation implements Buyable
{
    use BuyableTrait;

    protected $table = 'consultations';

    public function getBuyablePrice(): int
    {
        return $this->base_price ?? 0;
    }

    protected static function newFactory(): ConsultationFactory
    {
        return ConsultationTestFactory::new();
    }
}
