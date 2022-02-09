<?php

namespace EscolaLms\Consultations\Database\Factories;

use EscolaLms\Auth\Models\User;
use EscolaLms\Consultations\Enum\ConsultationParticipantStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationParticipantFactory extends Factory
{
    protected $model = ConsultationParticipant::class;

    public function definition()
    {
        $consultation = Consultation::inRandomOrder()->first();
        $user = User::where('id', '<>', $consultation->author_id)->inRandomOrder()->first();

        return [
            'consultation_id' => $consultation->getKey(),
            'user_id' => $user->getKey(),
            'status' => $this->faker->randomElement(ConsultationParticipantStatusEnum::getValues()),
        ];
    }
}
