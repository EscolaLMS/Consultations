<?php

namespace EscolaLms\Consultations\Tests\Services;

use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConsultationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private Consultation $consultation;
    private ConsultationServiceContract $consultationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('student');
        $this->consultation = Consultation::factory()->create([
            'max_session_students' => 1,
        ]);
        $this->consultationService = \App::make(ConsultationServiceContract::class);
    }

    public function test_term_is_not_busy_for_user(): void
    {
        $date = now()->addDay()->format('Y-m-d H:i:s');
        $this->assertFalse($this->consultationService->termIsBusyForUser($this->consultation->getKey(), $date, $this->user->getKey()));
    }

    public function test_term_is_busy_for_user_already_approved(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Term is busy for this user.');
        $date = now()->addDay()->format('Y-m-d H:i:s');

        ConsultationUserPivot::factory([
            'executed_at' => $date,
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $this->user->getKey()
        ])->create();

        $this->consultationService->termIsBusyForUser($this->consultation->getKey(), $date, $this->user->getKey());
    }

    public function test_term_is_busy_for_user_other_user(): void
    {
        $date = now()->addDay()->format('Y-m-d H:i:s');

        $user = User::factory()->create();
        $user->assignRole('student');

        ConsultationUserPivot::factory([
            'executed_at' => $date,
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $user->getKey()
        ])->create();

        $this->assertTrue($this->consultationService->termIsBusyForUser($this->consultation->getKey(), $date, $this->user->getKey()));
    }

    public function test_term_is_not_busy_for_user_multiple_students(): void
    {
        $date = now()->addDay()->format('Y-m-d H:i:s');

        $user = User::factory()->create();
        $user->assignRole('student');

        $this->consultation->update([
            'max_session_students' => 2,
        ]);

        ConsultationUserPivot::factory([
            'executed_at' => $date,
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $user->getKey()
        ])->create();

        $this->assertFalse($this->consultationService->termIsBusyForUser($this->consultation->getKey(), $date, $this->user->getKey()));
    }
}
