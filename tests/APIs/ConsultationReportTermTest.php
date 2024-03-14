<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Events\ApprovedTermWithTrainer;
use EscolaLms\Consultations\Events\RejectTermWithTrainer;
use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\RejectTerm;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationReportTermTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;
    private Consultation $consultation;
    private ConsultationUserPivot $consultationUserPivot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
    }

    private function initVariable(): void
    {
        $this->consultation = Consultation::factory()->create();
        $this->consultationUserPivot = ConsultationUserPivot::factory([
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $this->user->getKey(),
            'executed_at' => null,
            'executed_status' => ConsultationTermStatusEnum::NOT_REPORTED,
        ])->create();
    }

    public function testConsultationReportTerm(): void
    {
        $this->initVariable();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')
            ->json('POST',
                '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            );
        $this->consultationUserPivot->refresh();
        $this->assertTrue($this->consultationUserPivot->executed_at === $now->format('Y-m-d H:i:s'));
        $this->assertTrue($this->consultationUserPivot->executed_status === ConsultationTermStatusEnum::REPORTED);
        $this->response->assertOk();
    }

    public function testConsultationReportTermMultipleTerm(): void
    {
        $this->initVariable();
        $now = now()->modify('+1 day');
        ConsultationUserPivot::factory([
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'consultation_id' => $this->consultationUserPivot->consultation_id,
            'user_id' => $this->user->getKey()
        ])->create();
        $this->response = $this->actingAs($this->user, 'api')
            ->json('POST',
                '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            );
        $this->response->assertJson(fn (AssertableJson $json) => $json->where(
                'message', fn(string $json) => $json === __('You already reported this term.')
            )->etc()
        );
        $this->response->assertStatus(400);
    }

    public function testConsultationReportTermMultipleTermDifferentUsers(): void
    {
        $this->initVariable();
        $now = now()->modify('+1 day');

        $this->consultation->update([
            'max_session_students' => 4,
        ]);

        $user = User::factory()->create();
        $user->assignRole('student');
        ConsultationUserPivot::factory([
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $user->getKey()
        ])->create();

        $this->response = $this->actingAs($this->user, 'api')
            ->json('POST',
                '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            )->assertOk();
        $this->consultationUserPivot->refresh();
        $this->assertTrue($this->consultationUserPivot->executed_at === $now->format('Y-m-d H:i:s'));
        $this->assertTrue($this->consultationUserPivot->executed_status === ConsultationTermStatusEnum::REPORTED);
    }

    public function testConsultationReportTermMultipleTermDifferentUsersLimit(): void
    {
        $this->initVariable();
        $now = now()->modify('+1 day');

        $this->consultation->update([
            'max_session_students' => 1,
        ]);

        $user = User::factory()->create();
        $user->assignRole('student');
        ConsultationUserPivot::factory([
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $user->getKey()
        ])->create();

        $this->response = $this->actingAs($this->user, 'api')
            ->json('POST',
                '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            )->assertStatus(400)
            ->assertJson(fn (AssertableJson $json) => $json->where(
                    'message', fn(string $json) => $json === __('Term is busy, change your term.')
                )->etc()
            );
    }

    public function testConsultationReportTermUnauthorized(): void
    {
        $this->initVariable();
        $now = now()->modify('+1 day');
        $this->response = $this->json('POST',
                '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            );
        $this->response->assertUnauthorized();
    }

    public function testConsultationTermApproved(): void
    {
        Event::fake([
            ApprovedTerm::class,
            ApprovedTermWithTrainer::class,
        ]);
        $this->initVariable();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')->json('POST',
            '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
            [
                'term' => $now->format('Y-m-d H:i:s')
            ]
        );
        $this->consultationUserPivot->refresh();
        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/approve-term/' . $this->consultationUserPivot->getKey()
        );
        $this->consultationUserPivot->refresh();
        $userId = $this->user->getKey();
        Event::assertDispatched(ApprovedTerm::class, fn (ApprovedTerm $event) =>
            $event->getUser()->getKey() === $userId &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        Event::assertDispatched(ApprovedTermWithTrainer::class, fn (ApprovedTermWithTrainer $event) =>
            $event->getUser()->getKey() === $this->user->getKey() &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        $this->consultationUserPivot->refresh();
        $this->response->assertOk();
        $this->assertTrue($this->consultationUserPivot->executed_status === ConsultationTermStatusEnum::APPROVED);
    }

    public function testConsultationMultipleTermApproved(): void
    {
        Event::fake([
            ApprovedTerm::class,
            ApprovedTermWithTrainer::class,
        ]);
        $this->initVariable();
        $now = now()->modify('+1 day');

        $this->consultation->update([
            'max_session_students' => 3,
        ]);

        $student1 = User::factory()->create();
        $student1->assignRole('student');

        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $consultationStudent1 = ConsultationUserPivot::factory([
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::REPORTED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $student1->getKey()
        ])->create();

        $consultationStudent2 = ConsultationUserPivot::factory([
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::REPORTED,
            'consultation_id' => $this->consultation->getKey(),
            'user_id' => $student2->getKey()
        ])->create();

        $this->consultationUserPivot->update([
            'executed_at' => $now->format('Y-m-d H:i:s'),
            'executed_status' => ConsultationTermStatusEnum::REPORTED,
        ]);

        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/approve-term/' . $this->consultationUserPivot->getKey()
        )->assertOk();

        $this->consultationUserPivot->refresh();
        Event::assertDispatched(ApprovedTerm::class, fn (ApprovedTerm $event) =>
            $event->getUser()->getKey() === $this->user->getKey() &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        Event::assertDispatched(ApprovedTermWithTrainer::class, fn (ApprovedTermWithTrainer $event) =>
            $event->getUser()->getKey() === $this->user->getKey() &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        $this->assertTrue($this->consultationUserPivot->executed_status === ConsultationTermStatusEnum::APPROVED);

        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/approve-term/' . $consultationStudent1->getKey()
        )->assertOk();

        $consultationStudent1->refresh();
        Event::assertDispatched(ApprovedTerm::class, fn (ApprovedTerm $event) =>
            $event->getUser()->getKey() === $student1->getKey() &&
            $event->getConsultationTerm()->getKey() === $consultationStudent1->getKey()
        );
        Event::assertDispatched(ApprovedTermWithTrainer::class, fn (ApprovedTermWithTrainer $event) =>
            $event->getUser()->getKey() === $this->user->getKey() &&
            $event->getConsultationTerm()->getKey() === $consultationStudent1->getKey()
        );
        $this->assertTrue($consultationStudent1->executed_status === ConsultationTermStatusEnum::APPROVED);

        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/approve-term/' . $consultationStudent2->getKey()
        )->assertOk();

        $consultationStudent2->refresh();
        Event::assertDispatched(ApprovedTerm::class, fn (ApprovedTerm $event) =>
            $event->getUser()->getKey() === $student2->getKey() &&
            $event->getConsultationTerm()->getKey() === $consultationStudent2->getKey()
        );
        Event::assertDispatched(ApprovedTermWithTrainer::class, fn (ApprovedTermWithTrainer $event) =>
            $event->getUser()->getKey() === $this->user->getKey() &&
            $event->getConsultationTerm()->getKey() === $consultationStudent2->getKey()
        );
        $this->assertTrue($consultationStudent2->executed_status === ConsultationTermStatusEnum::APPROVED);
    }

    public function testConsultationTermApprovedUnauthorized(): void
    {
        $this->response = $this->json(
            'GET',
            '/api/consultations/approve-term/1'
        );
        $this->response->assertUnauthorized();
    }

    public function testConsultationTermReject(): void
    {
        Event::fake([
            RejectTerm::class,
            RejectTermWithTrainer::class
        ]);
        $this->initVariable();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')->json('POST',
            '/api/consultations/report-term/' . $this->consultationUserPivot->getKey(),
            [
                'term' => $now->format('Y-m-d H:i:s')
            ]
        );
        $this->consultationUserPivot->refresh();
        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/reject-term/' . $this->consultationUserPivot->getKey()
        );
        $userId = $this->user->getKey();
        Event::assertDispatched(RejectTerm::class, fn (RejectTerm $event) =>
            $event->getUser()->getKey() === $userId &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        Event::assertDispatched(RejectTermWithTrainer::class, fn (RejectTermWithTrainer $event) =>
            $event->getUser()->getKey() === $this->user->getKey() &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        $this->consultationUserPivot->refresh();
        $this->response->assertOk();
        $this->assertTrue($this->consultationUserPivot->executed_status === ConsultationTermStatusEnum::REJECT);
    }

    public function testConsultationTermRejectUnauthorized(): void
    {
        $this->response = $this->json(
            'GET',
            '/api/consultations/reject-term/1'
        );
        $this->response->assertUnauthorized();
    }
}
