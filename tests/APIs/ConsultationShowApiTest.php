<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationShowApiTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private string $apiUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->consultation = Consultation::factory()->create();
        $this->apiUrl = '/api/admin/consultations/' . $this->consultation->getKey();
    }

    public function testConsultationShowUnauthorized(): void
    {
        $response = $this->json('GET', $this->apiUrl);
        $response->assertUnauthorized();
    }

    public function testConsultationShow(): void
    {
        $response = $this->actingAs($this->user, 'api')->json(
            'GET',
            $this->apiUrl
        );
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->consultation->getKey(),
            'name' => $this->consultation->name,
            'status' => $this->consultation->status,
            'created_at' => $this->consultation->created_at,
        ]);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->has('author')->etc()
            )->etc()
        );
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationShowFailed(): void
    {
        $consultation = Consultation::factory()->create();
        $id = $consultation->getKey();
        $consultation->delete();
        $consultationUpdate = Consultation::factory()->make();
        $response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/admin/consultations/' . $id,
            $consultationUpdate->toArray()
        );
        $response->assertNotFound();
    }

    public function testConsultationShowAPI()
    {
        $response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/' . $this->consultation->getKey()
        );
        $response->assertOk();
    }
}
