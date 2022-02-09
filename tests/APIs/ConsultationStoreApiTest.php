<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConsultationStoreApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = config('auth.providers.users.model')::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
    }

    public function testConsultationStoreUnauthorized(): void
    {
        $response = $this->json('POST','/api/admin/consultations');
        $response->assertUnauthorized();
    }

    public function testConsultationStore(): void
    {
        $consultation = Consultation::factory()->make()->toArray();
        $response = $this->actingAs($this->user, 'api')->json(
            'POST',
            '/api/admin/consultations',
            $consultation
        );
        $response->assertCreated();
        $response->assertJsonFragment([
            'name' => $consultation['name'],
            'status' => $consultation['status'],
            'author_id' => $consultation['author_id'],
            'duration' => $consultation['duration'],
        ]);
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationStoreRequiredValidation(): void
    {
        $response = $this->actingAs($this->user, 'api')->json('POST',
            '/api/admin/consultations');
        $response->assertJsonValidationErrors(['name', 'status', 'description', 'author_id']);
    }
}
