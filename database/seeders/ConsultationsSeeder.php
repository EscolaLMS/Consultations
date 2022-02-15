<?php

namespace EscolaLms\Consultations\Database\Seeders;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationParticipant;
use Illuminate\Database\Seeder;

class ConsultationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Consultation::factory(10);
    }
}
