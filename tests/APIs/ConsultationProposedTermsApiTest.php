<?php

namespace EscolaLms\Consultations\Tests\APIs;

use Carbon\Carbon;
use EscolaLms\Cart\Events\OrderPaid;
use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Listeners\ReportTermListener;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationProposedTermsApiTest extends TestCase
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
        $this->consultation = Consultation::factory()->create();
    }

    private function initVariable(): void
    {
        $consultationsForOrder = Consultation::factory(3)
            ->create()
            ->each(fn (Consultation $consultation) => $consultation->proposedTerms()->saveMany(ConsultationProposedTerm::factory(3)->create()));
        $price = $consultationsForOrder->reduce(fn ($acc, Consultation $consultation) => $acc + $consultation->getBuyablePrice(), 0);
        $this->order = Order::factory()->afterCreating(
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
        Event::fake();
        $event = new OrderPaid($this->order, $this->user);
        $listener = app(ReportTermListener::class);
        $listener->handle($event);
    }

    public function testConsultationProposedTermsUnauthorized()
    {
        $this->response = $this->json(
            'GET',
            'api/consultations/proposed-terms/1'
        );
        $this->response->assertUnauthorized();
    }

    public function testConsultationProposedTerms()
    {
        $this->initVariable();
        $orderItem = $this->order->items()->first();
        $consultationTerms = $orderItem->buyable->proposedTerms->map(
            fn (ConsultationProposedTerm $consultationTerm) => Carbon::make($consultationTerm->proposed_at)->format('Y-m-d H:i:s')
        )->toArray();
        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            'api/consultations/proposed-terms/' . $orderItem->getKey()
        );
        $this->response->assertOk();
        $this->response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', true)
            ->where('data', $consultationTerms)
            ->etc()
        );
    }
}
