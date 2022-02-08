<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;

class ConsultationShowApiTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private string $apiUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = config('auth.providers.users.model')::factory()->create();
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
            'author_id' => $this->consultation->author_id,
            'duration' => $this->consultation->duration,
            'created_at' => $this->consultation->created_at,
        ]);
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationShowFailed(): void
    {
        $consultation = Consultation::factory()->create();
        $id = $consultation->getKey();
        $consultation->delete();
        $consultationUpdate = Consultation::factory()->make();
        $response = $this->actingAs($this->user, 'api')->json(
            'PUT',
            '/api/admin/consultations/' . $id,
            $consultationUpdate->toArray()
        );
        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
