<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;

class ConsultationScheduleTermsTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private ConsultationUserPivot $consultationUserPivot;
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
        $this->consultationTerms = collect();
        $this->consultation = Consultation::factory()->create();
        $this->apiUrl = '/api/admin/consultations/' . $this->consultation->getKey() . '/schedule';
        $this->consultation->proposedTerms()->saveMany(ConsultationProposedTerm::factory(3)->create());
        $this->consultationUserPivot = ConsultationUserPivot::factory([
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $this->user->getKey(),
            'executed_at' => now()->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::APPROVED
        ])->create();
    }

    public function testConsultationTermsListUnauthorized(): void
    {
        $this->response = $this->json('GET','/api/admin/consultations/1/schedule');
        $this->response->assertUnauthorized();
    }

    public function testConsultationTermsList(): void
    {
        $this->initVariable();
        $this->response = $this->actingAs($this->user, 'api')->get($this->apiUrl);
        $this->response->assertOk();
        $consultationTerms = collect([$this->consultationUserPivot])->map(function (ConsultationUserPivot $element) {
            return [
                'date' => Carbon::make($element->executed_at)->format('Y-m-d H:i:s') ?? '',
                'status' => $element->executed_status ?? '',
                'author' => $element->consultation->author->toArray() ?? '',
                'is_ended' => $element->isEnded()
            ];
        })->toArray();
        $this->response->assertJsonFragment($consultationTerms);
    }
}
