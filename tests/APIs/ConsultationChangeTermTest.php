<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ChangeTerm;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use EscolaLms\Core\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

class ConsultationChangeTermTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->consultation = Consultation::factory()->create();
        $this->consultation->author()->associate($this->user);
        $this->consultation->attachToUser($this->user);
    }

    public function testChangeTerm()
    {
        Event::fake();
        $term = $this->consultation->terms()->first();
        $newTerm = now()->modify('+2 hours')->format('Y-m-d H:i:s');
        $this->response = $this->actingAs($this->user, 'api')->post(
            '/api/admin/consultations/change-term/' . $term->getKey(),
            ['executed_at' => $newTerm]
        );
        $this->response->assertOk();
        $term->refresh();
        $this->assertTrue($term->executed_at === $newTerm);
        $this->assertTrue($term->executed_status === ConsultationTermStatusEnum::APPROVED);
        Event::assertDispatched(ChangeTerm::class);
    }

    public function testChangeTermUnauthorized()
    {
        $this->response = $this->json('POST','/api/admin/consultations/change-term/1');
        $this->response->assertUnauthorized();
    }
}
