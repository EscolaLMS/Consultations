<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationGenerateJitsiTest extends TestCase
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

    private function initVariable(): void
    {
        $consultation = Consultation::factory()->create();
        $this->consultationUserPivot = ConsultationUserPivot::factory([
            'consultation_id' => $consultation->getKey(),
            'user_id' => $this->user->getKey(),
        ])->create();
    }

    public function testGenerateJitsiUnAuthorized(): void
    {
        $response = $this->json('GET', 'api/consultations/generate-jitsi/1');
        $response->assertUnauthorized();
    }

    public function testGenerateJitsiWithApprovedTerm(): void
    {
        $consultation = Consultation::factory()->create();
        $this->consultationUserPivot = ConsultationUserPivot::factory([
            'consultation_id' => $consultation->getKey(),
            'user_id' => $this->user->getKey(),
            'executed_at' => now()->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
        ])->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', 'api/consultations/generate-jitsi/' . $this->consultationUserPivot->getKey());
        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('data',
                fn (AssertableJson $json) => $json->has('data',
                    fn (AssertableJson $json) => $json->has('jwt')
                        ->has('userInfo',
                            fn (AssertableJson $json) => $json
                                ->where('displayName', "{$this->user->first_name} {$this->user->last_name}")
                                ->where('email', $this->user->email)
                        )
                        ->where('roomName', lcfirst(Str::studly($this->consultationUserPivot->consultation->name)))
                        ->etc()
                )
                    ->etc()
            )->where('success', true)->etc()
        );
    }

    public function testGenerateJitsiWithRejectedTerm(): void
    {
        $this->initVariable();
        $response = $this->actingAs($this->user, 'api')->json('GET', 'api/consultations/generate-jitsi/' . $this->consultationUserPivot->getKey());
        $response->assertNotFound();
        $response->assertJson(fn (AssertableJson $json) => $json->where('message', __('Consultation term is not available'))->etc());
    }

    public function testGenerateJitsiBeforeExecutedAt(): void
    {
        $this->initVariable();
        $response = $this->actingAs($this->user, 'api')->json('GET', 'api/consultations/generate-jitsi/' . $this->consultationUserPivot->getKey());
        $response->assertNotFound();
        $response->assertJson(fn (AssertableJson $json) => $json->where('message', __('Consultation term is not available'))->etc());
    }
}
