<?php

namespace EscolaLms\Consultations\Database\Seeders;

use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Core\Models\User;
use Illuminate\Database\Seeder;

class ConsultationTermsSeeder extends Seeder
{
    public function run()
    {
        $users = User::limit(5)->get();
        Consultation::factory([], 5)->create()->each(function (Consultation $consultation) use($users) {
            ConsultationUserPivot::factory([
                'consultation_id' => $consultation->getKey(),
                'user_id' => $users->random(1)->first()->getKey(),
                'executed_at' => now()->format('Y-m-d H:i:s'),
                'executed_status' => ConsultationTermStatusEnum::APPROVED,
            ])->create();
        });
    }
}
