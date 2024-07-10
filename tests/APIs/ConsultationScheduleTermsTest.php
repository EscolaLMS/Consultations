<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Http\Resources\ConsultationAuthorResource;
use EscolaLms\Consultations\Http\Resources\ConsultationTermResource;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationScheduleTermsTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private ConsultationUserPivot $consultationUserPivot;
    private string $apiUrl;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->student = User::factory()->create();
        $this->student->guard_name = 'api';
        $this->student->assignRole('student');
    }

    private function initVariable(): void
    {
        $this->consultationTerms = collect();
        $this->consultation = Consultation::factory([
            'author_id' => $this->user
        ])->create();
        $this->apiUrl = '/api/admin/consultations/' . $this->consultation->getKey() . '/schedule';
        $this->consultation->proposedTerms()->saveMany(ConsultationProposedTerm::factory(3)->create());
        $this->consultationUserPivot = ConsultationUserPivot::factory([
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $this->student->getKey(),
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
        $this->response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->first(fn (AssertableJson $json) =>
                    $json->where('consultation_term_id', fn ($json) => $json === $this->consultationUserPivot->getKey())
                    ->where('is_started', fn ($json) => $json === true)
                    ->where('is_ended', fn ($json) => $json === false)
                    ->where('in_coming', fn ($json) => $json === false)
                    ->etc()
                )
            )->etc()
        );
    }

    public function testConsultationScheduleForTutor(): void
    {
        $this->initVariable();
        $this->response = $this->actingAs($this->user, 'api')->get('/api/consultations/my-schedule');
        $this->response->assertJson(fn (AssertableJson $json) => $json->has('data',
            fn (AssertableJson $json) =>
                $json->each(fn (AssertableJson $json) => $json
                    ->etc()
                )
        )->etc());
        $this->response->assertJsonFragment([
            'consultation_term_id' => $this->consultationUserPivot->getKey(),
            'date' => isset($this->consultationUserPivot->executed_at) ? Carbon::make($this->consultationUserPivot->executed_at)->format("Y-m-d\TH:i:s.000000\Z") : '',
            'status' => $this->consultationUserPivot->executed_status ?? '',
        ]);
    }

    public function testConsultationScheduleForNotTutor(): void
    {
        $this->initVariable();
        $this->response = $this->actingAs($this->student, 'api')->get('/api/consultations/my-schedule');
        $this->response->assertForbidden();
    }
}
