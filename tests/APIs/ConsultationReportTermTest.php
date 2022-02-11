<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Events\CartOrderPaid;
use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Listeners\ReportTermListener;
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
    }

    public function testConsultationReportTerm()
    {
        Event::fake(CartOrderPaid::class);
        $this->initVariable();
        $event = new CartOrderPaid($this->user, $this->order);
        $listener = app(ReportTermListener::class);
        $listener->handle($event);
    }
}
