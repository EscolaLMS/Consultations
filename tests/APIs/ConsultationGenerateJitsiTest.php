<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Tests\Models\ConsultationTest;
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

    public function testGenerateJitsiUnAuthorized(): void
    {
        $response = $this->json('GET', 'api/consultations/generate-jitsi/1');
        $response->assertUnauthorized();
    }

    public function testGenerateJitsiWithApprovedTerm(): void
    {
        $this->initVariable();
        $orderItem = $this->order->items()->first();
        $consultationTerm = ConsultationTerm::factory([
            'user_id' => $this->user->getKey(),
            'order_item_id' => $orderItem->getKey(),
            'executed_status' => ConsultationTermStatusEnum::APPROVED
        ])->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', 'api/consultations/generate-jitsi/' . $consultationTerm->getKey());
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
                        ->where('roomName', lcfirst(Str::studly($consultationTerm->orderItem->buyable->name)))
                        ->etc()
                )
                    ->etc()
            )->where('success', true)->etc()
        );
    }

    public function testGenerateJitsiWithRejectedTerm(): void
    {
        $this->initVariable();
        $orderItem = $this->order->items()->first();
        $consultationTerm = ConsultationTerm::factory([
            'user_id' => $this->user->getKey(),
            'order_item_id' => $orderItem->getKey(),
            'executed_status' => ConsultationTermStatusEnum::REJECT
        ])->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', 'api/consultations/generate-jitsi/' . $consultationTerm->getKey());
        $response->assertNotFound();
        $response->assertJson(fn (AssertableJson $json) => $json->where('message', __('Consultation term is not approved'))->etc());
    }
}
