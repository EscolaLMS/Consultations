<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;

class ConsultationUpdateApiTest extends TestCase
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

    public function testConsultationUpdateUnauthorized(): void
    {
        $response = $this->json('PUT',$this->apiUrl);
        $response->assertUnauthorized();
    }

    public function testConsultationUpdate(): void
    {
        $consultationUpdate = Consultation::factory()->make()->toArray();
        $response = $this->actingAs($this->user, 'api')->json(
            'PUT',
            $this->apiUrl,
            $consultationUpdate
        );
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->consultation->getKey(),
            'name' => $consultationUpdate['name'],
            'status' => $consultationUpdate['status'],
            'author_id' => $consultationUpdate['author_id'],
        ]);
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationUpdateFailed(): void
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
        $response->assertNotFound();
    }

    public function testConsultationUpdateRequiredValidation(): void
    {
        $response = $this->actingAs($this->user, 'api')->json(
            'PUT',
            $this->apiUrl
        );
        $response->assertJsonValidationErrors(['name', 'status', 'description', 'author_id']);
    }
}
