<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use EscolaLms\Consultations\Tests\Models\User;

class ConsultationDestroyApiTest extends TestCase
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
    }

    private function initVariable(): void
    {
        $this->consultation = Consultation::factory()->create();
        $this->apiUrl = '/api/admin/consultations/' . $this->consultation->getKey();
    }

    public function testConsultationDestroyUnauthorized(): void
    {
        $this->initVariable();
        $response = $this->json('DELETE', $this->apiUrl);
        $response->assertUnauthorized();
    }

    public function testConsultationDestroy(): void
    {
        $this->initVariable();
        $response = $this->actingAs($this->user, 'api')->json(
            'DELETE',
            $this->apiUrl
        );
        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationDestroyFailed(): void
    {
        $consultation = Consultation::factory()->create();
        $id = $consultation->getKey();
        $consultation->delete();
        $response = $this->actingAs($this->user, 'api')->json(
            'DELETE',
            '/api/admin/consultations/' . $id
        );
        $response->assertNotFound();
    }
}
