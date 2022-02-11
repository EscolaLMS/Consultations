<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Events\CartOrderPaid;
use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Listeners\ReportTermListener;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Consultations\Tests\Models\ConsultationTest;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

class ConsultationReportTermTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;
    private Order $order;
    private ConsultationTest $consultation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
    }

    private function initVariable(): void
    {
        $consultationsForOrder = ConsultationTest::factory(3)->create();
        $price = $consultationsForOrder->reduce(fn ($acc, ConsultationTest $consultation) => $acc + $consultation->getBuyablePrice(), 0);
        $this->order = Order::factory()->afterCreating(
                fn (Order $order) => $order->items()->saveMany(
                    $consultationsForOrder->map(
                        function (ConsultationTest $consultation) {
                            return OrderItem::query()->make([
                                'quantity' => 1,
                                'buyable_id' => $consultation->getKey(),
                                'buyable_type' => ConsultationTest::class,
                            ]);
                        }
                    )
                )
            )->create([
                'user_id' => $this->user->getKey(),
                'total' => $price,
                'subtotal' => $price,
            ]);
        Event::fake();
        $event = new CartOrderPaid($this->user, $this->order);
        $listener = app(ReportTermListener::class);
        $listener->handle($event);
    }

    public function testConsultationReportTerm()
    {
        $this->initVariable();
        $item = Order::where('user_id', '=', $this->user->getKey())->first()->items()->first();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')
            ->json('POST',
                '/api/consultations/report-term/' . $item->getKey(),
                [
                    'executed_at' => $now->format('Y-m-d H:i:s')
                ]
            );
        $consultationTermsRepositoryContract = app(ConsultationTermsRepositoryContract::class);
        $consultationTerm = $consultationTermsRepositoryContract->findByOrderItem($item->getKey());
        $this->assertTrue($consultationTerm->executed_at === $now->format('Y-m-d H:i:s'));
        $this->assertTrue($consultationTerm->executed_status === ConsultationTermStatusEnum::REPORTED);
        $this->response->assertOk();
        Event::assertDispatched(ReportTerm::class);
    }

    public function testConsultationReportTermUnauthorized()
    {
        $this->initVariable();
        $item = Order::where('user_id', '=', $this->user->getKey())->first()->items()->first();
        $now = now()->modify('+1 day');
        $this->response = $this->json('POST',
                '/api/consultations/report-term/' . $item->getKey(),
                [
                    'executed_at' => $now->format('Y-m-d H:i:s')
                ]
            );
        $this->response->assertUnauthorized();
    }
}
