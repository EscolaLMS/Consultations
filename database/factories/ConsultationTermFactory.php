<?php

namespace EscolaLms\Consultations\Database\Factories;

use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\ConsultationTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationTermFactory extends Factory
{
    protected $model = ConsultationTerm::class;

    public function definition()
    {
        $now = now()->modify('+2 hours');
        return [
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => $this->faker->randomElement(ConsultationTermStatusEnum::getValues()),
        ];
    }
}
