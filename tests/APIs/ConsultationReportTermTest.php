<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\RejectTerm;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationUserRepositoryContract;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

class ConsultationReportTermTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;
    private Order $order;
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
        $consultation = Consultation::factory()->create();
        $this->consultationUserPivot = ConsultationUserPivot::factory([
            'consultation_id' => Consultation::factory()->create()->getKey(),
            'user_id' => $this->user->getKey(),
            'executed_at' => null,
            'executed_status' => ConsultationTermStatusEnum::NOT_REPORTED,
        ])->create();
    }

    public function testConsultationReportTerma(): void
    {
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
        $userId = $this->user->getKey();
        Event::assertDispatched(ApprovedTerm::class, fn (ApprovedTerm $event) =>
            $event->getUser()->getKey() === $userId &&
            $event->getConsultationTerm()->getKey() === $this->consultationUserPivot->getKey()
        );
        $this->consultationUserPivot->refresh();
        $this->response->assertOk();
        $this->assertTrue($this->consultationUserPivot->executed_status === ConsultationTermStatusEnum::APPROVED);
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
        $this->initVariable();
        $orderItem = Order::whereUserId($this->user->getKey())->first()->items()->first();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')->json('POST',
            '/api/consultations/report-term/' . $orderItem->getKey(),
            [
                'term' => $now->format('Y-m-d H:i:s')
            ]
        );
        $consultationTermsRepositoryContract = app(ConsultationTermsRepositoryContract::class);
        $consultationTerm = $consultationTermsRepositoryContract->findByOrderItem($orderItem->getKey());
        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/reject-term/' . $consultationTerm->getKey()
        );
        $userId = $this->user->getKey();
        Event::assertDispatched(RejectTerm::class, fn (RejectTerm $event) =>
            $event->getUser()->getKey() === $userId &&
            $event->getConsultationTerm()->getKey() === $consultationTerm->getKey()
        );
        $consultationTerm->refresh();
        $this->response->assertOk();
        $this->assertTrue($consultationTerm->executed_status === ConsultationTermStatusEnum::REJECT);
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
