<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Auth\Models\User;
use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use EscolaLms\Payments\Models\Payment;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConsultationReportTermTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = config('auth.providers.users.model')::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
    }

    private function initVariable(): void
    {
        $consultationsForOrder = Consultation::factory(3)->create();
        $price = $consultationsForOrder->reduce(fn ($acc, Consultation $consultation) => $acc + $consultation->getBuyablePrice(), 0);

        $order = Order::factory()->has(Payment::factory()->state([
            'amount' => $price,
            'billable_id' => $this->user->getKey(),
            'billable_type' => User::class,
        ]))
            ->afterCreating(
                fn (Order $order) => $order->items()->saveMany(
                    $consultationsForOrder->map(
                        function (Consultation $consultation) {
                            return OrderItem::query()->make([
                                'quantity' => 1,
                                'buyable_id' => $consultation->getKey(),
                                'buyable_type' => Consultation::class,
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
        $this->initVariable();
    }
}
