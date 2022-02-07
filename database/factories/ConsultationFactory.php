<?php

namespace EscolaLms\Consultations\Database\Factories;

use EscolaLms\Auth\Models\User;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Core\Enums\UserRole;
use EscolaLms\Courses\Database\Factories\FakerMarkdownProvider\FakerProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    public function definition()
    {
        $tutor = User::role(UserRole::TUTOR)->inRandomOrder()->first();
        $now = now();
        return [
            'base_price' => $this->faker->numberBetween(1, 200),
            'name' => $this->faker->word,
            'status' => $this->faker->randomElement(ConsultationStatusEnum::getValues()),
            'duration' => random_int(2, 10) . " hours",
            'description' => $this->faker->sentence,
            'author_id' => empty($tutor) ? null : $tutor->getKey(),
            'started_at' => $now,
            'finished_at' => (clone $now)->modify('+1 hour'),
        ];
    }
}
