<?php

namespace EscolaLms\Consultations\Database\Factories;

use EscolaLms\Consultations\Models\ConsultationUserProposedTerm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConsultationUserProposedTermFactory extends Factory
{
    protected $model = ConsultationUserProposedTerm::class;

    public function definition(): array
    {
        return [
            'consultation_user_id' => ConsultationUserFactory::class,
            'proposed_at' => $this->faker->dateTimeBetween(Carbon::now(), Carbon::now()->addMonth()),
        ];
    }
}
