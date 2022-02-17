<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationApiTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = config('auth.providers.users.model')::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->consultation = Consultation::factory()->create();
        $this->consultation->author()->associate($this->user);
    }

    public function testConsultationsList(): void
    {
        $this->response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations');
        $this->response->assertOk();
    }

    public function testConsultationsListByDate(): void
    {
        $now = now()->modify('+1 days');
        $cons = Consultation::factory([
            'active_from' => $now,
            'active_to' => (clone $now)->modify('+1 days')
        ])->create();
        $this->response = $this->actingAs($this->user, 'api')->get('/api/consultations');
        $this->response->assertOk();
        $this->response->assertJsonMissing(['id' => $cons->getKey()]);
    }

    public function testConsultationsListWithFilter(): void
    {
        $filterData = [
            'base_price=' . $this->consultation->base_price,
            'name=' . $this->consultation->name,
            'status[]=' . $this->consultation->status,
        ];
        $this->response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations?' . implode('&', $filterData));
        $this->response->assertOk();
        $this->response->assertJsonFragment([
            'id' => $this->consultation->getKey(),
            'name' => $this->consultation->name,
            'status' => $this->consultation->status,
            'created_at' => $this->consultation->created_at,
        ]);
    }

    public function testConsultationsListUnauthorized(): void
    {
        $this->response = $this->json('GET','/api/admin/consultations');
        $this->response->assertUnauthorized();
    }
}
