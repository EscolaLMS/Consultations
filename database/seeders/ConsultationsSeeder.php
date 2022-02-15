<?php

namespace EscolaLms\Consultations\Database\Seeders;

use EscolaLms\Consultations\Models\Consultation;
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
